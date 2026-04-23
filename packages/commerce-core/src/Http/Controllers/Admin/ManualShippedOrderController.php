<?php

namespace Platform\CommerceCore\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Models\ShipmentRecord;
use Platform\CommerceCore\Services\ShipmentRecordService;

class ManualShippedOrderController extends Controller
{
    public function __construct(
        protected ShipmentRecordService $shipmentRecordService,
    ) {}

    public function index(Request $request): View
    {
        $carrierId = $request->filled('carrier_id')
            ? (int) $request->input('carrier_id')
            : null;
        $search = trim((string) $request->input('search')) ?: null;
        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $shipmentRecordsQuery = ShipmentRecord::query()
            ->with(['order', 'carrier', 'codSettlement'])
            ->whereNotIn('status', [
                ShipmentRecord::STATUS_DRAFT,
                ShipmentRecord::STATUS_READY_FOR_PICKUP,
                ShipmentRecord::STATUS_DELIVERED,
                ShipmentRecord::STATUS_RETURNED,
                ShipmentRecord::STATUS_CANCELED,
            ]);

        if ($carrierId) {
            $shipmentRecordsQuery->where('shipment_carrier_id', $carrierId);
        }

        if ($search) {
            $shipmentRecordsQuery->where(function ($query) use ($search) {
                $query
                    ->where('tracking_number', 'like', '%'.$search.'%')
                    ->orWhere('public_tracking_url', 'like', '%'.$search.'%')
                    ->orWhere('carrier_name_snapshot', 'like', '%'.$search.'%')
                    ->orWhere('recipient_name', 'like', '%'.$search.'%')
                    ->orWhere('recipient_phone', 'like', '%'.$search.'%')
                    ->orWhere('recipient_address', 'like', '%'.$search.'%')
                    ->orWhereHas('order', function ($orderQuery) use ($search) {
                        $orderQuery
                            ->where('increment_id', 'like', '%'.$search.'%')
                            ->orWhere('customer_first_name', 'like', '%'.$search.'%')
                            ->orWhere('customer_last_name', 'like', '%'.$search.'%')
                            ->orWhere('customer_email', 'like', '%'.$search.'%');
                    });
            });
        }

        $shipmentRecords = $shipmentRecordsQuery
            ->orderByDesc('handed_over_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $carriers = ShipmentCarrier::query()
            ->select('shipment_carriers.id', 'shipment_carriers.name')
            ->join('shipment_records', 'shipment_records.shipment_carrier_id', '=', 'shipment_carriers.id')
            ->whereNotIn('shipment_records.status', [
                ShipmentRecord::STATUS_DRAFT,
                ShipmentRecord::STATUS_READY_FOR_PICKUP,
                ShipmentRecord::STATUS_DELIVERED,
                ShipmentRecord::STATUS_RETURNED,
                ShipmentRecord::STATUS_CANCELED,
            ])
            ->distinct()
            ->orderBy('shipment_carriers.name')
            ->get();

        return view('commerce-core::admin.manual-shipped-orders.index', [
            'shipmentRecords' => $shipmentRecords,
            'carriers'          => $carriers,
            'selectedCarrierId' => $carrierId,
        ]);
    }

    public function markDelivered(ShipmentRecord $shipmentRecord): RedirectResponse
    {
        if (! $shipmentRecord->canBeMarkedDelivered()) {
            return redirect()
                ->route('admin.sales.shipped-orders.index')
                ->with('warning', 'This delivery is already closed.');
        }

        $this->shipmentRecordService->updateStatus(
            $shipmentRecord,
            ShipmentRecord::STATUS_DELIVERED,
            'Marked delivered from the In Delivery page.',
            auth('admin')->id(),
        );

        return redirect()
            ->route('admin.sales.shipped-orders.index')
            ->with('success', 'Delivery marked. If this was a cash on delivery order, it now appears in COD Receivables as collected by the courier.');
    }
}
