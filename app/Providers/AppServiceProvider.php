<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('admin-login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip().'|'.strtolower((string) $request->input('email')));
        });

        RateLimiter::for('customer-login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip().'|'.strtolower((string) $request->input('tenant_slug')).'|'.(string) $request->input('whatsapp_number'));
        });

        RateLimiter::for('customer-api', function (Request $request) {
            return Limit::perMinute(90)->by($request->user()?->getAuthIdentifier() ?: $request->ip());
        });

        RateLimiter::for('station-api', function (Request $request) {
            return Limit::perMinute(120)->by(sha1($request->bearerToken() ?: $request->ip()));
        });

        RateLimiter::for('station-upload', function (Request $request) {
            return Limit::perMinute(60)->by(sha1($request->bearerToken() ?: $request->ip()));
        });

        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });
    }
}
