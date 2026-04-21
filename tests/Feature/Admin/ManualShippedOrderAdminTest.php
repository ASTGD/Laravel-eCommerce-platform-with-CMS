<?php

use Illuminate\Support\Arr;
use Platform\CommerceCore\Models\CodSettlement;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Models\ShipmentRecord;
use Webkul\Admin\Tests\AdminTestCase;
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

use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(AdminTestCase::class);

beforeEach(function () {
    setManualShippedOrdersShippingMode('manual_basic');
});

it('shows a simple in delivery list in manual basic mode', function () {
    $fixture = createManualShippedOrderFixture();

    $carrier = ShipmentCarrier::query()->create([
        'code' => 'steadfast',
        'name' => 'Steadfast Courier',
        'tracking_url_template' => 'https://tracking.example/{tracking_number}',
        'supports_cod' => true,
        'is_active' => true,
    ]);

    ShipmentRecord::query()->create([
        'order_id' => $fixture['order']->id,
        'shipment_carrier_id' => $carrier->id,
        'status' => ShipmentRecord::STATUS_HANDED_TO_CARRIER,
        'carrier_name_snapshot' => $carrier->name,
        'tracking_number' => 'TRACK-MANUAL-001',
        'recipient_name' => $fixture['order']->customer_full_name,
        'recipient_phone' => $fixture['order']->shipping_address->phone,
        'recipient_address' => $fixture['order']->shipping_address->address,
        'destination_country' => $fixture['order']->shipping_address->country,
        'destination_region' => $fixture['order']->shipping_address->state,
        'destination_city' => $fixture['order']->shipping_address->city,
        'cod_amount_expected' => 1499,
        'handed_over_at' => now()->subHour(),
    ]);

    $this->loginAsAdmin();

    get(route('admin.sales.shipped-orders.index'))
        ->assertOk()
        ->assertSeeText('In Delivery')
        ->assertSeeText('Booked Date')
        ->assertSeeText('Shipment Status')
        ->assertSeeText('All couriers')
        ->assertSeeText((string) $fixture['order']->increment_id)
        ->assertSeeText('Steadfast Courier')
        ->assertSeeText('TRACK-MANUAL-001')
        ->assertSeeText('Open tracking link')
        ->assertSeeText('Mark Delivered');
});

it('filters the in delivery queue by courier', function () {
    $fixture = createManualShippedOrderFixture();

    $pathaoCarrier = ShipmentCarrier::query()->create([
        'code' => 'pathao',
        'name' => 'Pathao Courier',
        'supports_cod' => true,
        'is_active' => true,
    ]);

    $steadfastCarrier = ShipmentCarrier::query()->create([
        'code' => 'steadfast',
        'name' => 'Steadfast Courier',
        'supports_cod' => true,
        'is_active' => true,
    ]);

    ShipmentRecord::query()->create([
        'order_id' => $fixture['order']->id,
        'shipment_carrier_id' => $pathaoCarrier->id,
        'status' => ShipmentRecord::STATUS_IN_TRANSIT,
        'carrier_name_snapshot' => $pathaoCarrier->name,
        'tracking_number' => 'TRACK-PATHAO-001',
        'recipient_name' => $fixture['order']->customer_full_name,
        'recipient_phone' => $fixture['order']->shipping_address->phone,
        'recipient_address' => $fixture['order']->shipping_address->address,
        'destination_country' => $fixture['order']->shipping_address->country,
        'destination_region' => $fixture['order']->shipping_address->state,
        'destination_city' => $fixture['order']->shipping_address->city,
        'handed_over_at' => now()->subHours(2),
    ]);

    ShipmentRecord::query()->create([
        'order_id' => $fixture['order']->id,
        'shipment_carrier_id' => $steadfastCarrier->id,
        'status' => ShipmentRecord::STATUS_OUT_FOR_DELIVERY,
        'carrier_name_snapshot' => $steadfastCarrier->name,
        'tracking_number' => 'TRACK-STEADFAST-001',
        'recipient_name' => $fixture['order']->customer_full_name,
        'recipient_phone' => $fixture['order']->shipping_address->phone,
        'recipient_address' => $fixture['order']->shipping_address->address,
        'destination_country' => $fixture['order']->shipping_address->country,
        'destination_region' => $fixture['order']->shipping_address->state,
        'destination_city' => $fixture['order']->shipping_address->city,
        'handed_over_at' => now()->subHour(),
    ]);

    $this->loginAsAdmin();

    get(route('admin.sales.shipped-orders.index', ['carrier_id' => $pathaoCarrier->id]))
        ->assertOk()
        ->assertSeeText('Pathao Courier')
        ->assertSeeText('TRACK-PATHAO-001')
        ->assertDontSeeText('TRACK-STEADFAST-001');
});

it('marks a manual shipped order as delivered and moves hidden cod state to collected by carrier', function () {
    $fixture = createManualShippedOrderFixture();

    $carrier = ShipmentCarrier::query()->create([
        'code' => 'manual-courier',
        'name' => 'Local Courier',
        'supports_cod' => true,
        'is_active' => true,
    ]);

    $shipmentRecord = ShipmentRecord::query()->create([
        'order_id' => $fixture['order']->id,
        'shipment_carrier_id' => $carrier->id,
        'status' => ShipmentRecord::STATUS_IN_TRANSIT,
        'carrier_name_snapshot' => $carrier->name,
        'tracking_number' => 'TRACK-MANUAL-DELIVERED',
        'recipient_name' => $fixture['order']->customer_full_name,
        'recipient_phone' => $fixture['order']->shipping_address->phone,
        'recipient_address' => $fixture['order']->shipping_address->address,
        'destination_country' => $fixture['order']->shipping_address->country,
        'destination_region' => $fixture['order']->shipping_address->state,
        'destination_city' => $fixture['order']->shipping_address->city,
        'cod_amount_expected' => 1799,
        'carrier_fee_amount' => 120,
        'cod_fee_amount' => 0,
        'return_fee_amount' => 0,
        'net_remittable_amount' => 1679,
        'handed_over_at' => now()->subHours(4),
    ]);

    $this->loginAsAdmin();

    post(route('admin.sales.shipped-orders.mark-delivered', $shipmentRecord))
        ->assertRedirect(route('admin.sales.shipped-orders.index'));

    $shipmentRecord->refresh();
    $codSettlement = CodSettlement::query()
        ->where('shipment_record_id', $shipmentRecord->id)
        ->firstOrFail();

    expect($shipmentRecord->status)->toBe(ShipmentRecord::STATUS_DELIVERED)
        ->and($shipmentRecord->delivered_at)->not->toBeNull()
        ->and((float) $shipmentRecord->cod_amount_collected)->toBe(1799.0)
        ->and($codSettlement->status)->toBe(CodSettlement::STATUS_COLLECTED_BY_CARRIER)
        ->and((float) $codSettlement->collected_amount)->toBe(1799.0)
        ->and($codSettlement->collected_at)->not->toBeNull();

    get(route('admin.sales.shipped-orders.index'))
        ->assertOk()
        ->assertDontSeeText('TRACK-MANUAL-DELIVERED');
});

function setManualShippedOrdersShippingMode(string $mode): void
{
    CoreConfig::query()->updateOrCreate(
        [
            'code' => 'sales.shipping_workflow.shipping_mode',
            'channel_code' => 'default',
        ],
        [
            'value' => $mode,
        ],
    );
}

function createManualShippedOrderFixture(): array
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

    return [
        'order' => $order->fresh(['addresses', 'items', 'payment']),
    ];
}
