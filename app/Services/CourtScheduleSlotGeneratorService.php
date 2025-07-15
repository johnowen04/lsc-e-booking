<?php

namespace App\Services;

use App\Models\Court;
use App\Models\CourtScheduleSlot;
use App\Services\PricingRuleService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CourtScheduleSlotGeneratorService
{
    public function __construct(
        protected PricingRuleService $pricingRuleService,
    ) {}

    /**
     * Get all schedule slots for a specific court within a date range.
     *
     * @param int $courtId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSlotsForCourtDateRange(int $courtId, Carbon $startDate, Carbon $endDate)
    {
        return CourtScheduleSlot::query()
            ->where('court_id', $courtId)
            ->whereBetween('date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->get();
    }

    /**
     * Update pricing for a specific court schedule slot.
     */
    public function updateSlotPricing(CourtScheduleSlot $slot): void
    {
        $rule = $this->pricingRuleService->getPricingRuleForHour(
            $slot->court_id,
            $slot->date,
            $slot->start_at
        );

        $slot->update([
            'pricing_rule_id' => $rule->id,
            'price' => $rule->price_per_hour,
        ]);
    }

    /**
     * Generate schedule slots for all active courts over a date range.
     */
    public function generateSlotsForDateRange(Carbon $startDate, Carbon $endDate): void
    {
        $startTime = now();

        $courts = Court::isActive()->get();
        $dates = $this->generateDateRange($startDate, $endDate);

        $dates->each(function (Carbon $date) use ($courts) {
            $courts->each(fn(Court $court) => $this->generateSlotsForCourtAndDate($court, $date));
        });

        $this->logDuration("Generated slots for all courts", $startTime, [
            'days' => $dates->count(),
            'courts' => $courts->count(),
        ]);
    }

    /**
     * Generate schedule slots for a single court over a date range.
     */
    public function generateSlotsForCourtAndDateRange(Court $court, Carbon $startDate, Carbon $endDate): void
    {
        $startTime = now();
        $dates = $this->generateDateRange($startDate, $endDate);

        $dates->each(fn(Carbon $date) => $this->generateSlotsForCourtAndDate($court, $date));

        $this->logDuration("Generated slots for court {$court->id}", $startTime, [
            'days' => $dates->count(),
        ]);
    }

    /**
     * Generate schedule slots for a single court and date.
     */
    public function generateSlotsForCourtAndDate(Court $court, Carbon $date): void
    {
        $startHour = 8;
        $endHour = 22;
        $now = now();

        $existingStarts = CourtScheduleSlot::query()
            ->where('court_id', $court->id)
            ->whereDate('date', $date->toDateString())
            ->pluck('start_at')
            ->map(fn($dt) => (string) $dt)
            ->toArray();

        $pricingRules = $this->pricingRuleService
            ->getPricesForDate(collect([$court->id]), $date);

        $slotInserts = [];

        for ($hour = $startHour; $hour < $endHour; $hour++) {
            $startAt = $date->copy()->setTime($hour, 0, 0);
            $startAtKey = $startAt->format('Y-m-d H:i:s');

            if (in_array($startAtKey, $existingStarts, true)) {
                continue;
            }

            $pricing = $pricingRules[$court->id][$hour] ?? null;

            $slotInserts[] = [
                'court_id' => $court->id,
                'date' => $date->toDateString(),
                'start_at' => $startAt,
                'end_at' => $startAt->copy()->addHour(),
                'status' => 'available',
                'price' => $pricing?->price,
                'pricing_rule_id' => $pricing?->pricing_rule_id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($slotInserts) {
            CourtScheduleSlot::insert($slotInserts);
        }
    }

    /**
     * Refresh prices for all schedule slots between two dates.
     */
    public function refreshSlotPrices(Carbon $startDate, Carbon $endDate): void
    {
        $slots = CourtScheduleSlot::query()
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        foreach ($slots as $slot) {
            $rule = $this->pricingRuleService->getPricingRuleForHour(
                $slot->court_id,
                $slot->date,
                $slot->start_at
            );

            $slot->update([
                'pricing_rule_id' => $rule->id,
                'price' => $rule->price_per_hour,
            ]);
        }
    }

    /**
     * Generate a collection of dates between two Carbon instances.
     */
    protected function generateDateRange(Carbon $startDate, Carbon $endDate): \Illuminate\Support\Collection
    {
        $dates = collect();
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dates->push($date->copy());
        }
        return $dates;
    }

    /**
     * Log execution duration in milliseconds.
     */
    protected function logDuration(string $message, Carbon $startTime, array $context = []): void
    {
        $durationMs = $startTime->diffInMilliseconds(now());
        Log::info("⏱ {$message} in {$durationMs}ms", $context);
    }
}
