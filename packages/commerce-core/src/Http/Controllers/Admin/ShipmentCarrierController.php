<?php

namespace Platform\CommerceCore\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Platform\CommerceCore\DataGrids\Sales\ShipmentCarrierDataGrid;
use Platform\CommerceCore\Http\Requests\Admin\ShipmentCarrierRequest;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Repositories\ShipmentCarrierRepository;
use Platform\CommerceCore\Support\CarrierTrackingProviderRegistry;

class ShipmentCarrierController extends Controller
{
    public function __construct(
        protected ShipmentCarrierRepository $shipmentCarrierRepository,
        protected CarrierTrackingProviderRegistry $carrierTrackingProviderRegistry,
    ) {}

    public function index()
    {
        if (request()->ajax()) {
            return datagrid(ShipmentCarrierDataGrid::class)->process();
        }

        return view('commerce-core::admin.carriers.index');
    }

    public function create(): View
    {
        return view('commerce-core::admin.carriers.form', [
            'carrier' => new ShipmentCarrier([
                'supports_cod' => true,
                'default_cod_fee_type' => 'flat',
                'default_payout_method' => 'bank_transfer',
                'integration_driver' => ShipmentCarrier::INTEGRATION_DRIVER_MANUAL,
                'is_active' => true,
            ]),
            'codFeeTypes' => ShipmentCarrierRequest::COD_FEE_TYPES,
            'integrationDrivers' => $this->carrierTrackingProviderRegistry->driverLabels(),
            'payoutMethods' => ShipmentCarrierRequest::PAYOUT_METHODS,
        ]);
    }

    public function store(ShipmentCarrierRequest $request): RedirectResponse
    {
        $carrier = $this->shipmentCarrierRepository->create($request->payload());

        return redirect()
            ->route('admin.sales.carriers.edit', $carrier)
            ->with('success', 'Carrier created.');
    }

    public function edit(ShipmentCarrier $carrier): View
    {
        return view('commerce-core::admin.carriers.form', [
            'carrier' => $carrier,
            'codFeeTypes' => ShipmentCarrierRequest::COD_FEE_TYPES,
            'integrationDrivers' => $this->carrierTrackingProviderRegistry->driverLabels(),
            'payoutMethods' => ShipmentCarrierRequest::PAYOUT_METHODS,
        ]);
    }

    public function update(ShipmentCarrierRequest $request, ShipmentCarrier $carrier): RedirectResponse
    {
        $this->shipmentCarrierRepository->update($request->payload(), $carrier->id);

        return redirect()
            ->route('admin.sales.carriers.edit', $carrier)
            ->with('success', 'Carrier updated.');
    }

    public function destroy(ShipmentCarrier $carrier): JsonResponse|RedirectResponse
    {
        $this->shipmentCarrierRepository->delete($carrier->id);

        if (request()->ajax()) {
            return new JsonResponse([
                'message' => 'Carrier deleted.',
            ]);
        }

        return redirect()
            ->route('admin.sales.carriers.index')
            ->with('success', 'Carrier deleted.');
    }
}
