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
use function Pest\Laravel\post;

uses(ProvidePaymentHelpers::class);

function setSslCommerzConfig(string $code, mixed $value): void
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
    setSslCommerzConfig('sales.payment_methods.mode.channel', 'custom');
    setSslCommerzConfig('sales.payment_methods.cashondelivery.active', 1);
    setSslCommerzConfig('sales.payment_methods.sslcommerz_gateway.sandbox', 1);
    setSslCommerzConfig('sales.payment_methods.sslcommerz_gateway.store_id', 'test_store');
    setSslCommerzConfig('sales.payment_methods.sslcommerz_gateway.store_password', 'test_password');
    setSslCommerzConfig('sales.payment_methods.sslcommerz_gateway.request_timeout', 30);
    setSslCommerzConfig('sales.payment_methods.sslcommerz_gateway.strict_amount_validation', 1);
    setSslCommerzConfig('sales.payment_methods.sslcommerz_gateway.log_payloads', 1);
    setSslCommerzConfig('sales.payment_methods.sslcommerz_bkash.active', 1);
    setSslCommerzConfig('sales.payment_methods.sslcommerz_card.active', 1);
    setSslCommerzConfig('sales.payment_methods.sslcommerz_nagad.active', 1);
});

it('starts an sslcommerz checkout session for the selected Bangladesh payment method', function () {
    $cart = $this->createCartWithItems('sslcommerz_bkash', [
        'base_currency_code' => 'BDT',
        'channel_currency_code' => 'BDT',
        'cart_currency_code' => 'BDT',
    ]);

    Http::fake([
        'https://sandbox.sslcommerz.com/gwprocess/v3/api.php' => Http::response([
            'status' => 'SUCCESS',
            'sessionkey' => 'SESSION-123',
            'GatewayPageURL' => 'https://sandbox.sslcommerz.com/EasyCheckOut/test-session',
        ], 200),
    ]);

    get(route('commerce-core.sslcommerz.redirect', ['code' => 'sslcommerz_bkash']))
        ->assertRedirect('https://sandbox.sslcommerz.com/EasyCheckOut/test-session');

    Http::assertSent(function ($request) use ($cart) {
        return $request->url() === 'https://sandbox.sslcommerz.com/gwprocess/v3/api.php'
            && $request['value_a'] === (string) $cart->id
            && $request['store_id'] === 'test_store';
    });

    $attempt = PaymentAttempt::query()->where('cart_id', $cart->id)->latest('id')->first();

    expect($attempt)->not->toBeNull()
        ->and($attempt->provider)->toBe('sslcommerz')
        ->and($attempt->method_code)->toBe('sslcommerz_bkash')
        ->and($attempt->status)->toBe('redirected')
        ->and($attempt->session_key)->toBe('SESSION-123');
});

it('returns customers to the checkout payment step when sslcommerz payment fails', function () {
    $cart = $this->createCartWithItems('sslcommerz_bkash', [
        'base_currency_code' => 'BDT',
        'channel_currency_code' => 'BDT',
        'cart_currency_code' => 'BDT',
    ]);

    get(route('commerce-core.sslcommerz.fail', ['code' => 'sslcommerz_bkash', 'value_a' => $cart->id]))
        ->assertRedirect(route('shop.checkout.onepage.index', ['step' => 'payment']))
        ->assertSessionHas('error');
});

it('creates an order and invoice after a verified sslcommerz payment callback', function () {
    $cart = $this->createCartWithItems('sslcommerz_bkash', [
        'base_currency_code' => 'BDT',
        'channel_currency_code' => 'BDT',
        'cart_currency_code' => 'BDT',
    ]);

    $transactionId = 'bank-tran-123';
    $validationId = 'val-123';

    Http::fake([
        'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php*' => Http::response([
            'status' => 'VALID',
            'tran_id' => "cart_{$cart->id}_20260414010101",
            'bank_tran_id' => $transactionId,
            'val_id' => $validationId,
            'value_a' => (string) $cart->id,
            'value_b' => 'sslcommerz_bkash',
            'currency_type' => 'BDT',
            'amount' => (string) $cart->base_grand_total,
        ], 200),
    ]);

    get(route('commerce-core.sslcommerz.success', [
        'code' => 'sslcommerz_bkash',
        'val_id' => $validationId,
        'tran_id' => "cart_{$cart->id}_20260414010101",
        'value_a' => $cart->id,
    ]))
        ->assertRedirect(route('shop.checkout.onepage.success'))
        ->assertSessionHas('order_id');

    $order = Order::query()->where('cart_id', $cart->id)->first();

    expect($order)->not->toBeNull()
        ->and($order->status)->toBe('processing');

    $transaction = OrderTransaction::query()->where('transaction_id', $transactionId)->first();

    expect($transaction)->not->toBeNull()
        ->and($transaction->order_id)->toBe($order->id);

    expect(Invoice::query()->where('order_id', $order->id)->exists())->toBeTrue();

    $cart->refresh();

    expect($cart->is_active)->toBe(0);

    $attempt = PaymentAttempt::query()->where('cart_id', $cart->id)->latest('id')->first();

    expect($attempt)->not->toBeNull()
        ->and($attempt->status)->toBe('paid')
        ->and($attempt->finalized_via)->toBe('success_redirect')
        ->and($attempt->gateway_tran_id)->toBe($transactionId);
});

it('does not duplicate orders or invoices when the success callback is received twice', function () {
    $cart = $this->createCartWithItems('sslcommerz_bkash', [
        'base_currency_code' => 'BDT',
        'channel_currency_code' => 'BDT',
        'cart_currency_code' => 'BDT',
    ]);

    Http::fake([
        'https://sandbox.sslcommerz.com/gwprocess/v3/api.php' => Http::response([
            'status' => 'SUCCESS',
            'sessionkey' => 'SESSION-DUPLICATE',
            'GatewayPageURL' => 'https://sandbox.sslcommerz.com/EasyCheckOut/test-session',
        ], 200),
        'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php*' => Http::response([
            'status' => 'VALID',
            'tran_id' => "cart_{$cart->id}_DUPLICATE",
            'bank_tran_id' => 'bank-tran-duplicate',
            'val_id' => 'val-duplicate',
            'value_a' => (string) $cart->id,
            'value_b' => 'sslcommerz_bkash',
            'currency_type' => 'BDT',
            'amount' => (string) $cart->base_grand_total,
        ], 200),
    ]);

    get(route('commerce-core.sslcommerz.redirect', ['code' => 'sslcommerz_bkash']));

    PaymentAttempt::query()->where('cart_id', $cart->id)->update([
        'merchant_tran_id' => "cart_{$cart->id}_DUPLICATE",
    ]);

    $payload = [
        'code' => 'sslcommerz_bkash',
        'val_id' => 'val-duplicate',
        'tran_id' => "cart_{$cart->id}_DUPLICATE",
        'value_a' => $cart->id,
        'value_b' => 'sslcommerz_bkash',
    ];

    get(route('commerce-core.sslcommerz.success', $payload))
        ->assertRedirect(route('shop.checkout.onepage.success'));

    get(route('commerce-core.sslcommerz.success', $payload))
        ->assertRedirect(route('shop.checkout.onepage.success'));

    $order = Order::query()->where('cart_id', $cart->id)->first();

    expect(Order::query()->where('cart_id', $cart->id)->count())->toBe(1)
        ->and(Invoice::query()->where('order_id', $order->id)->count())->toBe(1)
        ->and(OrderTransaction::query()->where('transaction_id', 'bank-tran-duplicate')->count())->toBe(1);

    $attempt = PaymentAttempt::query()->where('cart_id', $cart->id)->first();

    expect($attempt->status)->toBe('paid')
        ->and($attempt->callback_count)->toBe(2);
});

it('finalizes only once when ipn arrives before the browser success redirect', function () {
    $cart = $this->createCartWithItems('sslcommerz_bkash', [
        'base_currency_code' => 'BDT',
        'channel_currency_code' => 'BDT',
        'cart_currency_code' => 'BDT',
    ]);

    Http::fake([
        'https://sandbox.sslcommerz.com/gwprocess/v3/api.php' => Http::response([
            'status' => 'SUCCESS',
            'sessionkey' => 'SESSION-IPN',
            'GatewayPageURL' => 'https://sandbox.sslcommerz.com/EasyCheckOut/test-session',
        ], 200),
        'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php*' => Http::response([
            'status' => 'VALID',
            'tran_id' => "cart_{$cart->id}_IPN",
            'bank_tran_id' => 'bank-tran-ipn',
            'val_id' => 'val-ipn',
            'value_a' => (string) $cart->id,
            'value_b' => 'sslcommerz_bkash',
            'currency_type' => 'BDT',
            'amount' => (string) $cart->base_grand_total,
        ], 200),
    ]);

    get(route('commerce-core.sslcommerz.redirect', ['code' => 'sslcommerz_bkash']));

    PaymentAttempt::query()->where('cart_id', $cart->id)->update([
        'merchant_tran_id' => "cart_{$cart->id}_IPN",
    ]);

    post(route('commerce-core.sslcommerz.ipn', ['code' => 'sslcommerz_bkash']), [
        'val_id' => 'val-ipn',
        'tran_id' => "cart_{$cart->id}_IPN",
        'value_a' => (string) $cart->id,
        'value_b' => 'sslcommerz_bkash',
    ])->assertOk();

    get(route('commerce-core.sslcommerz.success', [
        'code' => 'sslcommerz_bkash',
        'val_id' => 'val-ipn',
        'tran_id' => "cart_{$cart->id}_IPN",
        'value_a' => $cart->id,
        'value_b' => 'sslcommerz_bkash',
    ]))->assertRedirect(route('shop.checkout.onepage.success'));

    $order = Order::query()->where('cart_id', $cart->id)->first();

    expect(Order::query()->where('cart_id', $cart->id)->count())->toBe(1)
        ->and(Invoice::query()->where('order_id', $order->id)->count())->toBe(1)
        ->and(OrderTransaction::query()->where('transaction_id', 'bank-tran-ipn')->count())->toBe(1);

    $attempt = PaymentAttempt::query()->where('cart_id', $cart->id)->first();

    expect($attempt->status)->toBe('paid')
        ->and($attempt->finalized_via)->toBe('ipn')
        ->and($attempt->ipn_count)->toBe(1)
        ->and($attempt->callback_count)->toBe(1);
});

it('returns customers to the payment step when validation amount does not match the cart total', function () {
    $cart = $this->createCartWithItems('sslcommerz_bkash', [
        'base_currency_code' => 'BDT',
        'channel_currency_code' => 'BDT',
        'cart_currency_code' => 'BDT',
    ]);

    Http::fake([
        'https://sandbox.sslcommerz.com/gwprocess/v3/api.php' => Http::response([
            'status' => 'SUCCESS',
            'sessionkey' => 'SESSION-MISMATCH',
            'GatewayPageURL' => 'https://sandbox.sslcommerz.com/EasyCheckOut/test-session',
        ], 200),
        'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php*' => Http::response([
            'status' => 'VALID',
            'tran_id' => "cart_{$cart->id}_MISMATCH",
            'bank_tran_id' => 'bank-tran-mismatch',
            'val_id' => 'val-mismatch',
            'value_a' => (string) $cart->id,
            'value_b' => 'sslcommerz_bkash',
            'currency_type' => 'BDT',
            'amount' => (string) ($cart->base_grand_total + 50),
        ], 200),
    ]);

    get(route('commerce-core.sslcommerz.redirect', ['code' => 'sslcommerz_bkash']));

    PaymentAttempt::query()->where('cart_id', $cart->id)->update([
        'merchant_tran_id' => "cart_{$cart->id}_MISMATCH",
    ]);

    get(route('commerce-core.sslcommerz.success', [
        'code' => 'sslcommerz_bkash',
        'val_id' => 'val-mismatch',
        'tran_id' => "cart_{$cart->id}_MISMATCH",
        'value_a' => $cart->id,
        'value_b' => 'sslcommerz_bkash',
    ]))
        ->assertRedirect(route('shop.checkout.onepage.index', ['step' => 'payment']))
        ->assertSessionHas('error');

    expect(Order::query()->where('cart_id', $cart->id)->exists())->toBeFalse();

    $attempt = PaymentAttempt::query()->where('cart_id', $cart->id)->first();

    expect($attempt)->not->toBeNull()
        ->and($attempt->status)->toBe('invalid')
        ->and(PaymentGatewayEvent::query()->where('payment_attempt_id', $attempt->id)->where('event_type', 'success_redirect')->count())->toBe(1);
});

it('returns customers to the checkout payment step when sslcommerz payment is cancelled', function () {
    $cart = $this->createCartWithItems('sslcommerz_bkash', [
        'base_currency_code' => 'BDT',
        'channel_currency_code' => 'BDT',
        'cart_currency_code' => 'BDT',
    ]);

    Http::fake([
        'https://sandbox.sslcommerz.com/gwprocess/v3/api.php' => Http::response([
            'status' => 'SUCCESS',
            'sessionkey' => 'SESSION-CANCEL',
            'GatewayPageURL' => 'https://sandbox.sslcommerz.com/EasyCheckOut/test-session',
        ], 200),
    ]);

    get(route('commerce-core.sslcommerz.redirect', ['code' => 'sslcommerz_bkash']));

    $attempt = PaymentAttempt::query()->where('cart_id', $cart->id)->first();

    get(route('commerce-core.sslcommerz.cancel', [
        'code' => 'sslcommerz_bkash',
        'tran_id' => $attempt->merchant_tran_id,
        'value_a' => $cart->id,
    ]))
        ->assertRedirect(route('shop.checkout.onepage.index', ['step' => 'payment']))
        ->assertSessionHas('error');

    expect($attempt->fresh()->status)->toBe('cancelled');
});
