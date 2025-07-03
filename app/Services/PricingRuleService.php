<?php

namespace App\Services;

use App\Models\PricingRule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PricingRuleService
{
    protected int $ttlMinutes = 10;

    public function calculateTotalForGroupedSlots(Collection $groupedSlots): float
    {
        return $groupedSlots->sum(function (array $group) {
            $date = Carbon::parse($group['date']);
            $startsAt = Carbon::parse($group['starts_at']);
            $endsAt = Carbon::parse($group['ends_at']);
            $courtId = $group['court_id'];

            $hour = $startsAt->copy();
            $total = 0;

            while ($hour < $endsAt) {
                $rule = $this->getPricingRuleForHour($courtId, $date->toDateString(), $hour);
                $total += $rule->price_per_hour;
                $hour->addHour();
            }

            return $total;
        });
    }

    public function calculatePricePerGroup(array $group): float
    {
        $date = Carbon::parse($group['date']);
        $startsAt = Carbon::parse("{$group['date']} {$group['starts_at']}");
        $endsAt = Carbon::parse("{$group['date']} {$group['ends_at']}");
        $courtId = $group['court_id'];

        $hour = $startsAt->copy();
        $total = 0;

        while ($hour < $endsAt) {
            $rule = $this->getPricingRuleForHour($courtId, $date->toDateString(), $hour);
            $total += $rule->price_per_hour;
            $hour->addHour();
        }

        return $total;
    }

    public function hasAnyPricingRule(): bool
    {
        return PricingRule::query()->exists();
    }

    public function getPricingRuleForHour(int $courtId, string $date, Carbon $hour): Model
    {
        $rules = $this->getCachedRules($courtId, $date);

        $rule = $rules->first(function ($rule) use ($hour) {
            $start = Carbon::createFromTimeString($rule->time_start);
            $end = Carbon::createFromTimeString($rule->time_end);

            if ($start->eq(Carbon::createFromTime(0)) && $end->eq(Carbon::createFromTime(0))) {
                return true; // applies to all hours
            }

            return $start < $end
                ? ($hour->between($start, $end->subSecond()))
                : ($hour->gte($start) || $hour->lt($end));
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
                    $hourTime = Carbon::createFromFormat('H:i', "$hour:00");

                    try {
                        $price = $this->getPriceForHour($courtId, $dateString, $hourTime);
                    } catch (\Throwable) {
                        $price = 0;
                    }

                    return [$hour => (object) ['price' => $price]];
                });

            return [$courtId => $hourPrices];
        });
    }

    protected function getCachedRules(int $courtId, string $date): Collection
    {
        $key = "pricing_rules_{$courtId}_{$date}";

        return Cache::remember($key, now()->addMinutes($this->ttlMinutes), function () use ($courtId, $date) {
            return $this->loadApplicableRules($courtId, $date);
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
}
