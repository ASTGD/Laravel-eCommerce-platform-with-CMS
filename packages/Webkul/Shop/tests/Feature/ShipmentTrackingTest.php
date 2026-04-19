<?php

use Platform\CommerceCore\Models\ShipmentEvent;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Models\ShipmentRecord;
use Webkul\Checkout\Models\Cart;
use Webkul\Checkout\Models\CartAddress;
use Webkul\Checkout\Models\CartItem;
use Webkul\Checkout\Models\CartPayment;
use Webkul\Customer\Models\Customer;
use Webkul\Customer\Models\CustomerAddress;
use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\OrderPayment;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('shows the public shipment tracking page', function () {
    get(route('shop.shipment-tracking.index'))
        ->assertOk()
        ->assertSeeText('Track Your Shipment')
        ->assertSeeText('Order / Tracking Number');
});

it('looks up a shipment publicly by tracking number and phone', function () {
    $fixture = createPublicShipmentTrackingFixture();

    post(route('shop.shipment-tracking.lookup'), [
        'reference' => $fixture['shipment']->tracking_number,
        'phone' => $fixture['phone'],
    ])
        ->assertOk()
        ->assertSeeText('Shipment Tracking Result')
        ->assertSeeText($fixture['shipment']->tracking_number)
        ->assertSeeText('Steadfast Courier')
        ->assertSeeText('Track on carrier site')
        ->assertSeeText('Out for Delivery')
        ->assertSeeText('Arrived at destination hub')
        ->assertSee('https://carrier.example/track/TRACK-PUBLIC-001', false);
});

it('looks up a shipment publicly by order number and phone', function () {
    $fixture = createPublicShipmentTrackingFixture();

    post(route('shop.shipment-tracking.lookup'), [
        'reference' => (string) $fixture['order']->increment_id,
        'phone' => $fixture['phone'],
    ])
        ->assertOk()
        ->assertSeeText('Order #'.$fixture['order']->increment_id)
        ->assertSeeText($fixture['shipment']->tracking_number)
        ->assertSeeText('Shipment created');
});

it('does not show shipment details when the phone does not match', function () {
    $fixture = createPublicShipmentTrackingFixture();

    post(route('shop.shipment-tracking.lookup'), [
        'reference' => $fixture['shipment']->tracking_number,
        'phone' => '01999999999',
    ])
        ->assertOk()
        ->assertSeeText('No shipment matched that reference and phone number.')
        ->assertDontSeeText($fixture['shipment']->tracking_number);
});

function createPublicShipmentTrackingFixture(): array
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

    CartAddress::factory()->create([
        'cart_id' => $cart->id,
        'customer_id' => $customer->id,
        'address_type' => CartAddress::ADDRESS_TYPE_BILLING,
    ]);

    CartAddress::factory()->create([
        'cart_id' => $cart->id,
        'customer_id' => $customer->id,
        'address_type' => CartAddress::ADDRESS_TYPE_SHIPPING,
    ]);

    CartPayment::factory()->create([
        'cart_id' => $cart->id,
        'method' => $paymentMethod = 'cashondelivery',
        'method_title' => core()->getConfigData('sales.payment_methods.'.$paymentMethod.'.title'),
    ]);

    $order = Order::factory()->create([
        'cart_id' => $cart->id,
        'customer_id' => $customer->id,
        'customer_email' => $customer->email,
        'customer_first_name' => $customer->first_name,
        'customer_last_name' => $customer->last_name,
    ]);

    OrderItem::factory()->create([
        'product_id' => $product->id,
        'order_id' => $order->id,
        'sku' => $product->sku,
        'type' => $product->type,
        'name' => $product->name,
    ]);

    OrderAddress::factory()->create([
        'cart_id' => $cart->id,
        'customer_id' => $customer->id,
        'address_type' => OrderAddress::ADDRESS_TYPE_BILLING,
    ]);

    $shippingAddress = OrderAddress::factory()->create([
        'cart_id' => $cart->id,
        'customer_id' => $customer->id,
        'address_type' => OrderAddress::ADDRESS_TYPE_SHIPPING,
        'phone' => '01712345678',
    ]);

    OrderPayment::factory()->create([
        'order_id' => $order->id,
        'method' => 'cashondelivery',
    ]);

    $carrier = ShipmentCarrier::query()->create([
        'code' => 'steadfast',
        'name' => 'Steadfast Courier',
        'tracking_url_template' => 'https://carrier.example/track/{tracking_number}',
        'supports_cod' => true,
        'default_cod_fee_type' => 'flat',
        'is_active' => true,
    ]);

    $shipment = ShipmentRecord::query()->create([
        'order_id' => $order->id,
        'shipment_carrier_id' => $carrier->id,
        'status' => ShipmentRecord::STATUS_OUT_FOR_DELIVERY,
        'carrier_name_snapshot' => 'Steadfast Courier',
        'tracking_number' => 'TRACK-PUBLIC-001',
        'recipient_name' => $order->customer_full_name,
        'recipient_phone' => $shippingAddress->phone,
        'recipient_address' => $shippingAddress->address,
        'handed_over_at' => now()->subDay(),
    ]);

    ShipmentEvent::query()->create([
        'shipment_record_id' => $shipment->id,
        'event_type' => ShipmentEvent::EVENT_SHIPMENT_CREATED,
        'status_after_event' => ShipmentRecord::STATUS_HANDED_TO_CARRIER,
        'event_at' => now()->subDay(),
    ]);

    ShipmentEvent::query()->create([
        'shipment_record_id' => $shipment->id,
        'event_type' => ShipmentEvent::EVENT_ARRIVED_DESTINATION_HUB,
        'status_after_event' => ShipmentRecord::STATUS_IN_TRANSIT,
        'event_at' => now()->subHours(4),
    ]);

    ShipmentEvent::query()->create([
        'shipment_record_id' => $shipment->id,
        'event_type' => ShipmentEvent::EVENT_STATUS_UPDATED,
        'status_after_event' => ShipmentRecord::STATUS_OUT_FOR_DELIVERY,
        'event_at' => now()->subHour(),
    ]);

    return [
        'order' => $order,
        'shipment' => $shipment,
        'phone' => $shippingAddress->phone,
    ];
}
