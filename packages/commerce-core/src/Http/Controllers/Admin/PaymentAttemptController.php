<?php

namespace Platform\CommerceCore\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Platform\CommerceCore\DataGrids\Sales\PaymentAttemptDataGrid;
use Platform\CommerceCore\Models\PaymentAttempt;
use Platform\CommerceCore\Services\PaymentReconciliationService;
use Webkul\Sales\Models\Order;

class PaymentAttemptController extends Controller
{
    public function __construct(
        protected PaymentReconciliationService $reconciliationService,
    ) {}

    public function index()
    {
        if (request()->ajax()) {
            return datagrid(PaymentAttemptDataGrid::class)->process();
        }

        return view('commerce-core::admin.payments.index');
    }

    public function show(PaymentAttempt $paymentAttempt): View
    {
        $paymentAttempt->load(['events' => fn ($query) => $query->latest('id'), 'order']);

        return view('commerce-core::admin.payments.view', compact('paymentAttempt'));
    }

    public function reconcile(PaymentAttempt $paymentAttempt): RedirectResponse
    {
        try {
            $this->reconciliationService->reconcile($paymentAttempt, 'manual_reconcile');

            return redirect()
                ->route('admin.sales.payments.view', $paymentAttempt)
                ->with('success', 'Payment reconciliation completed.');
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.sales.payments.view', $paymentAttempt)
                ->with('error', $e->getMessage());
        }
    }

    public function reconcileOrder(Order $order): RedirectResponse
    {
        $paymentAttempt = PaymentAttempt::query()
            ->where('order_id', $order->id)
            ->latest('id')
            ->first();

        if (! $paymentAttempt) {
            return redirect()
                ->route('admin.sales.orders.view', $order->id)
                ->with('error', 'No external payment attempt was found for this order.');
        }

        try {
            $this->reconciliationService->reconcile($paymentAttempt, 'manual_reconcile');

            return redirect()
                ->route('admin.sales.orders.view', $order->id)
                ->with('success', 'Payment reconciliation completed.');
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.sales.orders.view', $order->id)
                ->with('error', $e->getMessage());
        }
    }
}
