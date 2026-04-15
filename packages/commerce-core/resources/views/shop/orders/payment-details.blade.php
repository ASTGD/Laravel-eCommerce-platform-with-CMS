@php
    $paymentAdditional = $order->payment?->additional ?? [];
    $paymentRefunds = \Platform\CommerceCore\Models\PaymentRefund::query()
        ->where('order_id', $order->id)
        ->latest('id')
        ->get();
    $paymentProvider = data_get($paymentAdditional, 'provider');
    $paymentMethodLabel = \Platform\CommerceCore\Support\PaymentMethodRegistry::labelForCode(
        data_get($paymentAdditional, 'method_code', $order->payment?->method)
    );
    $paymentHeading = match ($paymentProvider) {
        'bkash' => 'bKash Details',
        'sslcommerz' => 'SSLCommerz Details',
        default => null,
    };
@endphp

@if ($paymentHeading)
    <div class="rounded-lg bg-gray-50 p-3 text-xs text-zinc-600">
        <p class="font-medium text-navyBlue">
            {{ $paymentHeading }}
        </p>

        <p>Method: {{ $paymentMethodLabel }}</p>
        <p>Status: {{ strtoupper((string) data_get($paymentAdditional, 'validation_status', data_get($paymentAdditional, 'status', 'pending'))) }}</p>

        @if ($paymentProvider === 'bkash' && data_get($paymentAdditional, 'merchant_invoice_number'))
            <p>Transaction: {{ data_get($paymentAdditional, 'merchant_invoice_number') }}</p>
        @endif

        @if ($paymentProvider === 'sslcommerz' && data_get($paymentAdditional, 'merchant_transaction_id'))
            <p>Transaction: {{ data_get($paymentAdditional, 'merchant_transaction_id') }}</p>
        @endif

        @if ($paymentProvider === 'bkash' && data_get($paymentAdditional, 'payment_id'))
            <p>Payment ID: {{ data_get($paymentAdditional, 'payment_id') }}</p>
        @endif

        @if (data_get($paymentAdditional, 'gateway_transaction_id'))
            <p>Gateway Reference: {{ data_get($paymentAdditional, 'gateway_transaction_id') }}</p>
        @endif

        @if ($paymentProvider === 'bkash' && data_get($paymentAdditional, 'payer_reference'))
            <p>Payer Reference: {{ data_get($paymentAdditional, 'payer_reference') }}</p>
        @endif

        @if ($paymentProvider === 'bkash' && data_get($paymentAdditional, 'customer_msisdn'))
            <p>Customer MSISDN: {{ data_get($paymentAdditional, 'customer_msisdn') }}</p>
        @endif

        @if (in_array($paymentProvider, ['sslcommerz', 'bkash'], true) && $paymentRefunds->isNotEmpty())
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
