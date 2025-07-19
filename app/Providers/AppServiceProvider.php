<?php

namespace App\Providers;

use App\Models\Court;
use App\Models\PricingRule;
use App\Observers\CourtObserver;
use App\Observers\PricingRuleObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // if (app()->environment('production')) {
        //     URL::forceHttps();
        // }

        URL::forceScheme('https');

        Court::observe(CourtObserver::class);
        PricingRule::observe(PricingRuleObserver::class);
    }
}
