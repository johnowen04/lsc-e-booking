<?php

namespace App\Observers;

use App\Models\Court;
use App\Models\PricingRule;
use App\Jobs\RefreshCourtSlotPricesJob;
use App\Jobs\GenerateCourtSlotsJob;
use App\Services\PricingRuleService;
use Illuminate\Support\Carbon;

class PricingRuleObserver
{
    protected PricingRuleService $pricingRuleService;

    public function __construct(PricingRuleService $pricingRuleService)
    {
        $this->pricingRuleService = $pricingRuleService;
    }

    public function saved(PricingRule $rule): void
    {
        $this->handleInvalidateAndJobs($rule);
    }

    public function deleted(PricingRule $rule): void
    {
        $this->handleInvalidateAndJobs($rule);
    }

    protected function handleInvalidateAndJobs(PricingRule $rule): void
    {
        $courtIds = $rule->court_id
            ? [$rule->court_id]
            : Court::isActive()->pluck('id')->toArray();

        // Determine date range to invalidate cache
        $startDate = $rule->start_date
            ? Carbon::parse($rule->start_date)->startOfDay()
            : now()->startOfDay();

        $endDate = $rule->end_date
            ? Carbon::parse($rule->end_date)->endOfDay()
            : now()->copy()->addDays(7)->endOfDay();

        $dateRange = collect();
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateRange->push($date->toDateString());
        }

        foreach ($courtIds as $courtId) {
            foreach ($dateRange as $date) {
                $this->pricingRuleService->clearCachedRules($courtId, $date);
            }

            // Optional: Regenerate court slots
            GenerateCourtSlotsJob::dispatch(
                Court::find($courtId),
                now(),
                now()->copy()->addDays(7)
            );

            // Optional: Recalculate cached prices
            RefreshCourtSlotPricesJob::dispatch(
                courtId: $courtId,
                dayOfWeek: $rule->day_of_week,
                timeStart: $rule->time_start,
                timeEnd: $rule->time_end,
            );
        }
    }
}
