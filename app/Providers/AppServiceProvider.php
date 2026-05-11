<?php

namespace App\Providers;

use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Request as RequestFacade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (! class_exists(Debugbar::class)) {
            return;
        }

        if (! config('app.debug') || app()->environment('production')) {
            Debugbar::disable();

            return;
        }

        $allowedIPs = array_map('trim', explode(',', config('app.debug_allowed_ips')));

        $allowedIPs = array_filter($allowedIPs);

        if (empty($allowedIPs)) {
            return;
        }

        if (in_array(RequestFacade::ip(), $allowedIPs)) {
            Debugbar::enable();
        } else {
            Debugbar::disable();
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Schema::defaultStringLength(191);

        $this->registerSecurityRateLimiters();

        ParallelTesting::setUpTestDatabase(function (string $database, int $token) {
            Artisan::call('db:seed');
        });
    }

    protected function registerSecurityRateLimiters(): void
    {
        RateLimiter::for('admin-login', fn (Request $request) => Limit::perMinute(5)->by($this->emailIpKey($request)));
        RateLimiter::for('customer-login', fn (Request $request) => Limit::perMinute(5)->by($this->emailIpKey($request)));
        RateLimiter::for('customer-register', fn (Request $request) => Limit::perMinute(5)->by($this->emailIpKey($request)));
        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(5)->by($this->emailIpKey($request)));
        RateLimiter::for('checkout-order', fn (Request $request) => Limit::perMinute(10)->by($this->actorIpKey($request)));
        RateLimiter::for('cart-mutation', fn (Request $request) => Limit::perMinute(60)->by($this->actorIpKey($request)));
        RateLimiter::for('review-submit', fn (Request $request) => Limit::perMinute(10)->by($this->actorIpKey($request)));
        RateLimiter::for('shipment-tracking', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('affiliate-action', fn (Request $request) => Limit::perMinute(20)->by($this->actorIpKey($request)));
        RateLimiter::for('customer-address', fn (Request $request) => Limit::perMinute(30)->by($this->actorIpKey($request)));
        RateLimiter::for('payment-callback', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
        RateLimiter::for('courier-webhook', fn (Request $request) => Limit::perMinute(120)->by($request->ip().'|'.$this->routeParameterKey($request, 'carrier')));
    }

    protected function emailIpKey(Request $request): string
    {
        return strtolower((string) $request->input('email', 'guest')).'|'.$request->ip();
    }

    protected function actorIpKey(Request $request): string
    {
        $actorId = auth()->guard('customer')->id()
            ?: auth()->guard('admin')->id()
            ?: 'guest';

        return $actorId.'|'.$request->ip();
    }

    protected function routeParameterKey(Request $request, string $parameter): string
    {
        $value = $request->route($parameter);

        if (is_object($value) && method_exists($value, 'getKey')) {
            return (string) $value->getKey();
        }

        return (string) ($value ?: 'none');
    }
}
