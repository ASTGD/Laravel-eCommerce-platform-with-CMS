<?php

namespace Platform\CommerceCore\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Platform\CommerceCore\Models\PaymentRefund;
use Platform\CommerceCore\Services\PaymentRefundStatusService;

class PaymentRefundController extends Controller
{
    public function __construct(
        protected PaymentRefundStatusService $refundStatusService,
    ) {}

    public function refresh(PaymentRefund $paymentRefund): RedirectResponse
    {
        try {
            $paymentRefund = $this->refundStatusService->refresh($paymentRefund);

            return redirect()
                ->route('admin.sales.orders.view', $paymentRefund->order_id)
                ->with('success', 'Refund status refreshed successfully.');
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.sales.orders.view', $paymentRefund->order_id)
                ->with('error', $e->getMessage());
        }
    }
}
