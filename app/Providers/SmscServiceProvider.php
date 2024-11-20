<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\SmscDeliveryReportService;

class SmscServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(SmscDeliveryReportService::class, function ($app) {
            return new SmscDeliveryReportService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}