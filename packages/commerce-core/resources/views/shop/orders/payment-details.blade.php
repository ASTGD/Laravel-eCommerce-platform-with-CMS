@php
    $paymentAdditional = $order->payment?->additional ?? [];
@endphp

@if (data_get($paymentAdditional, 'provider') === 'sslcommerz')
    <div class="rounded-lg bg-gray-50 p-3 text-xs text-zinc-600">
        <p class="font-medium text-navyBlue">
            SSLCOMMERZ Details
        </p>

        <p>Status: {{ strtoupper((string) data_get($paymentAdditional, 'validation_status', data_get($paymentAdditional, 'status', 'pending'))) }}</p>

        @if (data_get($paymentAdditional, 'merchant_transaction_id'))
            <p>Transaction: {{ data_get($paymentAdditional, 'merchant_transaction_id') }}</p>
        @endif

        @if (data_get($paymentAdditional, 'gateway_transaction_id'))
            <p>Gateway Reference: {{ data_get($paymentAdditional, 'gateway_transaction_id') }}</p>
        @endif
    </div>
@endif
