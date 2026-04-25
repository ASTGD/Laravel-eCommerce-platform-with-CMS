<?php

namespace Platform\CommerceCore\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Platform\CommerceCore\Models\ShipmentHandoverBatch;
use Platform\CommerceCore\Models\ShipmentRecord;
use Platform\CommerceCore\Services\ManualShipmentHandoverService;
use Platform\CommerceCore\Services\ManualToShipService;
use Webkul\Core\Traits\PDFHandler;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\ShipmentRepository;

class ManualToShipController extends Controller
{
    use PDFHandler;

    public function __construct(
        protected ManualToShipService $manualToShipService,
        protected ManualShipmentHandoverService $manualShipmentHandoverService,
        protected ShipmentRepository $shipmentRepository,
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

    public function saveBookingDraft(Request $request, Order $order): RedirectResponse|JsonResponse
    {
        $validated = $this->validateBookingDraftPayload($request);

        $draft = $this->manualToShipService->saveBookingDraft(
            $order,
            $validated['shipment'],
            auth('admin')->id(),
        );

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'message' => 'Booking draft saved.',
                'draft' => $this->bookingDraftResponse($order, $draft),
            ]);
        }

        return redirect()
            ->to(route('admin.sales.to-ship.index').'#needs-booking')
            ->with('success', 'Booking draft saved. Print the parcel label and invoice before completing booking.');
    }

    public function showBookingDraft(Order $order): JsonResponse
    {
        $draft = $this->manualToShipService->draftForOrder($order);

        return response()->json([
            'draft' => $draft ? $this->bookingDraftResponse($order, $draft) : null,
        ]);
    }

    public function printDraftDocuments(Request $request, Order $order, string $document): View|JsonResponse|Response
    {
        abort_unless(in_array($document, ['label', 'invoice', 'both'], true), 404);

        $validated = $this->validateBookingDraftPayload($request);

        $draft = $this->manualToShipService->saveBookingDraft(
            $order,
            $validated['shipment'],
            auth('admin')->id(),
        );
        $view = view('commerce-core::admin.manual-to-ship.print-documents', [
            'printData' => $this->manualToShipService->printableBookingData(
                $order,
                $this->manualToShipService->draftShipmentData($draft),
                $document,
            ),
        ]);
        $html = $view->render();
        $draft = $this->manualToShipService->markDraftDocumentsGenerated($draft, $document, auth('admin')->id());

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'message' => $document === 'both'
                    ? 'Parcel label and invoice are ready.'
                    : sprintf('%s is ready.', $document === 'label' ? 'Parcel label' : 'Invoice'),
                'html' => $html,
                'draft' => $this->bookingDraftResponse($order, $draft),
            ]);
        }

        return response($html);
    }

    public function showDraftDocument(Request $request, Order $order, string $document): View|JsonResponse|RedirectResponse
    {
        abort_unless(in_array($document, ['label', 'invoice', 'both'], true), 404);

        $draft = $this->manualToShipService->draftForOrder($order);

        if (! $draft || ! $this->manualToShipService->documentIsReady($draft, $document)) {
            $message = 'This document needs to be generated again before it can be opened.';

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                ], 422);
            }

            return redirect()
                ->to(route('admin.sales.to-ship.index').'#needs-booking')
                ->with('warning', $message);
        }

        return view('commerce-core::admin.manual-to-ship.print-documents', [
            'printData' => $this->manualToShipService->printableBookingData(
                $order,
                $this->manualToShipService->draftShipmentData($draft),
                $document,
            ),
        ]);
    }

    public function completeBooking(Request $request, Order $order): RedirectResponse
    {
        $draft = $this->manualToShipService->draftForOrder($order);

        if (! $draft) {
            throw ValidationException::withMessages([
                'shipment' => 'Save a booking draft before completing this parcel.',
            ]);
        }

        $documentStatuses = $this->manualToShipService->documentStatuses($draft);

        if (! $documentStatuses['can_complete']) {
            throw ValidationException::withMessages([
                'documents' => 'Print both the parcel label and invoice again before completing booking.',
            ]);
        }

        if (! $order->canShip()) {
            throw ValidationException::withMessages([
                'shipment' => 'This order is no longer available in To Ship.',
            ]);
        }

        $shipmentPayload = $this->manualToShipService->draftShipmentData($draft);

        DB::transaction(function () use ($order, $draft, $shipmentPayload) {
            request()->merge([
                'redirect_to' => 'to_ship',
                'shipment' => $shipmentPayload,
            ]);

            $this->shipmentRepository->create([
                'shipment' => $shipmentPayload,
                'order_id' => $order->id,
            ]);

            $draft->delete();
        });

        return redirect()
            ->to(route('admin.sales.to-ship.index').'#parcel-ready-for-handover')
            ->with('success', 'Booking completed. Parcel is now ready for courier handover.');
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

    protected function validateBookingDraftPayload(Request $request): array
    {
        $validator = Validator::make(
            $request->all(),
            $this->manualToShipService->bookingValidationRules(),
            $this->manualToShipService->bookingValidationMessages(),
            $this->manualToShipService->bookingValidationAttributes(),
        );

        if ($validator->fails()) {
            if ($request->ajax() || $request->expectsJson()) {
                throw ValidationException::withMessages($validator->errors()->toArray());
            }

            $validator->validate();
        }

        $validated = $validator->validated();
        $validated['shipment']['source'] = $request->input('shipment.source');
        $validated['shipment']['items'] = $request->input('shipment.items', []);

        return $validated;
    }

    protected function bookingDraftResponse(Order $order, ShipmentRecord $draft): array
    {
        $payload = $this->manualToShipService->bookingDraftResponse($draft);

        foreach (['label', 'invoice'] as $document) {
            if (($payload['document_statuses'][$document]['status'] ?? null) !== 'ready') {
                continue;
            }

            $payload['document_statuses'][$document]['preview_url'] = route(
                'admin.sales.to-ship.booking-draft.document',
                [$order, 'document' => $document],
            );
        }

        return $payload;
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
