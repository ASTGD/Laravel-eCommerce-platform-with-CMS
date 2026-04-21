<?php

namespace Platform\CommerceCore\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Platform\CommerceCore\Services\ManualToShipService;

class ManualToShipController extends Controller
{
    public function __construct(
        protected ManualToShipService $manualToShipService,
    ) {}

    public function index(): View
    {
        return view('commerce-core::admin.manual-to-ship.index', [
            'orders' => $this->manualToShipService->paginateOrders(),
            'shipmentCarriers' => $this->manualToShipService->activeCarriers(),
        ]);
    }
}
