<?php

namespace Platform\CommerceCore\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Platform\CommerceCore\Services\ManualCodReceivableService;

class ManualCodReceivableController extends Controller
{
    public function __construct(
        protected ManualCodReceivableService $manualCodReceivableService,
    ) {}

    public function index(): View
    {
        $search = trim((string) request('search')) ?: null;
        $perPage = (int) request('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        return view('commerce-core::admin.manual-cod-receivables.index', [
            'courierSummaries' => $this->manualCodReceivableService->courierSummaries($perPage, $search),
        ]);
    }

    public function recordReceived(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'shipment_carrier_id' => ['required', 'integer', 'exists:shipment_carriers,id'],
            'amount'              => ['required', 'numeric', 'min:0.01'],
            'note'                => ['nullable', 'string', 'max:1000'],
        ], [
            'shipment_carrier_id.required' => 'Choose the courier you received the COD payment from.',
            'shipment_carrier_id.exists'   => 'Choose a valid courier from the receivables list.',
            'amount.required'              => 'Enter the amount your business received from the courier.',
            'amount.numeric'               => 'Enter the received amount as a valid number.',
            'amount.min'                   => 'Received amount must be greater than zero.',
            'note.max'                     => 'Keep the note within 1000 characters.',
        ]);

        $allocation = $this->manualCodReceivableService->recordReceipt(
            carrierId: (int) $validated['shipment_carrier_id'],
            amount: (float) $validated['amount'],
            note: $validated['note'] ?? null,
            actorAdminId: auth('admin')->id(),
        );

        return redirect()
            ->route('admin.sales.cod-receivables.index')
            ->with('success', 'COD payment recorded as received by the merchant and applied to '.$allocation['allocation_count'].' delivery record(s).');
    }
}
