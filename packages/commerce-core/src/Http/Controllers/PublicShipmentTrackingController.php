<?php

namespace Platform\CommerceCore\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Platform\CommerceCore\Http\Requests\PublicShipmentTrackingRequest;
use Platform\CommerceCore\Services\CustomerShipmentTrackingService;

class PublicShipmentTrackingController extends Controller
{
    public function __construct(protected CustomerShipmentTrackingService $trackingService) {}

    public function index(): View
    {
        return view('commerce-core::shop.shipment-tracking.index', [
            'trackingResult' => null,
            'lookupAttempted' => false,
            'lookupError' => null,
            'lookupInput' => [
                'reference' => request()->query('reference'),
                'phone' => null,
            ],
        ]);
    }

    public function lookup(PublicShipmentTrackingRequest $request): View
    {
        $trackingResult = $this->trackingService->lookupPublic(
            $request->string('reference')->value(),
            $request->string('phone')->value(),
        );

        return view('commerce-core::shop.shipment-tracking.index', [
            'trackingResult' => $trackingResult,
            'lookupAttempted' => true,
            'lookupError' => $trackingResult ? null : 'No shipment matched that reference and phone number.',
            'lookupInput' => [
                'reference' => $request->string('reference')->value(),
                'phone' => $request->string('phone')->value(),
            ],
        ]);
    }
}
