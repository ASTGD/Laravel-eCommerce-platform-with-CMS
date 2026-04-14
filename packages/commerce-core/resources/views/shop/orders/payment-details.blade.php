@php
    $paymentAdditional = $order->payment?->additional ?? [];
    $paymentRefunds = \Platform\CommerceCore\Models\PaymentRefund::query()
        ->where('order_id', $order->id)
        ->latest('id')
        ->get();
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

        @if ($paymentRefunds->isNotEmpty())
            <div class="mt-3 border-t border-zinc-200 pt-3">
                <p class="font-medium text-navyBlue">
                    Refund History
                </p>

                <div class="mt-2 space-y-2">
                    @foreach ($paymentRefunds as $paymentRefund)
                        <div class="rounded border border-zinc-200 bg-white p-2">
                            <p>Amount: {{ core()->formatBasePrice((float) $paymentRefund->requested_amount) }}</p>
                            <p>Status: {{ strtoupper($paymentRefund->status) }}</p>

                            @if ($paymentRefund->gateway_refund_ref)
                                <p>Refund Reference: {{ $paymentRefund->gateway_refund_ref }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endif
