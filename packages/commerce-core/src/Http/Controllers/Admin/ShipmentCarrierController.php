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
use Platform\CommerceCore\Support\ShippingMode;

class ShipmentCarrierController extends Controller
{
    public function __construct(
        protected ShipmentCarrierRepository $shipmentCarrierRepository,
        protected ShippingMode $shippingMode,
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
        return view('commerce-core::admin.carriers.form', $this->formViewData(new ShipmentCarrier([
            'address' => null,
            'supports_cod' => true,
            'default_cod_fee_type' => 'flat',
            'default_payout_method' => 'bank_transfer',
            'integration_driver' => ShipmentCarrier::INTEGRATION_DRIVER_MANUAL,
            'is_active' => true,
        ])));
    }

    public function store(ShipmentCarrierRequest $request): RedirectResponse
    {
        $this->shipmentCarrierRepository->create($request->payload());

        return redirect()
            ->route('admin.sales.carriers.create')
            ->with('success', 'Courier service added.');
    }

    public function edit(ShipmentCarrier $carrier): View
    {
        return view('commerce-core::admin.carriers.form', $this->formViewData($carrier));
    }

    public function update(ShipmentCarrierRequest $request, ShipmentCarrier $carrier): RedirectResponse
    {
        $this->shipmentCarrierRepository->update($request->payload(), $carrier->id);

        return redirect()
            ->route('admin.sales.carriers.edit', $carrier)
            ->with('success', 'Courier service updated.');
    }

    public function destroy(ShipmentCarrier $carrier): JsonResponse|RedirectResponse
    {
        $this->shipmentCarrierRepository->delete($carrier->id);

        if (request()->ajax()) {
            return new JsonResponse([
                'message' => 'Courier service deleted.',
            ]);
        }

        return redirect()
            ->route('admin.sales.carriers.index')
            ->with('success', 'Courier service deleted.');
    }

    protected function formViewData(ShipmentCarrier $carrier): array
    {
        $selectedCourierService = old('courier_service', ShipmentCarrierRequest::courierServiceForDriver($carrier->trackingDriver()));
        $legacyDriver = $carrier->exists && $selectedCourierService === ShipmentCarrierRequest::COURIER_SERVICE_MANUAL_OTHER
            ? $carrier->trackingDriver()
            : null;
        $preservedConnectionLabel = null;

        if (in_array($legacyDriver, [
            null,
            '',
            ShipmentCarrier::INTEGRATION_DRIVER_MANUAL,
        ], true)) {
            $legacyDriver = null;
        }

        if ($carrier->exists && ! $this->shippingMode->showsAdvancedCarrierConfiguration()) {
            $preservedConnectionLabel = match ($carrier->trackingDriver()) {
                ShipmentCarrierRequest::COURIER_SERVICE_STEADFAST => 'Steadfast',
                ShipmentCarrierRequest::COURIER_SERVICE_PATHAO => 'Pathao',
                ShipmentCarrier::INTEGRATION_DRIVER_MANUAL, null, '' => null,
                default => str($carrier->trackingDriver())->replace('_', ' ')->title()->value(),
            };
        }

        return [
            'carrier' => $carrier,
            'integrationOptions' => ShipmentCarrierRequest::courierOptions(),
            'selectedCourierService' => $selectedCourierService,
            'legacyDriverLabel' => $legacyDriver ? str($legacyDriver)->replace('_', ' ')->title()->value() : null,
            'preservedConnectionLabel' => $preservedConnectionLabel,
            'codFeeTypes' => ShipmentCarrierRequest::COD_FEE_TYPES,
            'payoutMethods' => ShipmentCarrierRequest::PAYOUT_METHODS,
            'showsAdvancedCarrierConfiguration' => $this->shippingMode->showsAdvancedCarrierConfiguration(),
        ];
    }
}
