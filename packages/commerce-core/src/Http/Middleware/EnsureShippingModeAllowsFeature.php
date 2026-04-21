<?php

namespace Platform\CommerceCore\Http\Middleware;

use Closure;
use Platform\CommerceCore\Support\ShippingMode;

class EnsureShippingModeAllowsFeature
{
    public function __construct(
        protected ShippingMode $shippingMode,
    ) {}

    public function handle($request, Closure $next, string $requiredMode = ShippingMode::ADVANCED_PRO)
    {
        if ($requiredMode === ShippingMode::ADVANCED_PRO && ! $this->shippingMode->usesAdvancedPro()) {
            abort(403, 'This shipping workflow is only available in Advanced Pro mode.');
        }

        if ($requiredMode === ShippingMode::MANUAL_BASIC && ! $this->shippingMode->usesManualBasic()) {
            abort(403, 'This shipping workflow is only available in Manual Basic mode.');
        }

        return $next($request);
    }
}
