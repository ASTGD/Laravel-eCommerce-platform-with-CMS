@php
    $paymentAdditional = $order->payment?->additional ?? [];
    $paymentAttempt = \Platform\CommerceCore\Models\PaymentAttempt::query()
        ->where('order_id', $order->id)
        ->latest('id')
        ->first();
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
@endif
