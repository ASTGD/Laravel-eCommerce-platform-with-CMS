<?php

namespace Platform\CommerceCore\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Platform\CommerceCore\DataGrids\Sales\ShipmentRecordDataGrid;
use Platform\CommerceCore\Http\Requests\Admin\ShipmentBookingReferenceRequest;
use Platform\CommerceCore\Http\Requests\Admin\ShipmentCarrierBookingRequest;
use Platform\CommerceCore\Http\Requests\Admin\ShipmentDeliveryFailureRequest;
use Platform\CommerceCore\Http\Requests\Admin\ShipmentRecordEventRequest;
use Platform\CommerceCore\Http\Requests\Admin\ShipmentRecordStatusRequest;
use Platform\CommerceCore\Http\Requests\Admin\ShipmentReattemptRequest;
use Platform\CommerceCore\Http\Requests\Admin\ShipmentReturnOperationRequest;
use Platform\CommerceCore\Models\ShipmentEvent;
use Platform\CommerceCore\Models\ShipmentRecord;
use Platform\CommerceCore\Services\CarrierBookingService;
use Platform\CommerceCore\Services\CarrierTrackingSyncService;
use Platform\CommerceCore\Services\ShipmentRecordService;
use Platform\CommerceCore\Support\CarrierBookingProviderRegistry;

class ShipmentRecordController extends Controller
{
    public function __construct(
        protected ShipmentRecordService $shipmentRecordService,
        protected CarrierTrackingSyncService $carrierTrackingSyncService,
        protected CarrierBookingService $carrierBookingService,
        protected CarrierBookingProviderRegistry $carrierBookingProviderRegistry,
    ) {}

    public function index()
    {
        if (request()->ajax()) {
            return datagrid(ShipmentRecordDataGrid::class)->process();
        }

        return view('commerce-core::admin.shipment-records.index');
    }

    public function show(ShipmentRecord $shipmentRecord): View
    {
        $shipmentRecord->load([
            'order',
            'carrier',
            'nativeShipment',
            'inventorySource',
            'items.orderItem',
            'events.actor',
            'communications.shipmentEvent',
        ]);

        return view('commerce-core::admin.shipment-records.show', [
            'shipmentRecord' => $shipmentRecord,
            'statusOptions' => ShipmentRecord::statusLabels(),
            'failureReasonOptions' => ShipmentRecord::failureReasonLabels(),
            'eventTypeOptions' => ShipmentEvent::manualEventLabels(),
            'canCreateCarrierBooking' => $shipmentRecord->carrier
                ? $this->carrierBookingProviderRegistry->supportsCarrier($shipmentRecord->carrier)
                : false,
        ]);
    }

    public function updateStatus(ShipmentRecordStatusRequest $request, ShipmentRecord $shipmentRecord): RedirectResponse
    {
        $this->shipmentRecordService->updateStatus(
            $shipmentRecord,
            $request->string('status')->value(),
            $request->string('note')->value() ?: null,
            auth('admin')->id(),
        );

        return redirect()
            ->route('admin.sales.shipment-operations.view', $shipmentRecord)
            ->with('success', 'Shipment status updated.');
    }

    public function storeEvent(ShipmentRecordEventRequest $request, ShipmentRecord $shipmentRecord): RedirectResponse
    {
        $this->shipmentRecordService->appendEvent(
            $shipmentRecord,
            $request->string('event_type')->value(),
            $request->string('note')->value() ?: null,
            auth('admin')->id(),
            $request->string('status_after_event')->value() ?: null,
        );

        return redirect()
            ->route('admin.sales.shipment-operations.view', $shipmentRecord)
            ->with('success', 'Shipment event recorded.');
    }

    public function recordDeliveryFailure(ShipmentDeliveryFailureRequest $request, ShipmentRecord $shipmentRecord): RedirectResponse
    {
        $this->shipmentRecordService->recordDeliveryFailure(
            $shipmentRecord,
            $request->string('failure_reason')->value(),
            $request->string('note')->value() ?: null,
            $request->boolean('requires_reattempt'),
            auth('admin')->id(),
        );

        return redirect()
            ->route('admin.sales.shipment-operations.view', $shipmentRecord)
            ->with('success', 'Delivery failure recorded.');
    }

    public function approveReattempt(ShipmentReattemptRequest $request, ShipmentRecord $shipmentRecord): RedirectResponse
    {
        $this->shipmentRecordService->approveReattempt(
            $shipmentRecord,
            $request->string('note')->value() ?: null,
            auth('admin')->id(),
        );

        return redirect()
            ->route('admin.sales.shipment-operations.view', $shipmentRecord)
            ->with('success', 'Reattempt approved.');
    }

    public function initiateReturn(ShipmentReturnOperationRequest $request, ShipmentRecord $shipmentRecord): RedirectResponse
    {
        $this->shipmentRecordService->initiateReturn(
            $shipmentRecord,
            $request->string('note')->value() ?: null,
            auth('admin')->id(),
        );

        return redirect()
            ->route('admin.sales.shipment-operations.view', $shipmentRecord)
            ->with('success', 'Return initiated.');
    }

    public function completeReturn(ShipmentReturnOperationRequest $request, ShipmentRecord $shipmentRecord): RedirectResponse
    {
        $this->shipmentRecordService->completeReturn(
            $shipmentRecord,
            $request->string('note')->value() ?: null,
            auth('admin')->id(),
        );

        return redirect()
            ->route('admin.sales.shipment-operations.view', $shipmentRecord)
            ->with('success', 'Return completed.');
    }

    public function syncTracking(ShipmentRecord $shipmentRecord): RedirectResponse
    {
        $result = $this->carrierTrackingSyncService->syncShipmentRecord($shipmentRecord);

        return redirect()
            ->route('admin.sales.shipment-operations.view', $shipmentRecord)
            ->with($result->status === 'failed' ? 'error' : 'success', $result->message);
    }

    public function updateBookingReferences(ShipmentBookingReferenceRequest $request, ShipmentRecord $shipmentRecord): RedirectResponse
    {
        $this->shipmentRecordService->updateBookingReferences(
            $shipmentRecord,
            $request->payload(),
            $request->string('note')->value() ?: null,
            auth('admin')->id(),
        );

        return redirect()
            ->route('admin.sales.shipment-operations.view', $shipmentRecord)
            ->with('success', 'Carrier booking references updated.');
    }

    public function createCarrierBooking(ShipmentCarrierBookingRequest $request, ShipmentRecord $shipmentRecord): RedirectResponse
    {
        $result = $this->carrierBookingService->bookShipmentRecord($shipmentRecord, [
            'actor_admin_id' => auth('admin')->id(),
            'note' => $request->string('note')->value() ?: null,
        ]);

        $flashKey = $result->status === 'booked' ? 'success' : ($result->status === 'skipped' ? 'warning' : 'error');

        return redirect()
            ->route('admin.sales.shipment-operations.view', $shipmentRecord)
            ->with($flashKey, $result->message);
    }
}
