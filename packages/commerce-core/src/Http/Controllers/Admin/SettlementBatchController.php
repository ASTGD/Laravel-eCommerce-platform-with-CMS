<?php

namespace Platform\CommerceCore\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use Platform\CommerceCore\DataGrids\Sales\SettlementBatchDataGrid;
use Platform\CommerceCore\Http\Requests\Admin\SettlementBatchImportRequest;
use Platform\CommerceCore\Http\Requests\Admin\SettlementBatchStoreRequest;
use Platform\CommerceCore\Http\Requests\Admin\SettlementBatchUpdateRequest;
use Platform\CommerceCore\Models\SettlementBatch;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Services\SettlementBatchImportService;
use Platform\CommerceCore\Services\SettlementBatchService;

class SettlementBatchController extends Controller
{
    public function __construct(
        protected SettlementBatchService $settlementBatchService,
        protected SettlementBatchImportService $settlementBatchImportService,
    ) {}

    public function index()
    {
        if (request()->ajax()) {
            return datagrid(SettlementBatchDataGrid::class)->process();
        }

        return view('commerce-core::admin.settlement-batches.index');
    }

    public function create(): View
    {
        return view('commerce-core::admin.settlement-batches.create', [
            'carriers' => ShipmentCarrier::query()->orderBy('sort_order')->orderBy('name')->get(),
            'eligibleSettlements' => $this->settlementBatchService->eligibleSettlements(),
            'statusOptions' => SettlementBatch::statusLabels(),
        ]);
    }

    public function import(): View
    {
        return view('commerce-core::admin.settlement-batches.import', [
            'carriers' => ShipmentCarrier::query()->orderBy('sort_order')->orderBy('name')->get(),
            'statusOptions' => SettlementBatch::statusLabels(),
        ]);
    }

    public function store(SettlementBatchStoreRequest $request): RedirectResponse
    {
        $batch = $this->settlementBatchService->createBatch(
            $request->validated(),
            auth('admin')->id(),
        );

        return redirect()
            ->route('admin.sales.settlement-batches.view', $batch)
            ->with('success', 'Settlement batch created.');
    }

    public function show(SettlementBatch $settlementBatch): View
    {
        $settlementBatch->load([
            'carrier',
            'creator',
            'updater',
            'items.codSettlement.order',
            'items.codSettlement.shipmentRecord',
            'items.codSettlement.carrier',
        ]);

        return view('commerce-core::admin.settlement-batches.show', [
            'settlementBatch' => $settlementBatch,
            'statusOptions' => SettlementBatch::statusLabels(),
            'summary' => $this->settlementBatchService->detailSummary($settlementBatch),
        ]);
    }

    public function update(SettlementBatchUpdateRequest $request, SettlementBatch $settlementBatch): RedirectResponse
    {
        $this->settlementBatchService->updateBatch(
            $settlementBatch,
            $request->validated(),
            auth('admin')->id(),
        );

        return redirect()
            ->route('admin.sales.settlement-batches.view', $settlementBatch)
            ->with('success', 'Settlement batch updated.');
    }

    public function storeImport(SettlementBatchImportRequest $request): RedirectResponse
    {
        $result = $this->settlementBatchImportService->importFromUploadedFile(
            $request->file('import_file'),
            Arr::except($request->validated(), ['import_file']),
            auth('admin')->id(),
        );

        return redirect()
            ->route('admin.sales.settlement-batches.view', $result['batch'])
            ->with('success', "Settlement batch imported from CSV ({$result['rows_imported']} row(s)).");
    }
}
