<?php

use Illuminate\Support\Arr;
use Platform\CommerceCore\Models\CodSettlement;
use Platform\CommerceCore\Models\SettlementBatch;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Models\ShipmentEvent;
use Platform\CommerceCore\Models\ShipmentRecord;
use Illuminate\Support\Facades\Schema;
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
    Schema::disableForeignKeyConstraints();

    try {
        ShipmentEvent::query()->delete();
        CodSettlement::query()->delete();
        SettlementBatch::query()->delete();
        ShipmentRecord::query()->delete();
        ShipmentCarrier::query()->delete();
    } finally {
        Schema::enableForeignKeyConstraints();
    }
});

it('hides advanced shipping menus and blocks advanced routes in manual basic mode', function () {
    setShippingModeForAdminTests('manual_basic');

    $this->loginAsAdmin();

    get(route('admin.sales.to-ship.index'))
        ->assertOk()
        ->assertSeeText('To Ship')
        ->assertSeeText('In Delivery')
        ->assertSeeText('COD Receivables')
        ->assertDontSeeText('Order Shipments')
        ->assertDontSeeText('Shipment Ops')
        ->assertDontSeeText('COD Settlements')
        ->assertDontSeeText('Settlement Batches')
        ->assertDontSeeText('Pickup Points')
        ->assertDontSeeText('Courier Services');

    get(route('admin.sales.orders.index'))
        ->assertOk()
        ->assertSeeText('Shipments')
        ->assertDontSeeText('Courier Services');

    get(route('admin.sales.shipments.index'))
        ->assertRedirect(route('admin.sales.to-ship.index'));

    get(route('admin.sales.shipped-orders.index'))->assertOk();
    get(route('admin.sales.cod-receivables.index'))->assertOk();
    get(route('admin.sales.shipment-operations.index'))->assertForbidden();
    get(route('admin.sales.cod-settlements.index'))->assertForbidden();
    get(route('admin.sales.settlement-batches.index'))->assertForbidden();
});

it('shows advanced shipping menus and allows advanced routes in advanced pro mode', function () {
    setShippingModeForAdminTests('advanced_pro');

    $this->loginAsAdmin();

    get(route('admin.sales.shipments.index'))
        ->assertOk()
        ->assertSeeText('Order Shipments')
        ->assertDontSeeText('To Ship')
        ->assertDontSeeText('In Delivery')
        ->assertDontSeeText('COD Receivables')
        ->assertDontSeeText('Shipped Orders')
        ->assertSeeText('Shipment Ops')
        ->assertSeeText('COD Settlements')
        ->assertSeeText('Settlement Batches')
        ->assertDontSeeText('Pickup Points')
        ->assertDontSeeText('Courier Services');

    get(route('admin.sales.orders.index'))
        ->assertOk()
        ->assertSeeText('Shipments')
        ->assertDontSeeText('Courier Services');

    get(route('admin.sales.shipped-orders.index'))->assertForbidden();
    get(route('admin.sales.cod-receivables.index'))->assertForbidden();
    get(route('admin.sales.shipment-operations.index'))->assertOk();
    get(route('admin.sales.cod-settlements.index'))->assertOk();
    get(route('admin.sales.settlement-batches.index'))->assertOk();
});

it('keeps the courier form business-only in manual basic mode', function () {
    setShippingModeForAdminTests('manual_basic');

    $this->loginAsAdmin();

    get(route('admin.sales.carriers.create'))
        ->assertOk()
        ->assertSeeText('Add Courier Service')
        ->assertSeeText('Courier Name')
        ->assertSeeText('Courier Code')
        ->assertSeeText('Contact Person')
        ->assertSeeText('Address')
        ->assertDontSeeText('Automation & API Connection (Pro)')
        ->assertDontSeeText('Automation Type')
        ->assertDontSeeText('Status Update Secret')
        ->assertDontSeeText('API URL');
});

it('shows advanced courier connection fields in advanced pro mode', function () {
    setShippingModeForAdminTests('advanced_pro');

    $this->loginAsAdmin();

    get(route('admin.sales.carriers.create'))
        ->assertOk()
        ->assertSeeText('Automation & API Connection (Pro)')
        ->assertSeeText('Automation Type')
        ->assertSeeText('Courier Payment Defaults');
});

it('hides advanced shipment summaries and blocks advanced action posts in manual basic mode', function () {
    setShippingModeForAdminTests('manual_basic');

    $fixture = createShippingModeOperationalFixture();

    $this->loginAsAdmin();

    get(route('admin.sales.orders.view', $fixture['order']->id))
        ->assertOk()
        ->assertDontSeeText('Shipment Ops')
        ->assertDontSeeText('COD Settlements')
        ->assertDontSeeText('Settlement Batches');

    post(route('admin.sales.shipment-operations.update-status', $fixture['shipmentRecord']), [
        'status' => ShipmentRecord::STATUS_DELIVERED,
    ])->assertForbidden();

    post(route('admin.sales.shipment-operations.sync-tracking', $fixture['shipmentRecord']))
        ->assertForbidden();

    post(route('admin.sales.shipment-operations.book-with-carrier', $fixture['shipmentRecord']))
        ->assertForbidden();

    post(route('admin.sales.cod-settlements.update', $fixture['codSettlement']), [
        'status' => CodSettlement::STATUS_REMITTED,
        'collected_amount' => $fixture['codSettlement']->expected_amount,
        'remitted_amount' => $fixture['codSettlement']->net_amount,
        'short_amount' => 0,
        'disputed_amount' => 0,
        'carrier_fee_amount' => $fixture['codSettlement']->carrier_fee_amount,
        'cod_fee_amount' => $fixture['codSettlement']->cod_fee_amount,
        'return_fee_amount' => $fixture['codSettlement']->return_fee_amount,
    ])->assertForbidden();

    post(route('admin.sales.settlement-batches.update', $fixture['settlementBatch']), [
        'reference' => $fixture['settlementBatch']->reference,
        'payout_method' => $fixture['settlementBatch']->payout_method,
        'status' => SettlementBatch::STATUS_RECONCILED,
        'notes' => 'Should not be allowed in manual mode.',
    ])->assertForbidden();
});

it('keeps advanced shipment summaries and operational actions visible in advanced pro mode', function () {
    setShippingModeForAdminTests('advanced_pro');

    $fixture = createShippingModeOperationalFixture([
        'carrier_attributes' => [
            'code' => 'steadfast',
            'name' => 'Steadfast Courier',
            'integration_driver' => 'steadfast',
            'tracking_sync_enabled' => true,
            'api_base_url' => 'https://portal.steadfast.test/api/v1',
            'api_username' => 'merchant@example.com',
            'api_password' => 'secret',
            'api_key' => 'api-key',
            'api_secret' => 'api-secret',
        ],
    ]);

    $this->loginAsAdmin();

    get(route('admin.sales.orders.view', $fixture['order']->id))
        ->assertOk()
        ->assertSeeText('Shipment Ops')
        ->assertSeeText('COD Settlements')
        ->assertSeeText('Settlement Batches');

    get(route('admin.sales.shipment-operations.view', $fixture['shipmentRecord']))
        ->assertOk()
        ->assertSeeText('Operational Actions')
        ->assertSeeText('Tracking Sync')
        ->assertSeeText('Courier Booking')
        ->assertSeeText('Save Booking References')
        ->assertSeeText('Update Shipment Status')
        ->assertSeeText('Record Delivery Failure')
        ->assertSeeText('Log Operational Event');
});

function setShippingModeForAdminTests(string $mode): void
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

function createShippingModeOperationalFixture(array $overrides = []): array
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

    $carrierAttributes = array_merge([
        'code' => 'manual-carrier',
        'name' => 'Manual Carrier',
        'supports_cod' => true,
        'default_payout_method' => 'bkash',
        'default_cod_fee_type' => 'flat',
        'default_cod_fee_amount' => 25,
        'default_return_fee_amount' => 0,
        'is_active' => true,
    ], $overrides['carrier_attributes'] ?? []);

    $carrier = ShipmentCarrier::query()->create($carrierAttributes);

    $shipmentRecord = ShipmentRecord::query()->create([
        'order_id' => $order->id,
        'shipment_carrier_id' => $carrier->id,
        'status' => ShipmentRecord::STATUS_IN_TRANSIT,
        'carrier_name_snapshot' => $carrier->name,
        'tracking_number' => 'TRACK-HARDEN-001',
        'recipient_name' => $order->customer_full_name,
        'recipient_phone' => $order->shipping_address->phone,
        'recipient_address' => $order->shipping_address->address,
        'destination_country' => $order->shipping_address->country,
        'destination_region' => $order->shipping_address->state,
        'destination_city' => $order->shipping_address->city,
        'cod_amount_expected' => 1500,
        'cod_amount_collected' => 0,
        'carrier_fee_amount' => 120,
        'cod_fee_amount' => 25,
        'return_fee_amount' => 0,
        'net_remittable_amount' => 1355,
        'handed_over_at' => now()->subDay(),
    ]);

    ShipmentEvent::query()->create([
        'shipment_record_id' => $shipmentRecord->id,
        'event_type' => ShipmentEvent::EVENT_SHIPMENT_CREATED,
        'status_after_event' => ShipmentRecord::STATUS_IN_TRANSIT,
        'event_at' => now()->subDay(),
        'meta' => [
            'tracking_number' => $shipmentRecord->tracking_number,
        ],
    ]);

    $codSettlement = CodSettlement::query()->create([
        'shipment_record_id' => $shipmentRecord->id,
        'order_id' => $order->id,
        'shipment_carrier_id' => $carrier->id,
        'status' => CodSettlement::STATUS_EXPECTED,
        'expected_amount' => 1500,
        'collected_amount' => 0,
        'remitted_amount' => 0,
        'short_amount' => 0,
        'disputed_amount' => 0,
        'carrier_fee_amount' => 120,
        'cod_fee_amount' => 25,
        'return_fee_amount' => 0,
        'net_amount' => 1355,
    ]);

    $settlementBatch = SettlementBatch::query()->create([
        'reference' => 'BATCH-HARDEN-001',
        'shipment_carrier_id' => $carrier->id,
        'payout_method' => 'bank_transfer',
        'status' => SettlementBatch::STATUS_REMITTED,
        'gross_expected_amount' => 1500,
        'gross_remitted_amount' => 1355,
        'total_short_amount' => 0,
        'total_deductions_amount' => 145,
        'total_adjustments_amount' => 0,
        'net_amount' => 1355,
    ]);

    $settlementBatch->items()->create([
        'cod_settlement_id' => $codSettlement->id,
        'expected_amount' => 1355,
        'remitted_amount' => 1355,
        'adjustment_amount' => 0,
        'short_amount' => 0,
    ]);

    return [
        'order' => $order->fresh(['addresses']),
        'carrier' => $carrier,
        'shipmentRecord' => $shipmentRecord->fresh(['carrier', 'order', 'codSettlement']),
        'codSettlement' => $codSettlement->fresh(),
        'settlementBatch' => $settlementBatch->fresh(),
    ];
}
