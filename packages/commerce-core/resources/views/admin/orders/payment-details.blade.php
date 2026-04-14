@php
    $paymentAdditional = $order->payment?->additional ?? [];
    $paymentAttempt = \Platform\CommerceCore\Models\PaymentAttempt::query()
        ->where('order_id', $order->id)
        ->latest('id')
        ->first();
    $paymentRefunds = \Platform\CommerceCore\Models\PaymentRefund::query()
        ->where('order_id', $order->id)
        ->latest('id')
        ->get();
@endphp

@if (data_get($paymentAdditional, 'provider') === 'sslcommerz' || $paymentAttempt)
    <p class="pt-4 font-semibold text-gray-800 dark:text-white">
        SSLCOMMERZ Details
    </p>

    <div class="space-y-1 pt-1 text-gray-600 dark:text-gray-300">
        <p>Method: {{ $paymentAttempt?->method_code ?: data_get($paymentAdditional, 'method_code', $order->payment?->method) }}</p>
        <p>Status: {{ strtoupper((string) ($paymentAttempt?->validation_status ?: data_get($paymentAdditional, 'validation_status', data_get($paymentAdditional, 'status', 'pending')))) }}</p>

        @if ($paymentAttempt?->merchant_tran_id || data_get($paymentAdditional, 'merchant_transaction_id'))
            <p>Merchant Transaction: {{ $paymentAttempt?->merchant_tran_id ?: data_get($paymentAdditional, 'merchant_transaction_id') }}</p>
        @endif

        @if ($paymentAttempt?->gateway_tran_id || data_get($paymentAdditional, 'gateway_transaction_id'))
            <p>Gateway Transaction: {{ $paymentAttempt?->gateway_tran_id ?: data_get($paymentAdditional, 'gateway_transaction_id') }}</p>
        @endif

        @if ($paymentAttempt?->session_key || data_get($paymentAdditional, 'session_key'))
            <p>Session Key: {{ $paymentAttempt?->session_key ?: data_get($paymentAdditional, 'session_key') }}</p>
        @endif

        @if ($paymentAttempt?->finalized_via || data_get($paymentAdditional, 'finalized_via'))
            <p>Finalized Via: {{ $paymentAttempt?->finalized_via ?: data_get($paymentAdditional, 'finalized_via') }}</p>
        @endif

        @if ($paymentAttempt?->last_reconciled_at)
            <p>Last Reconciled: {{ core()->formatDate($paymentAttempt->last_reconciled_at, 'd M Y H:i') }}</p>
        @endif
    </div>

    @if ($paymentAttempt)
        <div class="mt-3 flex gap-2">
            <a
                href="{{ route('admin.sales.payments.view', $paymentAttempt) }}"
                class="secondary-button"
            >
                View Payment Attempt
            </a>

            @if (bouncer()->hasPermission('sales.orders.reconcile_payment'))
                <form method="POST" action="{{ route('admin.sales.orders.payments.reconcile', $order->id) }}">
                    @csrf

                    <button type="submit" class="primary-button">
                        Reconcile Payment
                    </button>
                </form>
            @endif
        </div>
    @endif

    @if ($paymentRefunds->isNotEmpty())
        <div class="mt-4 rounded border border-slate-200 p-3 dark:border-gray-800">
            <p class="font-semibold text-gray-800 dark:text-white">
                Refund History
            </p>

            <div class="mt-3 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                @foreach ($paymentRefunds as $paymentRefund)
                    <div class="rounded border border-slate-200 p-3 dark:border-gray-800">
                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-1">
                                <p>Refund Amount: {{ core()->formatBasePrice((float) $paymentRefund->requested_amount) }}</p>
                                <p>Status: {{ strtoupper($paymentRefund->status) }}</p>

                                @if ($paymentRefund->gateway_refund_ref)
                                    <p>Refund Reference: {{ $paymentRefund->gateway_refund_ref }}</p>
                                @endif

                                @if ($paymentRefund->gateway_status)
                                    <p>Gateway Status: {{ $paymentRefund->gateway_status }}</p>
                                @endif

                                @if ($paymentRefund->reason)
                                    <p>Reason: {{ $paymentRefund->reason }}</p>
                                @endif

                                @if ($paymentRefund->last_error)
                                    <p>Last Error: {{ $paymentRefund->last_error }}</p>
                                @endif
                            </div>

                            @if (
                                in_array($paymentRefund->status, ['pending', 'invalid'], true)
                                && bouncer()->hasPermission('sales.orders.refresh_refund_status')
                            )
                                <form method="POST" action="{{ route('admin.sales.orders.payment_refunds.refresh', $paymentRefund) }}">
                                    @csrf

                                    <button type="submit" class="secondary-button">
                                        Refresh Refund Status
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endif
