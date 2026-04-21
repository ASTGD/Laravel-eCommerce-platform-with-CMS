<?php

namespace Platform\CommerceCore\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Platform\CommerceCore\Models\ShipmentRecord;
use Platform\CommerceCore\Services\ShipmentRecordService;

class ManualShippedOrderController extends Controller
{
    public function __construct(
        protected ShipmentRecordService $shipmentRecordService,
    ) {}

    public function index(): View
    {
        $shipmentRecords = ShipmentRecord::query()
            ->with(['order', 'carrier', 'codSettlement'])
            ->whereNotIn('status', [
                ShipmentRecord::STATUS_DRAFT,
                ShipmentRecord::STATUS_CANCELED,
            ])
            ->orderByRaw('CASE WHEN delivered_at IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('handed_over_at')
            ->orderByDesc('id')
            ->paginate(15);

        return view('commerce-core::admin.manual-shipped-orders.index', [
            'shipmentRecords' => $shipmentRecords,
        ]);
    }

    public function markDelivered(ShipmentRecord $shipmentRecord): RedirectResponse
    {
        if (! $shipmentRecord->canBeMarkedDelivered()) {
            return redirect()
                ->route('admin.sales.shipped-orders.index')
                ->with('warning', 'This shipment is already closed and cannot be marked delivered again.');
        }

        $this->shipmentRecordService->updateStatus(
            $shipmentRecord,
            ShipmentRecord::STATUS_DELIVERED,
            'Marked delivered from the Shipped Orders page.',
            auth('admin')->id(),
        );

        return redirect()
            ->route('admin.sales.shipped-orders.index')
            ->with('success', 'Shipment marked as delivered.');
    }
}
