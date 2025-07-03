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
     * Generate schedule slots for all courts in a date range.
     */
    public function generateSlotsForDateRange(Carbon $startDate, Carbon $endDate): void
    {
        $startTime = now(); // for readable logs

        $courts = Court::isActive()->get();

        $dates = collect();
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dates->push($date->copy());
        }

        foreach ($dates as $date) {
            foreach ($courts as $court) {
                $this->generateSlotsForCourtAndDate($court, $date);
            }
        }

        $durationMs = $startTime->diffInMilliseconds(now());
        Log::info("⏱ Generated court schedule slots for {$dates->count()} day(s) and {$courts->count()} court(s) in {$durationMs}ms.");
    }

    /**
     * Generate schedule slots for a single court and date.
     */
    public function generateSlotsForCourtAndDate(Court $court, Carbon $date): void
    {
        $startHour = 8;
        $endHour = 22;

        // Fetch existing slots for the date and court to avoid re-creating
        $existingStarts = CourtScheduleSlot::query()
            ->where('court_id', $court->id)
            ->whereDate('date', $date->toDateString())
            ->pluck('start_at')
            ->map(fn($dt) => $dt instanceof Carbon ? $dt->format('Y-m-d H:i:s') : (string) $dt)
            ->toArray();

        // Prefetch pricing rules for the day for this court
        $pricingRules = $this->pricingRuleService
            ->getPricesForDate(collect([$court->id]), $date);

        $now = now();
        $slotInserts = [];

        for ($hour = $startHour; $hour < $endHour; $hour++) {
            $startAt = $date->copy()->setTime($hour, 0, 0);
            $startAtKey = $startAt->format('Y-m-d H:i:s');

            if (in_array($startAtKey, $existingStarts)) {
                continue;
            }

            $endAt = $startAt->copy()->addHour();
            $pricing = $pricingRules[$court->id][$hour] ?? null;

            $slotInserts[] = [
                'court_id' => $court->id,
                'date' => $date->toDateString(),
                'start_at' => $startAt,
                'end_at' => $endAt,
                'status' => 'available',
                'price' => $pricing?->price,
                'pricing_rule_id' => $pricing?->pricing_rule_id ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($slotInserts)) {
            CourtScheduleSlot::insert($slotInserts);
        }
    }


    /**
     * Refresh prices for all existing future schedule slots.
     */
    public function refreshSlotPrices(Carbon $startDate, Carbon $endDate): void
    {
        $slots = CourtScheduleSlot::query()
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        foreach ($slots as $slot) {
            $pricingRule = $this->pricingRuleService->getPricingRuleForHour(
                $slot->court_id,
                $slot->date,
                $slot->start_at // already a Carbon instance
            );

            $slot->update([
                'pricing_rule_id' => $pricingRule?->id,
                'price' => $pricingRule?->price_per_hour,
            ]);
        }
    }
}
