<?php

namespace Platform\CommerceCore\Http\Middleware;

use Closure;
use Platform\CommerceCore\Support\ShippingMode;

class RedirectBasicShipmentBrowseRoutes
{
    public function __construct(
        protected ShippingMode $shippingMode,
    ) {}

    public function handle($request, Closure $next)
    {
        if (! $this->shippingMode->usesManualBasic()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if (in_array($routeName, [
            'admin.sales.shipments.index',
            'admin.sales.shipments.view',
        ], true)) {
            return redirect()
                ->route('admin.sales.to-ship.index')
                ->with('warning', 'Manual Basic mode uses To Ship, In Delivery, and COD Receivables instead of the native shipment screens.');
        }

        return $next($request);
    }
}
