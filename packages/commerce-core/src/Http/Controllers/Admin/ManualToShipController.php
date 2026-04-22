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
        $search = trim((string) request('search')) ?: null;
        $perPage = (int) request('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        return view('commerce-core::admin.manual-to-ship.index', [
            'orders' => $this->manualToShipService->paginateOrders($perPage, $search),
            'shipmentCarriers' => $this->manualToShipService->activeCarriers(),
        ]);
    }
}
