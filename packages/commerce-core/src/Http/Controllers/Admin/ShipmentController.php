<?php

namespace Platform\CommerceCore\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Webkul\Admin\Http\Controllers\Sales\ShipmentController as BaseShipmentController;

class ShipmentController extends BaseShipmentController
{
    public function store(int $orderId): RedirectResponse
    {
        $order = $this->orderRepository->findOrFail($orderId);

        if (! $order->canShip()) {
            session()->flash('error', trans('admin::app.sales.shipments.create.order-error'));

            return redirect()->back();
        }

        $this->validate(request(), [
            'shipment.source' => 'required',
            'shipment.items.*.*' => 'required|numeric|min:0',
            'shipment.carrier_id' => [
                'nullable',
                Rule::exists('shipment_carriers', 'id')->where(fn ($query) => $query->where('is_active', 1)),
            ],
            'shipment.public_tracking_url' => 'nullable|url|max:1000',
        ]);

        $data = request()->only(['shipment', 'carrier_name']);
        $carrierId = (int) data_get($data, 'shipment.carrier_id', 0);

        if ($carrierId > 0) {
            $carrier = ShipmentCarrier::query()->active()->find($carrierId);

            if ($carrier) {
                $data['shipment']['carrier_title'] = $carrier->name;
            }
        }

        if (! $this->isInventoryValidate($data)) {
            session()->flash('error', trans('admin::app.sales.shipments.create.quantity-invalid'));

            return redirect()->back();
        }

        $this->shipmentRepository->create(array_merge($data, [
            'order_id' => $orderId,
        ]));

        session()->flash('success', trans('admin::app.sales.shipments.create.success'));

        return redirect()->route('admin.sales.orders.view', $orderId);
    }
}
