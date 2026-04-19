<?php

namespace Platform\CommerceCore\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Platform\CommerceCore\DataGrids\Sales\CodSettlementDataGrid;
use Platform\CommerceCore\Http\Requests\Admin\CodSettlementUpdateRequest;
use Platform\CommerceCore\Models\CodSettlement;
use Platform\CommerceCore\Services\CodSettlementService;

class CodSettlementController extends Controller
{
    public function __construct(protected CodSettlementService $codSettlementService) {}

    public function index()
    {
        if (request()->ajax()) {
            return datagrid(CodSettlementDataGrid::class)->process();
        }

        return view('commerce-core::admin.cod-settlements.index');
    }

    public function show(CodSettlement $codSettlement): View
    {
        $codSettlement->load([
            'shipmentRecord.nativeShipment',
            'shipmentRecord.carrier',
            'order',
            'carrier',
            'batchItem.batch',
            'creator',
            'updater',
        ]);

        return view('commerce-core::admin.cod-settlements.show', [
            'codSettlement' => $codSettlement,
            'statusOptions' => CodSettlement::statusLabels(),
            'summary' => $this->codSettlementService->detailSummary($codSettlement),
        ]);
    }

    public function update(CodSettlementUpdateRequest $request, CodSettlement $codSettlement): RedirectResponse
    {
        $this->codSettlementService->updateSettlement(
            $codSettlement,
            $request->validated(),
            auth('admin')->id(),
        );

        return redirect()
            ->route('admin.sales.cod-settlements.view', $codSettlement)
            ->with('success', 'COD settlement updated.');
    }
}
