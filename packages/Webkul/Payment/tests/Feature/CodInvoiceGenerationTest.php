<?php

use Webkul\Core\Models\CoreConfig;
use Webkul\Payment\Tests\Concerns\ProvidePaymentHelpers;
use Webkul\Sales\Models\Invoice;
use Webkul\Sales\Models\Order;

use function Pest\Laravel\postJson;

uses(ProvidePaymentHelpers::class);

beforeEach(function () {
    CoreConfig::query()->updateOrCreate(
        [
            'code' => 'sales.payment_methods.cashondelivery.active',
            'channel_code' => 'default',
        ],
        [
            'value' => '1',
        ],
    );
});

it('creates a cod invoice on order placement even when the legacy generate-invoice config is disabled', function () {
    CoreConfig::query()->updateOrCreate(
        [
            'code' => 'sales.payment_methods.cashondelivery.generate_invoice',
            'channel_code' => 'default',
        ],
        [
            'value' => '0',
        ],
    );

    $cart = $this->createCartWithItems('cashondelivery');

    postJson(route('shop.checkout.onepage.orders.store'))
        ->assertOk()
        ->assertJsonPath('data.redirect', true);

    $order = Order::query()->where('cart_id', $cart->id)->firstOrFail();

    expect(Invoice::query()->where('order_id', $order->id)->exists())->toBeTrue();

    $invoice = Invoice::query()->where('order_id', $order->id)->firstOrFail();

    expect($invoice->state)->toBe(Invoice::STATUS_PENDING_PAYMENT);
});
