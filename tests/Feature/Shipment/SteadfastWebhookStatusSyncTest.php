<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Platform\CommerceCore\Mail\Admin\Shipment\OperationalUpdateNotification as AdminOperationalUpdateNotification;
use Platform\CommerceCore\Mail\Shop\Shipment\OperationalUpdateNotification as ShopOperationalUpdateNotification;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Models\ShipmentEvent;
use Platform\CommerceCore\Models\ShipmentRecord;
use Tests\TestCase;
use Webkul\Checkout\Models\Cart;
use Webkul\Checkout\Models\CartAddress;
use Webkul\Checkout\Models\CartItem;
use Webkul\Checkout\Models\CartPayment;
use Webkul\Checkout\Models\CartShippingRate;
use Webkul\Core\Models\CoreConfig;
use Webkul\Customer\Models\Customer;
use Webkul\Customer\Models\CustomerAddress;
use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\OrderPayment;

use function Pest\Laravel\postJson;

uses(TestCase::class);

it('syncs a steadfast webhook payload by tracking number', function () {
    Mail::fake();
    setSteadfastWebhookNotificationConfig('sales.shipment_notifications.customer_out_for_delivery_email', 1);
    setSteadfastWebhookNotificationConfig('sales.shipment_notifications.admin_out_for_delivery_email', 1);

    $fixture = createSteadfastWebhookFixture();

    $response = postJson(
        route('commerce-core.webhooks.shipment-carriers.steadfast', $fixture['carrier']),
        [
            'tracking_code' => $fixture['shipmentRecord']->tracking_number,
            'status' => 'out_for_delivery',
        ],
        [
            'Authorization' => 'Bearer steadfast-hook-secret',
        ],
    );

    $response->assertOk()
        ->assertJson([
            'status' => 'ok',
            'shipment_record_id' => $fixture['shipmentRecord']->id,
            'external_status' => 'out_for_delivery',
        ]);

    $fixture['shipmentRecord']->refresh();

    $latestEvent = ShipmentEvent::query()
        ->where('shipment_record_id', $fixture['shipmentRecord']->id)
        ->latest('id')
        ->firstOrFail();

    expect($fixture['shipmentRecord']->status)->toBe(ShipmentRecord::STATUS_OUT_FOR_DELIVERY)
        ->and($fixture['shipmentRecord']->last_tracking_synced_at)->not->toBeNull()
        ->and($fixture['shipmentRecord']->last_tracking_sync_status)->toBe('synced')
        ->and($fixture['shipmentRecord']->last_tracking_sync_message)->toContain('Steadfast webhook synced')
        ->and($latestEvent->note)->toContain('Steadfast webhook mapped');

    Mail::assertQueued(ShopOperationalUpdateNotification::class);
    Mail::assertQueued(AdminOperationalUpdateNotification::class);
});

it('can resolve a steadfast webhook payload by order invoice when tracking number is absent', function () {
    Mail::fake();
    setSteadfastWebhookNotificationConfig('sales.shipment_notifications.customer_delivered_email', 1);
    setSteadfastWebhookNotificationConfig('sales.shipment_notifications.admin_delivered_email', 1);

    $fixture = createSteadfastWebhookFixture();

    postJson(
        route('commerce-core.webhooks.shipment-carriers.steadfast', $fixture['carrier']),
        [
            'invoice' => $fixture['order']->increment_id,
            'delivery_status' => 'delivered',
        ],
        [
            'Authorization' => 'Bearer steadfast-hook-secret',
        ],
    )->assertOk();

    $fixture['shipmentRecord']->refresh();

    expect($fixture['shipmentRecord']->status)->toBe(ShipmentRecord::STATUS_DELIVERED)
        ->and($fixture['shipmentRecord']->delivered_at)->not->toBeNull()
        ->and($fixture['shipmentRecord']->last_tracking_sync_status)->toBe('synced');

    Mail::assertQueued(ShopOperationalUpdateNotification::class);
    Mail::assertQueued(AdminOperationalUpdateNotification::class);
});

it('does not duplicate timeline events when the webhook repeats the current mapped status', function () {
    $fixture = createSteadfastWebhookFixture([
        'status' => ShipmentRecord::STATUS_OUT_FOR_DELIVERY,
    ]);

    ShipmentEvent::query()->create([
        'shipment_record_id' => $fixture['shipmentRecord']->id,
        'event_type' => ShipmentEvent::EVENT_STATUS_UPDATED,
        'status_after_event' => ShipmentRecord::STATUS_OUT_FOR_DELIVERY,
        'event_at' => now()->subMinute(),
        'note' => 'Existing out for delivery event.',
    ]);

    $initialEventCount = ShipmentEvent::query()
        ->where('shipment_record_id', $fixture['shipmentRecord']->id)
        ->count();

    postJson(
        route('commerce-core.webhooks.shipment-carriers.steadfast', $fixture['carrier']),
        [
            'tracking_number' => $fixture['shipmentRecord']->tracking_number,
            'status' => 'out_for_delivery',
        ],
        [
            'Authorization' => 'Bearer steadfast-hook-secret',
        ],
    )->assertOk()
        ->assertJson([
            'status' => 'ok',
        ]);

    $fixture['shipmentRecord']->refresh();

    expect(ShipmentEvent::query()->where('shipment_record_id', $fixture['shipmentRecord']->id)->count())->toBe($initialEventCount)
        ->and($fixture['shipmentRecord']->last_tracking_sync_message)->toContain('already matches');
});

it('rejects steadfast webhook requests with an invalid authorization token', function () {
    $fixture = createSteadfastWebhookFixture();

    postJson(
        route('commerce-core.webhooks.shipment-carriers.steadfast', $fixture['carrier']),
        [
            'tracking_code' => $fixture['shipmentRecord']->tracking_number,
            'status' => 'out_for_delivery',
        ],
        [
            'Authorization' => 'Bearer wrong-secret',
        ],
    )->assertUnauthorized()
        ->assertJson([
            'status' => 'unauthorized',
        ]);

    $fixture['shipmentRecord']->refresh();

    expect($fixture['shipmentRecord']->status)->toBe(ShipmentRecord::STATUS_IN_TRANSIT);
});

it('accepts unmapped steadfast webhook statuses without changing shipment state', function () {
    $fixture = createSteadfastWebhookFixture();

    postJson(
        route('commerce-core.webhooks.shipment-carriers.steadfast', $fixture['carrier']),
        [
            'tracking_code' => $fixture['shipmentRecord']->tracking_number,
            'status' => 'unknown_approval_pending',
        ],
        [
            'Authorization' => 'Bearer steadfast-hook-secret',
        ],
    )->assertStatus(202)
        ->assertJson([
            'status' => 'accepted',
            'shipment_record_id' => $fixture['shipmentRecord']->id,
            'external_status' => 'unknown_approval_pending',
        ]);

    $fixture['shipmentRecord']->refresh();

    expect($fixture['shipmentRecord']->status)->toBe(ShipmentRecord::STATUS_IN_TRANSIT)
        ->and($fixture['shipmentRecord']->last_tracking_sync_status)->toBe('pending')
        ->and($fixture['shipmentRecord']->last_tracking_sync_message)->toContain('no local status mapping exists yet');
});

function createSteadfastWebhookFixture(array $shipmentOverrides = []): array
{
    $product = (new ProductFaker([
        'attributes' => [
            5 => 'new',
        ],
        'attribute_value' => [
            'new' => [
                'boolean_value' => true,
            ],
        ],
    ]))
        ->getSimpleProductFactory()
        ->create();

    $customer = Customer::factory()->create();

    $cart = Cart::factory()->create([
        'customer_id' => $customer->id,
        'customer_first_name' => $customer->first_name,
        'customer_last_name' => $customer->last_name,
        'customer_email' => $customer->email,
        'is_guest' => 0,
    ]);

    $additional = [
        'product_id' => $product->id,
        'rating' => '0',
        'is_buy_now' => '0',
        'quantity' => '1',
    ];

    CartItem::factory()->create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'sku' => $product->sku,
        'quantity' => $additional['quantity'],
        'name' => $product->name,
        'price' => $convertedPrice = core()->convertPrice($price = $product->price),
        'base_price' => $price,
        'total' => $convertedPrice * $additional['quantity'],
        'base_total' => $price * $additional['quantity'],
        'weight' => $product->weight ?? 0,
        'total_weight' => ($product->weight ?? 0) * $additional['quantity'],
        'base_total_weight' => ($product->weight ?? 0) * $additional['quantity'],
        'type' => $product->type,
        'additional' => $additional,
    ]);

    CustomerAddress::factory()->create([
        'cart_id' => $cart->id,
        'customer_id' => $customer->id,
        'address_type' => CustomerAddress::ADDRESS_TYPE,
    ]);

    $cartBillingAddress = CartAddress::factory()->create([
        'cart_id' => $cart->id,
        'customer_id' => $customer->id,
        'address_type' => CartAddress::ADDRESS_TYPE_BILLING,
    ]);

    $cartShippingAddress = CartAddress::factory()->create([
        'cart_id' => $cart->id,
        'customer_id' => $customer->id,
        'address_type' => CartAddress::ADDRESS_TYPE_SHIPPING,
    ]);

    CartPayment::factory()->create([
        'cart_id' => $cart->id,
        'method' => $paymentMethod = 'cashondelivery',
        'method_title' => core()->getConfigData('sales.payment_methods.'.$paymentMethod.'.title'),
    ]);

    CartShippingRate::factory()->create([
        'carrier' => 'free',
        'carrier_title' => 'Free shipping',
        'method' => 'free_free',
        'method_title' => 'Free Shipping',
        'method_description' => 'Free Shipping',
        'cart_address_id' => $cartShippingAddress->id,
    ]);

    $order = Order::factory()->create([
        'cart_id' => $cart->id,
        'customer_id' => $customer->id,
        'customer_email' => $customer->email,
        'customer_first_name' => $customer->first_name,
        'customer_last_name' => $customer->last_name,
        'status' => Order::STATUS_PROCESSING,
    ]);

    OrderItem::factory()->create([
        'product_id' => $product->id,
        'order_id' => $order->id,
        'sku' => $product->sku,
        'type' => $product->type,
        'name' => $product->name,
    ]);

    OrderAddress::factory()->create([
        ...Arr::except($cartBillingAddress->toArray(), ['id', 'created_at', 'updated_at']),
        'cart_id' => $cart->id,
        'customer_id' => $customer->id,
        'address_type' => OrderAddress::ADDRESS_TYPE_BILLING,
        'order_id' => $order->id,
    ]);

    OrderAddress::factory()->create([
        ...Arr::except($cartShippingAddress->toArray(), ['id', 'created_at', 'updated_at']),
        'cart_id' => $cart->id,
        'customer_id' => $customer->id,
        'address_type' => OrderAddress::ADDRESS_TYPE_SHIPPING,
        'order_id' => $order->id,
    ]);

    OrderPayment::factory()->create([
        'order_id' => $order->id,
    ]);

    $carrier = ShipmentCarrier::query()->create([
        'code' => 'steadfast',
        'name' => 'Steadfast Courier',
        'integration_driver' => 'steadfast',
        'tracking_sync_enabled' => true,
        'webhook_secret' => 'steadfast-hook-secret',
        'is_active' => true,
    ]);

    $shipmentRecord = ShipmentRecord::query()->create(array_merge([
        'order_id' => $order->id,
        'shipment_carrier_id' => $carrier->id,
        'status' => ShipmentRecord::STATUS_IN_TRANSIT,
        'carrier_name_snapshot' => $carrier->name,
        'tracking_number' => 'TRACK-WEBHOOK-001',
        'recipient_name' => $order->customer_full_name,
        'recipient_phone' => $order->shipping_address->phone,
        'recipient_address' => $order->shipping_address->address,
        'handed_over_at' => now()->subDay(),
    ], $shipmentOverrides));

    return [
        'order' => $order->fresh(['addresses', 'items', 'payment']),
        'carrier' => $carrier,
        'shipmentRecord' => $shipmentRecord,
    ];
}

function setSteadfastWebhookNotificationConfig(string $code, mixed $value): void
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
