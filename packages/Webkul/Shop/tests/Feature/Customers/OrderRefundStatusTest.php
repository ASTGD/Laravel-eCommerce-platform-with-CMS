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
        'sales.payment_methods.mode.channel' => 'custom',
        'sales.payment_methods.sslcommerz_gateway.sandbox' => 1,
        'sales.payment_methods.sslcommerz_gateway.store_id' => 'test_store',
        'sales.payment_methods.sslcommerz_gateway.store_password' => 'test_password',
        'sales.payment_methods.sslcommerz_gateway.request_timeout' => 30,
        'sales.payment_methods.sslcommerz_gateway.strict_amount_validation' => 1,
        'sales.payment_methods.sslcommerz_gateway.log_payloads' => 1,
        'sales.payment_methods.sslcommerz_card.active' => 1,
    ] as $code => $value) {
        CoreConfig::query()->updateOrCreate(
            ['code' => $code, 'channel_code' => 'default'],
            ['value' => (string) $value],
        );
    }
}

function createPaidSslOrderForShopRefundTest($test): Order
{
    $cart = $test->createCartWithItems('sslcommerz_card', [
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
            'value_b' => 'sslcommerz_card',
            'currency_type' => 'BDT',
            'amount' => (string) $cart->base_grand_total,
        ], 200),
    ]);

    get(route('commerce-core.sslcommerz.success', [
        'code' => 'sslcommerz_card',
        'val_id' => "val-{$cart->id}",
        'tran_id' => "cart_{$cart->id}_SHOP_REFUND",
        'value_a' => $cart->id,
        'value_b' => 'sslcommerz_card',
    ]))->assertRedirect(route('shop.checkout.onepage.success'));

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
        'method_code' => 'sslcommerz_card',
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
