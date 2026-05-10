<?php

namespace Webkul\Shop\CacheProfiles;

use Illuminate\Http\Request;
use Spatie\ResponseCache\CacheProfiles\CacheAllSuccessfulGetRequests;
use Symfony\Component\HttpFoundation\Response;

class SafeStorefrontCacheProfile extends CacheAllSuccessfulGetRequests
{
    public function enabled(Request $request): bool
    {
        return parent::enabled($request)
            && ! $this->hasAuthenticatedUser()
            && ! $this->isProtectedRequest($request);
    }

    public function shouldCacheRequest(Request $request): bool
    {
        return parent::shouldCacheRequest($request)
            && ! $this->hasAuthenticatedUser()
            && ! $this->isProtectedRequest($request);
    }

    public function shouldCacheResponse(Response $response): bool
    {
        if (! parent::shouldCacheResponse($response)) {
            return false;
        }

        return ! str_contains(strtolower($response->headers->get('Cache-Control', '')), 'no-store');
    }

    protected function hasAuthenticatedUser(): bool
    {
        return auth()->guard('admin')->check()
            || auth()->guard('customer')->check();
    }

    protected function isProtectedRequest(Request $request): bool
    {
        $adminPath = trim((string) config('app.admin_url', 'admin'), '/');

        if ($adminPath !== '' && $request->is($adminPath, $adminPath.'/*')) {
            return true;
        }

        if ($request->is(
            'customer',
            'customer/*',
            'checkout',
            'checkout/*',
            'api/checkout',
            'api/checkout/*',
            'payment/*',
            'webhooks/*',
            'shipment-tracking',
            'shipment-tracking/*',
        )) {
            return true;
        }

        $routeName = (string) ($request->route()?->getName() ?? '');

        foreach ([
            'admin.',
            'shop.customer.',
            'shop.customers.',
            'shop.checkout.',
            'commerce-core.',
        ] as $protectedPrefix) {
            if (str_starts_with($routeName, $protectedPrefix)) {
                return true;
            }
        }

        return false;
    }
}
