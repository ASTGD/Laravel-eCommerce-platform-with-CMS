<?php

namespace Platform\CommerceCore\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Services\ManualToShipService;
use Webkul\Admin\Http\Controllers\Sales\ShipmentController as BaseShipmentController;
use Webkul\Sales\Repositories\OrderItemRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\ShipmentRepository;

class ShipmentController extends BaseShipmentController
{
    public function __construct(
        OrderRepository $orderRepository,
        OrderItemRepository $orderItemRepository,
        ShipmentRepository $shipmentRepository,
        protected ManualToShipService $manualToShipService,
    ) {
        parent::__construct(
            $orderRepository,
            $orderItemRepository,
            $shipmentRepository
        );
    }

    public function store(int $orderId): RedirectResponse
    {
        $order = $this->orderRepository->findOrFail($orderId);
        $isManualToShipRequest = $this->isManualToShipRequest();

        if (! $order->canShip()) {
            session()->flash(
                'error',
                $isManualToShipRequest
                    ? 'This order is no longer available in To Ship.'
                    : trans('admin::app.sales.shipments.create.order-error')
            );

            return $this->redirectBackForShipmentFailure();
        }

        $carrierRules = [
            'nullable',
            Rule::exists('shipment_carriers', 'id')->where(fn ($query) => $query->where('is_active', 1)),
        ];

        if ($isManualToShipRequest) {
            array_unshift($carrierRules, 'required');
        }

        $rules = [
            'shipment.source' => 'required',
            'shipment.items.*.*' => 'required|numeric|min:0',
            'shipment.carrier_id' => $carrierRules,
            'shipment.track_number' => [$isManualToShipRequest ? 'required' : 'nullable', 'string', 'max:255'],
            'shipment.public_tracking_url' => 'nullable|url|max:1000',
            'shipment.note' => 'nullable|string|max:1000',
        ];

        $messages = [
            'shipment.carrier_id.required' => 'Select a courier before booking this shipment.',
            'shipment.track_number.required' => 'Enter the courier tracking number before saving this shipment.',
            'shipment.public_tracking_url.url' => 'Enter a valid tracking URL or leave it blank.',
            'shipment.note.max' => 'The booking note must be 1000 characters or fewer.',
        ];

        $attributes = [
            'shipment.carrier_id' => 'courier',
            'shipment.track_number' => 'tracking number',
            'shipment.public_tracking_url' => 'tracking URL',
            'shipment.note' => 'note',
        ];

        if ($isManualToShipRequest) {
            $rules = array_merge($rules, $this->manualToShipService->bookingValidationRules());
            $messages = array_merge($messages, $this->manualToShipService->bookingValidationMessages());
            $attributes = array_merge($attributes, $this->manualToShipService->bookingValidationAttributes());
        }

        $this->validate(request(), $rules, $messages, $attributes);

        $data = request()->only(['shipment', 'carrier_name']);
        $carrierId = (int) data_get($data, 'shipment.carrier_id', 0);

        if ($carrierId > 0) {
            $carrier = ShipmentCarrier::query()->active()->find($carrierId);

            if ($carrier) {
                $data['shipment']['carrier_title'] = $carrier->name;
            }
        }

        if (! $this->isInventoryValidate($data)) {
            session()->flash(
                'error',
                $isManualToShipRequest
                    ? 'Review stock before booking. One or more items do not have enough quantity in the selected stock source.'
                    : trans('admin::app.sales.shipments.create.quantity-invalid')
            );

            return $this->redirectBackForShipmentFailure();
        }

        $this->shipmentRepository->create(array_merge($data, [
            'order_id' => $orderId,
        ]));

        session()->flash(
            'success',
            $isManualToShipRequest
                ? 'Parcel booked and moved to Parcel Ready for Handover.'
                : trans('admin::app.sales.shipments.create.success')
        );

        return $this->redirectAfterShipmentStore($orderId);
    }

    protected function isManualToShipRequest(): bool
    {
        return request()->input('redirect_to') === 'to_ship';
    }

    protected function redirectAfterShipmentStore(int $orderId): RedirectResponse
    {
        if ($this->isManualToShipRequest()) {
            return redirect()->to(route('admin.sales.to-ship.index').'#parcel-ready-for-handover');
        }

        return redirect()->route('admin.sales.orders.view', $orderId);
    }

    protected function redirectBackForShipmentFailure(): RedirectResponse
    {
        $redirect = redirect()->back();

        if ($this->isManualToShipRequest()) {
            return $redirect->withInput();
        }

        return $redirect;
    }
}
