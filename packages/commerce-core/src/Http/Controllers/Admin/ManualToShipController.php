<?php

namespace Platform\CommerceCore\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Platform\CommerceCore\Models\ShipmentHandoverBatch;
use Platform\CommerceCore\Models\ShipmentRecord;
use Platform\CommerceCore\Services\ManualShipmentHandoverService;
use Platform\CommerceCore\Services\ManualToShipService;
use Webkul\Core\Traits\PDFHandler;
use Webkul\Sales\Models\Order;

class ManualToShipController extends Controller
{
    use PDFHandler;

    public function __construct(
        protected ManualToShipService $manualToShipService,
        protected ManualShipmentHandoverService $manualShipmentHandoverService,
    ) {}

    public function index(Request $request): View
    {
        $needsSearch = trim((string) $request->input('needs_search')) ?: null;
        $readySearch = trim((string) $request->input('ready_search')) ?: null;
        $needsPerPage = $this->resolvePerPage((int) $request->input('needs_per_page', 10));
        $readyPerPage = $this->resolvePerPage((int) $request->input('ready_per_page', 10));

        return view('commerce-core::admin.manual-to-ship.index', [
            'queueCounts' => $this->manualToShipService->queueCounts(),
            'needsBookingOrders' => $this->manualToShipService->paginateNeedsBookingOrders(
                perPage: $needsPerPage,
                search: $needsSearch,
                pageName: 'needs_page',
            ),
            'readyShipments' => $this->manualToShipService->paginateReadyShipments(
                perPage: $readyPerPage,
                search: $readySearch,
                carrierId: $request->filled('ready_carrier_id') ? (int) $request->input('ready_carrier_id') : null,
                preparedDate: trim((string) $request->input('ready_prepared_date')) ?: null,
                handoverMode: trim((string) $request->input('ready_handover_mode')) ?: null,
                pageName: 'ready_page',
            ),
            'shipmentCarriers' => $this->manualToShipService->activeCarriers(),
            'readyCarriers' => $this->manualToShipService->readyCarrierOptions(),
            'handoverModes' => ShipmentRecord::handoverModeLabels(),
            'batchHandoverTypes' => ShipmentHandoverBatch::typeLabels(),
            'currentAdminName' => auth('admin')->user()?->name ?: 'Current Admin',
        ]);
    }

    public function printDocuments(Request $request, Order $order, string $document): View|JsonResponse
    {
        abort_unless(in_array($document, ['label', 'invoice', 'both'], true), 404);

        $validator = Validator::make(
            $request->all(),
            $this->manualToShipService->bookingValidationRules(),
            $this->manualToShipService->bookingValidationMessages(),
            $this->manualToShipService->bookingValidationAttributes(),
        );

        if ($validator->fails()) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Complete the required booking fields before opening print preview.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validator->validate();
        }

        $validated = $validator->validated();

        return view('commerce-core::admin.manual-to-ship.print-documents', [
            'printData' => $this->manualToShipService->printableBookingData(
                $order,
                $validated['shipment'],
                $document,
            ),
        ]);
    }

    public function createHandoverBatch(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $this->validateHandoverPayload($request);

        $batch = $this->manualShipmentHandoverService->createDraftBatch(
            $validated['shipment_record_ids'],
            $validated,
            auth('admin')->id(),
        );

        $previewUrl = route('admin.sales.to-ship.handover-sheet.preview', $batch);
        $downloadUrl = route('admin.sales.to-ship.handover-sheet.pdf', $batch);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'message' => sprintf('Handover sheet %s is ready.', $batch->reference),
                'preview_url' => $previewUrl,
                'download_url' => $downloadUrl,
                'batch' => [
                    'id' => $batch->id,
                    'reference' => $batch->reference,
                    'shipment_record_ids' => $batch->shipments->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                    'shipment_count' => $batch->shipments->count(),
                    'parcel_count' => (int) $batch->parcel_count,
                    'total_cod_amount' => (float) $batch->total_cod_amount,
                    'carrier_name' => $batch->carrier?->name ?: 'Manual Courier',
                ],
            ]);
        }

        return redirect()->to($previewUrl);
    }

    public function printManifest(Request $request): RedirectResponse|JsonResponse
    {
        return $this->createHandoverBatch($request);
    }

    public function showHandoverSheet(ShipmentHandoverBatch $handoverBatch): View
    {
        return view('commerce-core::admin.manual-to-ship.handover-sheet-preview', [
            'merchant' => $this->manualToShipService->merchantDetails(),
            'handoverSheet' => $this->manualShipmentHandoverService->handoverSheetPreview($handoverBatch),
            'downloadUrl' => route('admin.sales.to-ship.handover-sheet.pdf', $handoverBatch),
        ]);
    }

    public function downloadHandoverSheetPdf(ShipmentHandoverBatch $handoverBatch)
    {
        return $this->downloadPDF(
            view('commerce-core::admin.manual-to-ship.handover-sheet-pdf', [
                'merchant' => $this->manualToShipService->merchantDetails(),
                'handoverSheet' => $this->manualShipmentHandoverService->handoverSheetPreview($handoverBatch),
            ])->render(),
            'handover-sheet-'.$handoverBatch->reference
        );
    }

    public function confirmHandover(Request $request): RedirectResponse
    {
        $validated = $this->validateConfirmPayload($request);

        $batch = $this->manualShipmentHandoverService->confirmPreparedBatch(
            $validated['shipment_record_ids'],
            auth('admin')->id(),
        );

        return redirect()
            ->route('admin.sales.shipped-orders.index')
            ->with('success', sprintf('Handover confirmed in batch %s. Selected parcels are now in In Delivery.', $batch->reference));
    }

    protected function validateHandoverPayload(Request $request): array
    {
        return $request->validate([
            'shipment_record_ids' => ['required', 'array', 'min:1'],
            'shipment_record_ids.*' => ['required', 'integer'],
            'handover_type' => ['required', Rule::in(array_keys(ShipmentHandoverBatch::typeLabels()))],
            'handover_at' => ['required', 'date'],
            'receiver_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'shipment_record_ids.required' => 'Select at least one parcel from Parcel Ready for Handover.',
            'handover_type.required' => 'Choose how these parcels are being handed over.',
            'handover_at.required' => 'Choose the handover date and time.',
        ], [
            'shipment_record_ids' => 'selected parcels',
            'handover_type' => 'handover type',
            'handover_at' => 'handover date and time',
            'receiver_name' => 'receiver or driver name',
            'notes' => 'handover notes',
        ]);
    }

    protected function validateConfirmPayload(Request $request): array
    {
        return $request->validate([
            'shipment_record_ids' => ['required', 'array', 'min:1'],
            'shipment_record_ids.*' => ['required', 'integer'],
        ], [
            'shipment_record_ids.required' => 'Select at least one parcel from Parcel Ready for Handover.',
        ], [
            'shipment_record_ids' => 'selected parcels',
        ]);
    }

    protected function resolvePerPage(int $perPage): int
    {
        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
    }
}
