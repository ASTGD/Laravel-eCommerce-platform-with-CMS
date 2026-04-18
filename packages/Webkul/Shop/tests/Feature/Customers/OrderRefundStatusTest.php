<?php

use Illuminate\Support\Facades\Http;
use Platform\CommerceCore\Models\PaymentRefund;
use Webkul\Core\Models\CoreConfig;
use Webkul\Payment\Tests\Concerns\ProvidePaymentHelpers;
use Webkul\Sales\Models\Order;

use function Pest\Laravel\get;

uses(ProvidePaymentHelpers::class);

function configureShopRefundGateway(): void
{
    foreach ([
        'sales.payment_methods.sslcommerz_gateway.sandbox' => 1,
        'sales.payment_methods.sslcommerz_gateway.store_id' => 'test_store',
        'sales.payment_methods.sslcommerz_gateway.store_password' => 'test_password',
        'sales.payment_methods.sslcommerz_gateway.request_timeout' => 30,
        'sales.payment_methods.sslcommerz_gateway.strict_amount_validation' => 1,
        'sales.payment_methods.sslcommerz_gateway.log_payloads' => 1,
        'sales.payment_methods.sslcommerz.active' => 1,
    ] as $code => $value) {
        CoreConfig::query()->updateOrCreate(
            ['code' => $code, 'channel_code' => 'default'],
            ['value' => (string) $value],
        );
    }
}

function configureShopBkashRefundGateway(): void
{
    foreach ([
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

function createPaidBkashOrderForShopRefundTest($test): Order
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
            'id_token' => 'shop-refund-id-token',
            'refresh_token' => 'shop-refund-refresh-token',
            'expires_in' => 3600,
        ], 200),
        'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/create' => Http::response([
            'statusCode' => '0000',
            'statusMessage' => 'Successful',
            'paymentID' => "shop-refund-pay-{$cart->id}",
            'bkashURL' => "https://sandbox.payment.bkash.com/checkout/shop-refund-pay-{$cart->id}",
            'transactionStatus' => 'Initiated',
        ], 200),
        'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/execute' => Http::response([
            'statusCode' => '0000',
            'statusMessage' => 'Successful',
            'paymentID' => "shop-refund-pay-{$cart->id}",
            'trxID' => "shop-refund-trx-{$cart->id}",
            'transactionStatus' => 'Completed',
            'amount' => (string) $cart->base_grand_total,
            'currency' => 'BDT',
            'merchantInvoiceNumber' => "bkash_invoice_{$cart->id}_shop_refund",
        ], 200),
    ]);

    get(route('commerce-core.bkash.redirect', ['code' => 'bkash']));

    \Platform\CommerceCore\Models\PaymentAttempt::query()->where('cart_id', $cart->id)->update([
        'merchant_tran_id' => "bkash_invoice_{$cart->id}_shop_refund",
        'session_key' => "shop-refund-pay-{$cart->id}",
    ]);

    get(route('commerce-core.bkash.callback', [
        'code' => 'bkash',
        'status' => 'success',
        'paymentID' => "shop-refund-pay-{$cart->id}",
    ]))->assertRedirect(route('shop.checkout.success', ['order' => Order::query()->where('cart_id', $cart->id)->firstOrFail()->id]));

    return Order::query()->where('cart_id', $cart->id)->firstOrFail();
}

function createPaidSslOrderForShopRefundTest($test): Order
{
    $cart = $test->createCartWithItems('sslcommerz', [
        'base_currency_code' => 'BDT',
        'channel_currency_code' => 'BDT',
        'cart_currency_code' => 'BDT',
    ]);

    Http::fake([
        'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php*' => Http::response([
            'status' => 'VALID',
            'tran_id' => "cart_{$cart->id}_SHOP_REFUND",
            'bank_tran_id' => "bank-tran-{$cart->id}",
            'val_id' => "val-{$cart->id}",
            'value_a' => (string) $cart->id,
            'value_b' => 'sslcommerz',
            'currency_type' => 'BDT',
            'amount' => (string) $cart->base_grand_total,
        ], 200),
    ]);

    get(route('commerce-core.sslcommerz.success', [
        'code' => 'sslcommerz',
        'val_id' => "val-{$cart->id}",
        'tran_id' => "cart_{$cart->id}_SHOP_REFUND",
        'value_a' => $cart->id,
        'value_b' => 'sslcommerz',
    ]))->assertRedirect(route('shop.checkout.success', ['order' => Order::query()->where('cart_id', $cart->id)->firstOrFail()->id]));

    return Order::query()->where('cart_id', $cart->id)->firstOrFail();
}

beforeEach(function () {
    configureShopRefundGateway();
});

it('shows sslcommerz refund history on the customer order detail page', function () {
    $order = createPaidSslOrderForShopRefundTest($this);

    PaymentRefund::query()->create([
        'payment_attempt_id' => \Platform\CommerceCore\Models\PaymentAttempt::query()->where('order_id', $order->id)->latest('id')->value('id'),
        'order_id' => $order->id,
        'provider' => 'sslcommerz',
        'method_code' => 'sslcommerz',
        'merchant_tran_id' => 'merchant-refund-shop',
        'gateway_tran_id' => 'gateway-refund-shop',
        'gateway_refund_ref' => 'refund-ref-shop',
        'requested_amount' => 10,
        'currency' => 'BDT',
        'status' => 'pending',
        'requested_at' => now(),
    ]);

    $this->loginAsCustomer($order->customer);

    get(route('shop.customers.account.orders.view', $order->id))
        ->assertOk()
        ->assertSeeText('Refund History')
        ->assertSeeText('refund-ref-shop')
        ->assertSeeText('PENDING');
});

it('shows bkash refund history on the customer order detail page', function () {
    configureShopBkashRefundGateway();

    $order = createPaidBkashOrderForShopRefundTest($this);

    PaymentRefund::query()->create([
        'payment_attempt_id' => \Platform\CommerceCore\Models\PaymentAttempt::query()->where('order_id', $order->id)->latest('id')->value('id'),
        'order_id' => $order->id,
        'provider' => 'bkash',
        'method_code' => 'bkash',
        'merchant_tran_id' => 'merchant-refund-shop-bkash',
        'gateway_tran_id' => 'gateway-refund-shop-bkash',
        'gateway_refund_ref' => 'bkash-refund-shop',
        'requested_amount' => 10,
        'currency' => 'BDT',
        'status' => 'pending',
        'requested_at' => now(),
    ]);

    $this->loginAsCustomer($order->customer);

    get(route('shop.customers.account.orders.view', $order->id))
        ->assertOk()
        ->assertSeeText('Refund History')
        ->assertSeeText('bkash-refund-shop')
        ->assertSeeText('PENDING');
});
