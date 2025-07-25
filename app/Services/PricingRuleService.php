<?php

namespace App\Services;

use App\Models\PricingRule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PricingRuleService
{
    protected int $ttlMinutes = 10;

    public function hasAnyPricingRule(): bool
    {
        return PricingRule::query()->exists();
    }

    public function getPricingRuleForHour(int $courtId, string $date, Carbon $hour): PricingRule
    {
        // Bypass cache for now
        $rules = $this->loadApplicableRules($courtId, $date);

        $rule = $rules->first(function ($rule) use ($hour) {
            $start = $hour->copy()->setTimeFromTimeString($rule->time_start);
            $end = $hour->copy()->setTimeFromTimeString($rule->time_end);

            if ($start->eq(Carbon::createFromTime(0)) && $end->eq(Carbon::createFromTime(0))) {
                return true;
            }

            return $start < $end
                ? $hour->gte($start) && $hour->lt($end)
                : $hour->gte($start) || $hour->lt($end);
        });

        if (! $rule) {
            throw new \RuntimeException(
                "❌ No pricing rule matched for court_id={$courtId}, date={$date}, hour={$hour->toTimeString()}"
            );
        }

        return $rule;
    }

    public function getPriceForHour(int $courtId, string $date, Carbon $hour): float
    {
        return $this->getPricingRuleForHour($courtId, $date, $hour)->price_per_hour;
    }

    public function getPricesForDate(Collection $courtIds, Carbon $date): Collection
    {
        $dateString = $date->toDateString();

        return $courtIds->mapWithKeys(function ($courtId) use ($dateString) {
            $hourPrices = collect(range(8, 21)) // 08:00 to 21:00
                ->mapWithKeys(function ($hour) use ($courtId, $dateString) {
                    $hourTime = Carbon::parse($dateString)->setTime($hour, 0);

                    try {
                        $rule = $this->getPricingRuleForHour($courtId, $dateString, $hourTime);
                        $price = $rule->price_per_hour;
                        $ruleId = $rule->id;
                    } catch (\Throwable) {
                        $price = 0;
                        $ruleId = null;
                    }

                    return [
                        $hour => (object) [
                            'price' => $price,
                            'pricing_rule_id' => $ruleId,
                        ],
                    ];
                });

            return [$courtId => $hourPrices];
        });
    }

    protected function loadApplicableRules(int $courtId, string $date): Collection
    {
        $carbonDate = Carbon::parse($date);
        $dayOfWeek = $carbonDate->dayOfWeekIso;

        return PricingRule::query()
            ->where('is_active', true)
            ->where(fn($q) => $q->whereNull('court_id')->orWhere('court_id', $courtId))
            ->where(fn($q) => $q->whereNull('day_of_week')->orWhere('day_of_week', $dayOfWeek))
            ->where(fn($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', $date))
            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $date))
            ->orderByDesc('priority')
            ->get();
    }

    /**
     * Caching methods kept for future use — not currently used
     */
    protected function getCachedRules(int $courtId, string $date): Collection
    {
        $key = $this->cacheKey($courtId, $date);

        return Cache::remember($key, now()->addMinutes($this->ttlMinutes), function () use ($courtId, $date) {
            return $this->loadApplicableRules($courtId, $date);
        });
    }

    protected function cacheKey(int $courtId, string $date): string
    {
        return "pricing_rules:court:{$courtId}:date:{$date}";
    }

    public function clearCachedRules(int $courtId, ?string $date = null): void
    {
        if ($date) {
            Cache::forget($this->cacheKey($courtId, $date));
        } else {
            // If no date provided, clear all by courtId
            $dates = collect(range(-7, 7))->map(fn($offset) => now()->addDays($offset)->toDateString());
            foreach ($dates as $dateToClear) {
                Cache::forget($this->cacheKey($courtId, $dateToClear));
            }
        }
    }
}
