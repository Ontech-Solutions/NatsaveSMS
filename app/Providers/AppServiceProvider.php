<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

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
    public function boot()
    {
        // Global API rate limiting
        RateLimiter::for('api', function (Request $request) {
            return [
                // Limit requests per minute
                Limit::perMinute(100)->by($request->header('X-API-Key')),
                
                // Limit SMS per day
                Limit::perDay(10000)
                    ->by($request->header('X-API-Key'))
                    ->response(function () {
                        return response()->json([
                            'success' => false,
                            'error' => [
                                'code' => 'DAILY_LIMIT_EXCEEDED',
                                'message' => 'Daily SMS limit exceeded'
                            ]
                        ], 429);
                    }),
            ];
        });
    }
}
