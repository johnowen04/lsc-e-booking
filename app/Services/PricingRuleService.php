<?php

namespace App\Services;

use App\Models\PricingRule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PricingRuleService
{
    protected static array $cache = [];
    protected static int $cacheSize = 100;

    public function hasAnyPricingRule(): bool
    {
        return PricingRule::get()->isNotEmpty();
    }

    public function getPricingRuleForHour(int $courtId, string $date, Carbon $hour): Model
    {
        $rules = $this->getCachedRules($courtId, $date);

        $rule = $rules->first(function ($rule) use ($hour) {
            $start = Carbon::createFromTimeString($rule->time_start)->format('H:i:s');
            $end = Carbon::createFromTimeString($rule->time_end)->format('H:i:s');

            if ($start === '00:00:00' && $end === '00:00:00') {
                return true;
            }

            return $start < $end
                ? ($hour >= $start && $hour < $end)
                : ($hour >= $start || $hour < $end);
        });

        if (!$rule) {
            throw new \RuntimeException(
                "❌ No pricing rule matched for court_id={$courtId}, date={$date}, hour={$hour->toTimeString()}"
            );
        }

        return $rule;
    }

    public function getPriceForHour(int $courtId, string $date, Carbon $hour): float|int
    {
        return $this->getPricingRuleForHour($courtId, $date, $hour)->price_per_hour ?? 0;
    }

    protected function getCachedRules(int $courtId, string $date): Collection
    {
        $key = "{$courtId}_{$date}";

        if (isset(self::$cache[$key])) {
            $rules = self::$cache[$key];
            unset(self::$cache[$key]);
            self::$cache[$key] = $rules;
            return $rules;
        }

        $rules = $this->loadApplicableRules($courtId, $date);
        self::$cache[$key] = $rules;

        if (count(self::$cache) > self::$cacheSize) {
            array_shift(self::$cache);
        }

        return $rules;
    }

    protected function loadApplicableRules(int $courtId, string $date): Collection
    {
        $carbonDate = Carbon::parse($date);
        $dayOfWeek = $carbonDate->dayOfWeekIso;

        return PricingRule::query()
            ->where('is_active', true)
            ->where(function ($query) use ($courtId) {
                $query->whereNull('court_id')->orWhere('court_id', $courtId);
            })
            ->where(function ($query) use ($dayOfWeek) {
                $query->whereNull('day_of_week')->orWhere('day_of_week', $dayOfWeek);
            })
            ->where(function ($query) use ($date) {
                $query->whereNull('start_date')->orWhere('start_date', '<=', $date);
            })
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date')->orWhere('end_date', '>=', $date);
            })
            ->orderByDesc('priority')
            ->get();
    }
}
