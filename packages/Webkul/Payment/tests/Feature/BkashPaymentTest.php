<?php

use Illuminate\Support\Facades\Http;
use Platform\CommerceCore\Models\PaymentAttempt;
use Platform\CommerceCore\Models\PaymentGatewayEvent;
use Webkul\Core\Models\CoreConfig;
use Webkul\Payment\Tests\Concerns\ProvidePaymentHelpers;
use Webkul\Sales\Models\Invoice;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderTransaction;

use function Pest\Laravel\get;

uses(ProvidePaymentHelpers::class);

function setBkashConfig(string $code, mixed $value): void
{
    CoreConfig::query()->updateOrCreate(
        [
            'code' => $code,
            'channel_code' => 'default',
        ],
        [
            'value' => (string) $value,
        ],
    );
}

beforeEach(function () {
    setBkashConfig('sales.payment_methods.cashondelivery.active', 1);
    setBkashConfig('sales.payment_methods.bkash.active', 1);
    setBkashConfig('sales.payment_methods.bkash_gateway.sandbox', 1);
    setBkashConfig('sales.payment_methods.bkash_gateway.sandbox_base_url', 'https://tokenized.sandbox.bka.sh/v1.2.0-beta');
    setBkashConfig('sales.payment_methods.bkash_gateway.username', 'sandbox-user');
    setBkashConfig('sales.payment_methods.bkash_gateway.password', 'sandbox-password');
    setBkashConfig('sales.payment_methods.bkash_gateway.app_key', 'sandbox-app-key');
    setBkashConfig('sales.payment_methods.bkash_gateway.app_secret', 'sandbox-app-secret');
    setBkashConfig('sales.payment_methods.bkash_gateway.request_timeout', 30);
    setBkashConfig('sales.payment_methods.bkash_gateway.strict_amount_validation', 1);
    setBkashConfig('sales.payment_methods.bkash_gateway.log_payloads', 1);
});

it('starts a direct bkash checkout session for the selected payment method', function () {
    $cart = $this->createCartWithItems('bkash', [
        'base_currency_code' => 'BDT',
        'channel_currency_code' => 'BDT',
        'cart_currency_code' => 'BDT',
    ]);

    Http::fake([
        'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/token/grant' => Http::response([
            'statusCode' => '0000',
            'statusMessage' => 'Successful',
            'id_token' => 'id-token-123',
            'refresh_token' => 'refresh-token-123',
            'expires_in' => 3600,
        ], 200),
        'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/create' => Http::response([
            'statusCode' => '0000',
            'statusMessage' => 'Successful',
            'paymentID' => 'pay-123',
            'bkashURL' => 'https://sandbox.payment.bkash.com/checkout/pay-123',
            'transactionStatus' => 'Initiated',
            'merchantInvoiceNumber' => 'bkash-invoice-123',
        ], 200),
    ]);

    get(route('commerce-core.bkash.redirect', ['code' => 'bkash']))
        ->assertRedirect('https://sandbox.payment.bkash.com/checkout/pay-123');

    $attempt = PaymentAttempt::query()->where('cart_id', $cart->id)->latest('id')->first();

    expect($attempt)->not->toBeNull()
        ->and($attempt->provider)->toBe('bkash')
        ->and($attempt->method_code)->toBe('bkash')
        ->and($attempt->status)->toBe('redirected')
        ->and($attempt->session_key)->toBe('pay-123');

    Http::assertSentCount(2);
});

it('creates an order and invoice after a verified direct bkash callback', function () {
    $cart = $this->createCartWithItems('bkash', [
        'base_currency_code' => 'BDT',
        'channel_currency_code' => 'BDT',
        'cart_currency_code' => 'BDT',
    ]);

    Http::fake([
        'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/token/grant' => Http::response([
            'statusCode' => '0000',
            'statusMessage' => 'Successful',
            'id_token' => 'id-token-456',
            'refresh_token' => 'refresh-token-456',
            'expires_in' => 3600,
        ], 200),
        'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/create' => Http::response([
            'statusCode' => '0000',
            'statusMessage' => 'Successful',
            'paymentID' => 'pay-456',
            'bkashURL' => 'https://sandbox.payment.bkash.com/checkout/pay-456',
            'transactionStatus' => 'Initiated',
        ], 200),
        'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/execute' => Http::response([
            'statusCode' => '0000',
            'statusMessage' => 'Successful',
            'paymentID' => 'pay-456',
            'trxID' => 'trx-456',
            'transactionStatus' => 'Completed',
            'amount' => (string) $cart->base_grand_total,
            'currency' => 'BDT',
            'merchantInvoiceNumber' => 'bkash-invoice-456',
            'payerReference' => $cart->billing_address->phone,
            'customerMsisdn' => $cart->billing_address->phone,
        ], 200),
    ]);

    get(route('commerce-core.bkash.redirect', ['code' => 'bkash']));

    PaymentAttempt::query()->where('cart_id', $cart->id)->update([
        'merchant_tran_id' => 'bkash-invoice-456',
        'session_key' => 'pay-456',
    ]);

    get(route('commerce-core.bkash.callback', [
        'code' => 'bkash',
        'status' => 'success',
        'paymentID' => 'pay-456',
    ]))
        ->assertRedirect(route('shop.checkout.onepage.success'))
        ->assertSessionHas('order_id');

    $order = Order::query()->where('cart_id', $cart->id)->first();

    expect($order)->not->toBeNull()
        ->and($order->status)->toBe('processing')
        ->and(data_get($order->payment?->additional, 'provider'))->toBe('bkash')
        ->and(data_get($order->payment?->additional, 'payment_id'))->toBe('pay-456');

    expect(Invoice::query()->where('order_id', $order->id)->exists())->toBeTrue();

    $transaction = OrderTransaction::query()->where('transaction_id', 'trx-456')->first();

    expect($transaction)->not->toBeNull()
        ->and($transaction->order_id)->toBe($order->id);

    $cart->refresh();

    expect($cart->is_active)->toBe(0);

    $attempt = PaymentAttempt::query()->where('cart_id', $cart->id)->latest('id')->first();

    expect($attempt)->not->toBeNull()
        ->and($attempt->status)->toBe('paid')
        ->and($attempt->gateway_tran_id)->toBe('trx-456')
        ->and($attempt->finalized_via)->toBe('callback_success');
});

it('falls back to query payment when execute payment is inconclusive', function () {
    $cart = $this->createCartWithItems('bkash', [
        'base_currency_code' => 'BDT',
        'channel_currency_code' => 'BDT',
        'cart_currency_code' => 'BDT',
    ]);

    Http::fake([
        'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/token/grant' => Http::response([
            'statusCode' => '0000',
            'statusMessage' => 'Successful',
            'id_token' => 'id-token-789',
            'refresh_token' => 'refresh-token-789',
            'expires_in' => 3600,
        ], 200),
        'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/create' => Http::response([
            'statusCode' => '0000',
            'statusMessage' => 'Successful',
            'paymentID' => 'pay-789',
            'bkashURL' => 'https://sandbox.payment.bkash.com/checkout/pay-789',
            'transactionStatus' => 'Initiated',
        ], 200),
        'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/execute' => Http::sequence()
            ->push([
                'statusCode' => '2023',
                'statusMessage' => 'Payment execution is pending',
                'paymentID' => 'pay-789',
                'transactionStatus' => 'Initiated',
            ], 200),
        'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/payment/status' => Http::response([
            'statusCode' => '0000',
            'statusMessage' => 'Successful',
            'paymentID' => 'pay-789',
            'trxID' => 'trx-789',
            'transactionStatus' => 'Completed',
            'amount' => (string) $cart->base_grand_total,
            'currency' => 'BDT',
            'merchantInvoiceNumber' => 'bkash-invoice-789',
        ], 200),
    ]);

    get(route('commerce-core.bkash.redirect', ['code' => 'bkash']));

    PaymentAttempt::query()->where('cart_id', $cart->id)->update([
        'merchant_tran_id' => 'bkash-invoice-789',
        'session_key' => 'pay-789',
    ]);

    $payload = [
        'code' => 'bkash',
        'status' => 'success',
        'paymentID' => 'pay-789',
    ];

    get(route('commerce-core.bkash.callback', $payload))
        ->assertRedirect(route('shop.checkout.onepage.success'));

    $attempt = PaymentAttempt::query()->where('cart_id', $cart->id)->first();

    expect($attempt->status)->toBe('paid')
        ->and(PaymentGatewayEvent::query()->where('payment_attempt_id', $attempt->id)->where('event_type', 'query_payment')->count())->toBe(1);
});

it('finalizes only once when the success callback is received twice', function () {
    $cart = $this->createCartWithItems('bkash', [
        'base_currency_code' => 'BDT',
        'channel_currency_code' => 'BDT',
        'cart_currency_code' => 'BDT',
    ]);

    Http::fake([
        'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/token/grant' => Http::response([
            'statusCode' => '0000',
            'statusMessage' => 'Successful',
            'id_token' => 'id-token-dup',
            'refresh_token' => 'refresh-token-dup',
            'expires_in' => 3600,
        ], 200),
        'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/create' => Http::response([
            'statusCode' => '0000',
            'statusMessage' => 'Successful',
            'paymentID' => 'pay-dup',
            'bkashURL' => 'https://sandbox.payment.bkash.com/checkout/pay-dup',
            'transactionStatus' => 'Initiated',
        ], 200),
        'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/execute' => Http::response([
            'statusCode' => '0000',
            'statusMessage' => 'Successful',
            'paymentID' => 'pay-dup',
            'trxID' => 'trx-dup',
            'transactionStatus' => 'Completed',
            'amount' => (string) $cart->base_grand_total,
            'currency' => 'BDT',
            'merchantInvoiceNumber' => 'bkash-invoice-dup',
        ], 200),
    ]);

    get(route('commerce-core.bkash.redirect', ['code' => 'bkash']));

    PaymentAttempt::query()->where('cart_id', $cart->id)->update([
        'merchant_tran_id' => 'bkash-invoice-dup',
        'session_key' => 'pay-dup',
    ]);

    $payload = [
        'code' => 'bkash',
        'status' => 'success',
        'paymentID' => 'pay-dup',
    ];

    get(route('commerce-core.bkash.callback', $payload))
        ->assertRedirect(route('shop.checkout.onepage.success'));

    get(route('commerce-core.bkash.callback', $payload))
        ->assertRedirect(route('shop.checkout.onepage.success'));

    $order = Order::query()->where('cart_id', $cart->id)->first();

    expect(Order::query()->where('cart_id', $cart->id)->count())->toBe(1)
        ->and(Invoice::query()->where('order_id', $order->id)->count())->toBe(1)
        ->and(OrderTransaction::query()->where('transaction_id', 'trx-dup')->count())->toBe(1);

    $attempt = PaymentAttempt::query()->where('cart_id', $cart->id)->first();

    expect($attempt->status)->toBe('paid')
        ->and($attempt->callback_count)->toBe(2);
});

it('returns customers to the payment step when direct bkash payment is cancelled', function () {
    $cart = $this->createCartWithItems('bkash', [
        'base_currency_code' => 'BDT',
        'channel_currency_code' => 'BDT',
        'cart_currency_code' => 'BDT',
    ]);

    Http::fake([
        'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/token/grant' => Http::response([
            'statusCode' => '0000',
            'statusMessage' => 'Successful',
            'id_token' => 'id-token-cancel',
            'refresh_token' => 'refresh-token-cancel',
            'expires_in' => 3600,
        ], 200),
        'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/create' => Http::response([
            'statusCode' => '0000',
            'statusMessage' => 'Successful',
            'paymentID' => 'pay-cancel',
            'bkashURL' => 'https://sandbox.payment.bkash.com/checkout/pay-cancel',
            'transactionStatus' => 'Initiated',
        ], 200),
    ]);

    get(route('commerce-core.bkash.redirect', ['code' => 'bkash']));

    PaymentAttempt::query()->where('cart_id', $cart->id)->update([
        'session_key' => 'pay-cancel',
    ]);

    get(route('commerce-core.bkash.callback', [
        'code' => 'bkash',
        'status' => 'cancel',
        'paymentID' => 'pay-cancel',
    ]))
        ->assertRedirect(route('shop.checkout.onepage.index', ['step' => 'payment']))
        ->assertSessionHas('error');

    $attempt = PaymentAttempt::query()->where('cart_id', $cart->id)->latest('id')->first();

    expect($attempt)->not->toBeNull()
        ->and($attempt->status)->toBe('cancelled');
});
