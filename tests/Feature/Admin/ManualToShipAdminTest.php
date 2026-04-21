<?php

use Illuminate\Support\Arr;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Models\ShipmentEvent;
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
use Webkul\Sales\Models\Shipment;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(AdminTestCase::class);

beforeEach(function () {
    CoreConfig::query()->updateOrCreate(
        [
            'code' => 'sales.shipping_workflow.shipping_mode',
            'channel_code' => 'default',
        ],
        [
            'value' => 'manual_basic',
        ],
    );
});

it('shows the basic to ship page with business-facing booking columns', function () {
    $fixture = createManualToShipFixture();

    ShipmentCarrier::query()->create([
        'code' => 'steadfast',
        'name' => 'Steadfast Courier',
        'supports_cod' => true,
        'is_active' => true,
    ]);

    $this->loginAsAdmin();

    get(route('admin.sales.to-ship.index'))
        ->assertOk()
        ->assertSeeText('To Ship')
        ->assertSeeText((string) $fixture['order']->increment_id)
        ->assertSeeText('COD')
        ->assertSeeText('Ready')
        ->assertSeeText('Book Shipment');
});

it('books a shipment from to ship and moves it into the in delivery queue', function () {
    $fixture = createManualToShipFixture();

    $carrier = ShipmentCarrier::query()->create([
        'code' => 'pathao',
        'name' => 'Pathao Courier',
        'tracking_url_template' => 'https://pathao.example/track/{tracking_number}',
        'supports_cod' => true,
        'is_active' => true,
    ]);

    $this->loginAsAdmin();

    post(route('admin.sales.shipments.store', $fixture['order']->id), [
        'redirect_to' => 'to_ship',
        'booking_order_id' => $fixture['order']->id,
        'shipment' => [
            'source' => $fixture['source'],
            'items' => $fixture['items'],
            'carrier_id' => $carrier->id,
            'track_number' => 'TRACK-BASIC-001',
            'public_tracking_url' => 'https://pathao.example/track/TRACK-BASIC-001',
            'note' => 'Packed and handed over to the courier desk.',
        ],
    ])->assertRedirect(route('admin.sales.to-ship.index'));

    $nativeShipment = Shipment::query()->latest('id')->firstOrFail();
    $shipmentRecord = ShipmentRecord::query()->where('native_shipment_id', $nativeShipment->id)->firstOrFail();
    $initialEvent = ShipmentEvent::query()
        ->where('shipment_record_id', $shipmentRecord->id)
        ->latest('id')
        ->firstOrFail();

    expect($shipmentRecord->shipment_carrier_id)->toBe($carrier->id)
        ->and($shipmentRecord->tracking_number)->toBe('TRACK-BASIC-001')
        ->and($shipmentRecord->public_tracking_url)->toBe('https://pathao.example/track/TRACK-BASIC-001')
        ->and($shipmentRecord->notes)->toBe('Packed and handed over to the courier desk.')
        ->and($initialEvent->note)->toBe('Packed and handed over to the courier desk.');

    get(route('admin.sales.to-ship.index'))
        ->assertOk()
        ->assertDontSeeText((string) $fixture['order']->increment_id);

    get(route('admin.sales.shipped-orders.index'))
        ->assertOk()
        ->assertSeeText('In Delivery')
        ->assertSeeText((string) $fixture['order']->increment_id)
        ->assertSeeText('Pathao Courier')
        ->assertSeeText('TRACK-BASIC-001');
});

function createManualToShipFixture(): array
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
        'method' => 'cashondelivery',
        'method_title' => 'Cash on Delivery',
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
        'method' => 'cashondelivery',
        'method_title' => 'Cash on Delivery',
    ]);

    $order = $order->fresh(['addresses', 'items', 'payment', 'channel.inventory_sources']);

    $source = (int) $order->channel->inventory_sources->firstOrFail()->id;
    $items = [];

    foreach ($order->items as $item) {
        $items[$item->id][$source] = (float) $item->qty_to_ship;
    }

    return [
        'order' => $order,
        'source' => $source,
        'items' => $items,
    ];
}
