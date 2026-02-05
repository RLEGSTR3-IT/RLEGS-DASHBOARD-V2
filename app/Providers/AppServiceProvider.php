<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\CcRevenue;
use App\Observers\CcRevenueObserver;

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
        // ✅ Register CcRevenue Observer for auto-recalculate AM Revenue
        CcRevenue::observe(CcRevenueObserver::class);
    }
}