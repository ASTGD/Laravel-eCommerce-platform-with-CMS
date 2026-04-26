<?php

namespace Platform\CommerceCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Platform\CommerceCore\Services\Affiliates\AffiliateReferralTrackingService;
use Platform\CommerceCore\Services\Affiliates\AffiliateSettingsService;
use Symfony\Component\HttpFoundation\Response;

class CaptureAffiliateReferral
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldCapture($request)) {
            app(AffiliateReferralTrackingService::class)->captureFromRequest($request);
        }

        return $next($request);
    }

    protected function shouldCapture(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($this->isAdminRequest($request)) {
            return false;
        }

        return filled($request->query(app(AffiliateSettingsService::class)->referralParameter()));
    }

    protected function isAdminRequest(Request $request): bool
    {
        $adminPath = trim((string) config('app.admin_url'), '/');

        return $adminPath !== '' && $request->is($adminPath, $adminPath.'/*');
    }
}
