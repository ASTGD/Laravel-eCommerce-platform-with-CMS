<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Platform\CommerceCore\Models\PaymentAttempt;
use Platform\CommerceCore\Models\PaymentRefund;
use Webkul\Admin\Tests\AdminTestCase;
use Webkul\Core\Models\CoreConfig;
use Webkul\Payment\Tests\Concerns\ProvidePaymentHelpers;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\Refund;

use function Pest\Laravel\from;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(AdminTestCase::class);
uses(ProvidePaymentHelpers::class);

function configureBkashRefundGateway(): void
{
    foreach ([
        'sales.payment_methods.cashondelivery.active' => 1,
        'sales.payment_methods.bkash.active' => 1,
        'sales.payment_methods.bkash_gateway.sandbox' => 1,
        'sales.payment_methods.bkash_gateway.sandbox_base_url' => 'https://tokenized.sandbox.bka.sh/v1.2.0-beta',
        'sales.payment_methods.bkash_gateway.username' => 'sandbox-user',
        'sales.payment_methods.bkash_gateway.password' => 'sandbox-password',
        'sales.payment_methods.bkash_gateway.app_key' => 'sandbox-app-key',
        'sales.payment_methods.bkash_gateway.app_secret' => 'sandbox-app-secret',
        'sales.payment_methods.bkash_gateway.request_timeout' => 30,
        'sales.payment_methods.bkash_gateway.strict_amount_validation' => 1,
        'sales.payment_methods.bkash_gateway.log_payloads' => 1,
    ] as $code => $value) {
        CoreConfig::query()->updateOrCreate(
            ['code' => $code, 'channel_code' => 'default'],
            ['value' => (string) $value],
        );
    }
}

function createPaidBkashOrderForRefundTest($test): Order
{
    $cart = $test->createCartWithItems('bkash', [
        'base_currency_code' => 'BDT',
        'channel_currency_code' => 'BDT',
        'cart_currency_code' => 'BDT',
    ]);

    Http::fake([
        'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/token/grant' => Http::response([
            'statusCode' => '0000',
            'statusMessage' => 'Successful',
            'id_token' => 'refund-id-token',
            'refresh_token' => 'refund-refresh-token',
            'expires_in' => 3600,
        ], 200),
        'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/create' => Http::response([
            'statusCode' => '0000',
            'statusMessage' => 'Successful',
            'paymentID' => "refund-pay-{$cart->id}",
            'bkashURL' => "https://sandbox.payment.bkash.com/checkout/refund-pay-{$cart->id}",
            'transactionStatus' => 'Initiated',
        ], 200),
        'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/execute' => Http::response([
            'statusCode' => '0000',
            'statusMessage' => 'Successful',
            'paymentID' => "refund-pay-{$cart->id}",
            'trxID' => "refund-trx-{$cart->id}",
            'transactionStatus' => 'Completed',
            'amount' => (string) $cart->base_grand_total,
            'currency' => 'BDT',
            'merchantInvoiceNumber' => "bkash_invoice_{$cart->id}_refund",
            'payerReference' => $cart->billing_address->phone,
            'customerMsisdn' => $cart->billing_address->phone,
        ], 200),
    ]);

    get(route('commerce-core.bkash.redirect', ['code' => 'bkash']));

    PaymentAttempt::query()->where('cart_id', $cart->id)->update([
        'merchant_tran_id' => "bkash_invoice_{$cart->id}_refund",
        'session_key' => "refund-pay-{$cart->id}",
    ]);

    get(route('commerce-core.bkash.callback', [
        'code' => 'bkash',
        'status' => 'success',
        'paymentID' => "refund-pay-{$cart->id}",
    ]))->assertRedirect(route('shop.checkout.onepage.success'));

    return Order::query()->where('cart_id', $cart->id)->firstOrFail();
}

beforeEach(function () {
    Cache::flush();
    configureBkashRefundGateway();
});

it('creates a bkash payment refund when the admin refund succeeds', function () {
    $order = createPaidBkashOrderForRefundTest($this);
    $item = $order->items()->firstOrFail();

    Http::fake([
        'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/payment/refund' => Http::response([
            'completedTime' => '2026-04-15T10:20:30:000 GMT+0000',
            'transactionStatus' => 'Completed',
            'originalTrxID' => "refund-trx-{$order->cart_id}",
            'refundTrxID' => 'bkash-refund-001',
            'amount' => (string) $order->base_grand_total,
            'currency' => 'BDT',
            'charge' => '0.00',
        ], 200),
    ]);

    $this->loginAsAdmin();

    post(route('admin.sales.refunds.store', $order->id), [
        'refund' => [
            'items' => [
                $item->id => 1,
            ],
            'shipping' => '0',
            'adjustment_refund' => '0',
            'adjustment_fee' => '0',
            'reason' => 'Customer requested bKash refund',
        ],
    ])
        ->assertRedirect(route('admin.sales.orders.view', $order->id))
        ->assertSessionHas('success');

    $refund = Refund::query()->where('order_id', $order->id)->firstOrFail();
    $paymentRefund = PaymentRefund::query()->where('refund_id', $refund->id)->firstOrFail();

    expect($paymentRefund->provider)->toBe('bkash')
        ->and($paymentRefund->status)->toBe('refunded')
        ->and($paymentRefund->gateway_refund_ref)->toBe('bkash-refund-001')
        ->and($paymentRefund->reason)->toBe('Customer requested bKash refund');

    $html = view('commerce-core::admin.orders.payment-details', [
        'order' => $order->fresh(),
    ])->render();

    expect($html)->toContain('Refund History')
        ->toContain('bkash-refund-001');
});

it('rolls back the local refund when the bkash gateway rejects it', function () {
    $order = createPaidBkashOrderForRefundTest($this);
    $item = $order->items()->firstOrFail();

    Http::fake([
        'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/payment/refund' => Http::response([
            'statusMessage' => 'Refund window expired',
        ], 200),
    ]);

    $this->loginAsAdmin();

    from(route('admin.sales.orders.view', $order->id))
        ->post(route('admin.sales.refunds.store', $order->id), [
            'refund' => [
                'items' => [
                    $item->id => 1,
                ],
                'shipping' => '0',
                'adjustment_refund' => '0',
                'adjustment_fee' => '0',
            ],
        ])
        ->assertRedirect(route('admin.sales.orders.view', $order->id))
        ->assertSessionHas('error', 'Refund window expired');

    expect(Refund::query()->where('order_id', $order->id)->count())->toBe(0)
        ->and(PaymentRefund::query()->where('order_id', $order->id)->count())->toBe(0);
});

it('refreshes a pending bkash refund status from the admin order view', function () {
    $order = createPaidBkashOrderForRefundTest($this);
    $item = $order->items()->firstOrFail();

    Http::fake(function ($request) use ($order) {
        $payload = $request->data();

        if (array_key_exists('amount', $payload)) {
            return Http::response([
                'transactionStatus' => 'Initiated',
                'originalTrxID' => "refund-trx-{$order->cart_id}",
                'refundTrxID' => 'bkash-refund-pending',
                'amount' => (string) $order->base_grand_total,
                'currency' => 'BDT',
            ], 200);
        }

        return Http::response([
            'completedTime' => '2026-04-15T10:40:30:000 GMT+0000',
            'transactionStatus' => 'Completed',
            'originalTrxID' => "refund-trx-{$order->cart_id}",
            'refundTrxID' => 'bkash-refund-pending',
            'amount' => (string) $order->base_grand_total,
            'currency' => 'BDT',
            'charge' => '0.00',
        ], 200);
    });

    $this->loginAsAdmin();

    post(route('admin.sales.refunds.store', $order->id), [
        'refund' => [
            'items' => [
                $item->id => 1,
            ],
            'shipping' => '0',
            'adjustment_refund' => '0',
            'adjustment_fee' => '0',
        ],
    ])->assertRedirect(route('admin.sales.orders.view', $order->id));

    $paymentRefund = PaymentRefund::query()->where('order_id', $order->id)->firstOrFail();

    expect($paymentRefund->status)->toBe('pending');

    post(route('admin.sales.orders.payment_refunds.refresh', $paymentRefund))
        ->assertRedirect(route('admin.sales.orders.view', $order->id))
        ->assertSessionHas('success', 'Refund status refreshed successfully.');

    $paymentRefund->refresh();

    expect($paymentRefund->status)->toBe('refunded')
        ->and($paymentRefund->gateway_refund_ref)->toBe('bkash-refund-pending')
        ->and($paymentRefund->processed_at)->not->toBeNull();
});
