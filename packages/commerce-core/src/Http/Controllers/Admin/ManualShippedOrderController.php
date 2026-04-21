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

        $shipmentRecordsQuery = ShipmentRecord::query()
            ->with(['order', 'carrier', 'codSettlement'])
            ->whereNotIn('status', [
                ShipmentRecord::STATUS_DRAFT,
                ShipmentRecord::STATUS_DELIVERED,
                ShipmentRecord::STATUS_RETURNED,
                ShipmentRecord::STATUS_CANCELED,
            ]);

        if ($carrierId) {
            $shipmentRecordsQuery->where('shipment_carrier_id', $carrierId);
        }

        $shipmentRecords = $shipmentRecordsQuery
            ->orderByDesc('handed_over_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $carriers = ShipmentCarrier::query()
            ->select('shipment_carriers.id', 'shipment_carriers.name')
            ->join('shipment_records', 'shipment_records.shipment_carrier_id', '=', 'shipment_carriers.id')
            ->whereNotIn('shipment_records.status', [
                ShipmentRecord::STATUS_DRAFT,
                ShipmentRecord::STATUS_DELIVERED,
                ShipmentRecord::STATUS_RETURNED,
                ShipmentRecord::STATUS_CANCELED,
            ])
            ->distinct()
            ->orderBy('shipment_carriers.name')
            ->get();

        return view('commerce-core::admin.manual-shipped-orders.index', [
            'shipmentRecords' => $shipmentRecords,
            'carriers'        => $carriers,
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
