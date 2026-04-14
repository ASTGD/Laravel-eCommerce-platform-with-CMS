@php
    $paymentAdditional = $order->payment?->additional ?? [];
@endphp

@if (data_get($paymentAdditional, 'provider') === 'sslcommerz')
    <p class="pt-4 font-semibold text-gray-800 dark:text-white">
        SSLCOMMERZ Details
    </p>

    <div class="space-y-1 pt-1 text-gray-600 dark:text-gray-300">
        <p>Method: {{ data_get($paymentAdditional, 'method_code', $order->payment?->method) }}</p>
        <p>Status: {{ strtoupper((string) data_get($paymentAdditional, 'validation_status', data_get($paymentAdditional, 'status', 'pending'))) }}</p>

        @if (data_get($paymentAdditional, 'merchant_transaction_id'))
            <p>Merchant Transaction: {{ data_get($paymentAdditional, 'merchant_transaction_id') }}</p>
        @endif

        @if (data_get($paymentAdditional, 'gateway_transaction_id'))
            <p>Gateway Transaction: {{ data_get($paymentAdditional, 'gateway_transaction_id') }}</p>
        @endif

        @if (data_get($paymentAdditional, 'session_key'))
            <p>Session Key: {{ data_get($paymentAdditional, 'session_key') }}</p>
        @endif

        @if (data_get($paymentAdditional, 'finalized_via'))
            <p>Finalized Via: {{ data_get($paymentAdditional, 'finalized_via') }}</p>
        @endif
    </div>
@endif
