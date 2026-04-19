<?php

use Illuminate\Support\Arr;
use Platform\CommerceCore\Models\CodSettlement;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Webkul\Admin\Tests\AdminTestCase;
use Webkul\Checkout\Models\Cart;
use Webkul\Checkout\Models\CartAddress;
use Webkul\Checkout\Models\CartItem;
use Webkul\Checkout\Models\CartPayment;
use Webkul\Checkout\Models\CartShippingRate;
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
use function Pest\Laravel\postJson;

uses(AdminTestCase::class);

it('auto creates an expected cod settlement from native shipment creation', function () {
    $fixture = createCodSettlementFixture();

    $this->loginAsAdmin();

    postJson(route('admin.sales.shipments.store', $fixture['order']->id), [
        'shipment' => [
            'source' => $fixture['source'],
            'items' => $fixture['items'],
            'carrier_title' => $fixture['carrier_title'],
            'track_number' => $fixture['track_number'],
        ],
    ])->assertRedirect(route('admin.sales.orders.view', $fixture['order']->id));

    $nativeShipment = Shipment::query()->latest('id')->firstOrFail();
    $settlement = CodSettlement::query()
        ->whereHas('shipmentRecord', fn ($query) => $query->where('native_shipment_id', $nativeShipment->id))
        ->firstOrFail();

    expect($settlement->status)->toBe(CodSettlement::STATUS_EXPECTED)
        ->and((string) $settlement->expected_amount)->toBe((string) number_format((float) $fixture['order']->base_grand_total, 2, '.', ''))
        ->and((string) $settlement->remitted_amount)->toBe('0.00');
});

it('shows the cod settlements index and detail screens', function () {
    $settlement = createSyncedCodSettlement();

    $this->loginAsAdmin();

    get(route('admin.sales.cod-settlements.index'))
        ->assertOk()
        ->assertSeeText('COD Settlements');

    get(route('admin.sales.cod-settlements.view', $settlement))
        ->assertOk()
        ->assertSeeText('COD Settlement')
        ->assertSeeText($settlement->order->increment_id)
        ->assertSeeText('Financial Snapshot')
        ->assertSeeText('Reconciliation Health');
});

it('updates a cod settlement from admin', function () {
    $settlement = createSyncedCodSettlement();

    $this->loginAsAdmin();

    post(route('admin.sales.cod-settlements.update', $settlement), [
        'status' => CodSettlement::STATUS_REMITTED,
        'collected_amount' => $settlement->expected_amount,
        'remitted_amount' => $settlement->net_amount,
        'short_amount' => 0,
        'disputed_amount' => 0,
        'carrier_fee_amount' => $settlement->carrier_fee_amount,
        'cod_fee_amount' => $settlement->cod_fee_amount,
        'return_fee_amount' => $settlement->return_fee_amount,
        'notes' => 'Courier payout received and reconciled.',
    ])->assertRedirect(route('admin.sales.cod-settlements.view', $settlement));

    $settlement->refresh();

    expect($settlement->status)->toBe(CodSettlement::STATUS_REMITTED)
        ->and($settlement->remitted_at)->not->toBeNull()
        ->and((string) $settlement->remitted_amount)->toBe((string) number_format((float) $settlement->net_amount, 2, '.', ''))
        ->and($settlement->notes)->toBe('Courier payout received and reconciled.');
});

it('blocks settled status when the remitted amount does not cover the net amount', function () {
    $settlement = createSyncedCodSettlement();

    $this->loginAsAdmin();

    post(route('admin.sales.cod-settlements.update', $settlement), [
        'status' => CodSettlement::STATUS_SETTLED,
        'collected_amount' => $settlement->expected_amount,
        'remitted_amount' => (float) $settlement->net_amount - 10,
        'short_amount' => 0,
        'disputed_amount' => 0,
        'carrier_fee_amount' => $settlement->carrier_fee_amount,
        'cod_fee_amount' => $settlement->cod_fee_amount,
        'return_fee_amount' => $settlement->return_fee_amount,
    ])
        ->assertSessionHasErrors(['remitted_amount']);

    expect($settlement->fresh()->status)->toBe(CodSettlement::STATUS_EXPECTED);
});

it('requires a dispute note when marking a settlement disputed', function () {
    $settlement = createSyncedCodSettlement();

    $this->loginAsAdmin();

    post(route('admin.sales.cod-settlements.update', $settlement), [
        'status' => CodSettlement::STATUS_DISPUTED,
        'collected_amount' => $settlement->expected_amount,
        'remitted_amount' => (float) $settlement->net_amount - 25,
        'short_amount' => 25,
        'disputed_amount' => 25,
        'carrier_fee_amount' => $settlement->carrier_fee_amount,
        'cod_fee_amount' => $settlement->cod_fee_amount,
        'return_fee_amount' => $settlement->return_fee_amount,
        'dispute_note' => '',
    ])
        ->assertSessionHasErrors(['dispute_note']);

    expect($settlement->fresh()->status)->toBe(CodSettlement::STATUS_EXPECTED);
});

function createSyncedCodSettlement(): CodSettlement
{
    $fixture = createCodSettlementFixture();

    test()->loginAsAdmin();

    postJson(route('admin.sales.shipments.store', $fixture['order']->id), [
        'shipment' => [
            'source' => $fixture['source'],
            'items' => $fixture['items'],
            'carrier_title' => $fixture['carrier_title'],
            'track_number' => $fixture['track_number'],
        ],
    ])->assertRedirect(route('admin.sales.orders.view', $fixture['order']->id));

    return CodSettlement::query()->latest('id')->firstOrFail();
}

function createCodSettlementFixture(): array
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
        'method' => 'cashondelivery',
        'method_title' => 'Cash On Delivery',
    ]);

    ShipmentCarrier::query()->create([
        'code' => 'steadfast',
        'name' => 'Steadfast Courier',
        'supports_cod' => true,
        'default_cod_fee_type' => 'flat',
        'default_cod_fee_amount' => 55,
        'default_return_fee_amount' => 120,
        'default_payout_method' => 'bkash',
        'is_active' => true,
    ]);

    $shipmentSources = $order->channel->inventory_sources->pluck('id')->toArray();
    $items = [];

    foreach ($order->items as $item) {
        foreach ($order->channel->inventory_sources as $inventorySource) {
            $items[$item->id][$inventorySource->id] = $inventorySource->id;
        }
    }

    return [
        'order' => $order->fresh(['addresses', 'items', 'payment', 'channel.inventory_sources']),
        'source' => fake()->randomElement($shipmentSources),
        'items' => $items,
        'carrier_title' => 'Steadfast Courier',
        'track_number' => 'TRACK-'.fake()->numerify('######'),
    ];
}
