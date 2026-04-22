<?php

use Illuminate\Support\Arr;
use Platform\CommerceCore\Models\CodSettlement;
use Platform\CommerceCore\Models\ShipmentCarrier;
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
        CodSettlement::query()->delete();
        ShipmentRecord::query()->delete();
        ShipmentCarrier::query()->delete();
    } finally {
        Schema::enableForeignKeyConstraints();
    }

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

it('shows courier-first cod receivable totals in manual basic mode', function () {
    $steadfast = ShipmentCarrier::query()->create([
        'code' => 'steadfast',
        'name' => 'Steadfast Courier',
        'supports_cod' => true,
        'is_active' => true,
    ]);

    $pathao = ShipmentCarrier::query()->create([
        'code' => 'pathao',
        'name' => 'Pathao Courier',
        'supports_cod' => true,
        'is_active' => true,
    ]);

    createManualCodReceivableSettlement($steadfast, [
        'expected_amount' => 1000,
        'net_amount' => 1000,
        'remitted_amount' => 0,
        'status' => CodSettlement::STATUS_COLLECTED_BY_CARRIER,
        'collected_at' => now()->subDays(3),
    ]);

    createManualCodReceivableSettlement($steadfast, [
        'expected_amount' => 800,
        'net_amount' => 800,
        'remitted_amount' => 300,
        'status' => CodSettlement::STATUS_REMITTED,
        'collected_at' => now()->subDays(2),
    ]);

    createManualCodReceivableSettlement($pathao, [
        'expected_amount' => 500,
        'net_amount' => 500,
        'remitted_amount' => 500,
        'status' => CodSettlement::STATUS_SETTLED,
        'collected_at' => now()->subDay(),
    ]);

    $this->loginAsAdmin();

    get(route('admin.sales.cod-receivables.index'))
        ->assertOk()
        ->assertSeeText('COD Receivables')
        ->assertSeeText('Search couriers')
        ->assertSeeText('Filter')
        ->assertSeeText('Per Page')
        ->assertSeeText('Record COD Received')
        ->assertSeeText('Steadfast Courier')
        ->assertSeeText('Pathao Courier')
        ->assertSeeText(core()->formatBasePrice(1800))
        ->assertSeeText(core()->formatBasePrice(300))
        ->assertSeeText(core()->formatBasePrice(1500))
        ->assertSeeText(core()->formatBasePrice(500))
        ->assertSeeText('Up to date')
        ->assertDontSeeText('Manual Basic mode')
        ->assertDontSeeText('Courier totals stay simple here, while shipment-level records remain accurate underneath.')
        ->assertDontSeeText('COD already collected by the courier from customers.')
        ->assertDontSeeText('Money your business has already received from the courier.')
        ->assertDontSeeText('Money still pending from the courier to your business.');
});

it('records courier cod receipt and allocates it oldest first across shipment settlements', function () {
    $carrier = ShipmentCarrier::query()->create([
        'code' => 'local-courier',
        'name' => 'Local Courier',
        'supports_cod' => true,
        'is_active' => true,
    ]);

    $oldest = createManualCodReceivableSettlement($carrier, [
        'expected_amount' => 1000,
        'net_amount' => 1000,
        'remitted_amount' => 0,
        'status' => CodSettlement::STATUS_COLLECTED_BY_CARRIER,
        'collected_at' => now()->subDays(4),
    ]);

    $newer = createManualCodReceivableSettlement($carrier, [
        'expected_amount' => 800,
        'net_amount' => 800,
        'remitted_amount' => 0,
        'status' => CodSettlement::STATUS_COLLECTED_BY_CARRIER,
        'collected_at' => now()->subDays(2),
    ]);

    $this->loginAsAdmin();

    post(route('admin.sales.cod-receivables.record-received'), [
        'shipment_carrier_id' => $carrier->id,
        'amount' => 1200,
        'note' => 'Bank transfer batch 1',
    ])->assertRedirect(route('admin.sales.cod-receivables.index'));

    $oldest['codSettlement']->refresh();
    $newer['codSettlement']->refresh();

    expect($oldest['codSettlement']->status)->toBe(CodSettlement::STATUS_SETTLED)
        ->and((float) $oldest['codSettlement']->remitted_amount)->toBe(1000.0)
        ->and($oldest['codSettlement']->settled_at)->not->toBeNull()
        ->and($oldest['codSettlement']->notes)->toContain('Bank transfer batch 1')
        ->and($newer['codSettlement']->status)->toBe(CodSettlement::STATUS_REMITTED)
        ->and((float) $newer['codSettlement']->remitted_amount)->toBe(200.0)
        ->and($newer['codSettlement']->remitted_at)->not->toBeNull()
        ->and($newer['codSettlement']->notes)->toContain('Bank transfer batch 1');

    get(route('admin.sales.cod-receivables.index'))
        ->assertOk()
        ->assertSeeText('Local Courier')
        ->assertSeeText(core()->formatBasePrice(1800))
        ->assertSeeText(core()->formatBasePrice(1200))
        ->assertSeeText(core()->formatBasePrice(600));
});

it('moves a delivered cod shipment into courier receivables through the shared manual pipeline', function () {
    $carrier = ShipmentCarrier::query()->create([
        'code' => 'steadfast',
        'name' => 'Steadfast Courier',
        'supports_cod' => true,
        'is_active' => true,
    ]);

    $fixture = createManualCodReceivableOrderFixture();

    $shipmentRecord = ShipmentRecord::query()->create([
        'order_id' => $fixture['order']->id,
        'shipment_carrier_id' => $carrier->id,
        'status' => ShipmentRecord::STATUS_IN_TRANSIT,
        'carrier_name_snapshot' => $carrier->name,
        'tracking_number' => 'TRACK-COD-PIPELINE-001',
        'recipient_name' => $fixture['order']->customer_full_name,
        'recipient_phone' => $fixture['order']->shipping_address->phone,
        'recipient_address' => $fixture['order']->shipping_address->address,
        'destination_country' => $fixture['order']->shipping_address->country,
        'destination_region' => $fixture['order']->shipping_address->state,
        'destination_city' => $fixture['order']->shipping_address->city,
        'cod_amount_expected' => 1500,
        'cod_amount_collected' => 0,
        'carrier_fee_amount' => 100,
        'cod_fee_amount' => 0,
        'return_fee_amount' => 0,
        'net_remittable_amount' => 1400,
        'handed_over_at' => now()->subHours(6),
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
        ->and((float) $shipmentRecord->cod_amount_collected)->toBe(1500.0)
        ->and($codSettlement->status)->toBe(CodSettlement::STATUS_COLLECTED_BY_CARRIER)
        ->and((float) $codSettlement->collected_amount)->toBe(1500.0)
        ->and((float) $codSettlement->remitted_amount)->toBe(0.0);

    get(route('admin.sales.cod-receivables.index'))
        ->assertOk()
        ->assertSeeText('Steadfast Courier')
        ->assertSeeText(core()->formatBasePrice(1400))
        ->assertSeeText(core()->formatBasePrice(0))
        ->assertSeeText(core()->formatBasePrice(1400));
});

it('does not add prepaid deliveries to cod receivables', function () {
    $carrier = ShipmentCarrier::query()->create([
        'code' => 'local-courier',
        'name' => 'Local Courier',
        'supports_cod' => true,
        'is_active' => true,
    ]);

    $fixture = createManualCodReceivableOrderFixture([
        'payment_method' => 'banktransfer',
        'payment_title' => 'Bank Transfer',
    ]);

    $shipmentRecord = ShipmentRecord::query()->create([
        'order_id' => $fixture['order']->id,
        'shipment_carrier_id' => $carrier->id,
        'status' => ShipmentRecord::STATUS_IN_TRANSIT,
        'carrier_name_snapshot' => $carrier->name,
        'tracking_number' => 'TRACK-PREPAID-001',
        'recipient_name' => $fixture['order']->customer_full_name,
        'recipient_phone' => $fixture['order']->shipping_address->phone,
        'recipient_address' => $fixture['order']->shipping_address->address,
        'destination_country' => $fixture['order']->shipping_address->country,
        'destination_region' => $fixture['order']->shipping_address->state,
        'destination_city' => $fixture['order']->shipping_address->city,
        'cod_amount_expected' => 0,
        'cod_amount_collected' => 0,
        'carrier_fee_amount' => 100,
        'cod_fee_amount' => 0,
        'return_fee_amount' => 0,
        'net_remittable_amount' => 0,
        'handed_over_at' => now()->subHours(3),
    ]);

    $this->loginAsAdmin();

    post(route('admin.sales.shipped-orders.mark-delivered', $shipmentRecord))
        ->assertRedirect(route('admin.sales.shipped-orders.index'));

    $shipmentRecord->refresh();

    expect($shipmentRecord->status)->toBe(ShipmentRecord::STATUS_DELIVERED)
        ->and($shipmentRecord->delivered_at)->not->toBeNull()
        ->and((float) $shipmentRecord->cod_amount_collected)->toBe(0.0)
        ->and(CodSettlement::query()->where('shipment_record_id', $shipmentRecord->id)->exists())->toBeFalse();

    get(route('admin.sales.cod-receivables.index'))
        ->assertOk()
        ->assertDontSeeText('Local Courier')
        ->assertSeeText('No COD receivables are ready yet.');
});

function createManualCodReceivableSettlement(ShipmentCarrier $carrier, array $overrides = []): array
{
    $fixture = createManualCodReceivableOrderFixture($overrides['order_fixture'] ?? []);

    $expectedAmount = (float) ($overrides['expected_amount'] ?? 1200);
    $carrierFeeAmount = (float) ($overrides['carrier_fee_amount'] ?? 0);
    $codFeeAmount = (float) ($overrides['cod_fee_amount'] ?? 0);
    $returnFeeAmount = (float) ($overrides['return_fee_amount'] ?? 0);
    $netAmount = (float) ($overrides['net_amount'] ?? max(0, $expectedAmount - $carrierFeeAmount - $codFeeAmount - $returnFeeAmount));
    $collectedAmount = (float) ($overrides['collected_amount'] ?? $expectedAmount);
    $remittedAmount = (float) ($overrides['remitted_amount'] ?? 0);
    $status = $overrides['status'] ?? CodSettlement::STATUS_COLLECTED_BY_CARRIER;
    $collectedAt = $overrides['collected_at'] ?? now()->subDay();
    $deliveredAt = $overrides['delivered_at'] ?? now()->subDay();
    $handedOverAt = $overrides['handed_over_at'] ?? $deliveredAt->copy()->subDay();

    $shipmentRecord = ShipmentRecord::query()->create([
        'order_id' => $fixture['order']->id,
        'shipment_carrier_id' => $carrier->id,
        'status' => ShipmentRecord::STATUS_DELIVERED,
        'carrier_name_snapshot' => $carrier->name,
        'tracking_number' => 'TRACK-COD-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
        'recipient_name' => $fixture['order']->customer_full_name,
        'recipient_phone' => $fixture['order']->shipping_address->phone,
        'recipient_address' => $fixture['order']->shipping_address->address,
        'destination_country' => $fixture['order']->shipping_address->country,
        'destination_region' => $fixture['order']->shipping_address->state,
        'destination_city' => $fixture['order']->shipping_address->city,
        'cod_amount_expected' => $expectedAmount,
        'cod_amount_collected' => $collectedAmount,
        'carrier_fee_amount' => $carrierFeeAmount,
        'cod_fee_amount' => $codFeeAmount,
        'return_fee_amount' => $returnFeeAmount,
        'net_remittable_amount' => $netAmount,
        'handed_over_at' => $handedOverAt,
        'delivered_at' => $deliveredAt,
    ]);

    $codSettlement = CodSettlement::query()->create([
        'shipment_record_id' => $shipmentRecord->id,
        'order_id' => $fixture['order']->id,
        'shipment_carrier_id' => $carrier->id,
        'status' => $status,
        'expected_amount' => $expectedAmount,
        'collected_amount' => $collectedAmount,
        'remitted_amount' => $remittedAmount,
        'short_amount' => max(0, $netAmount - $remittedAmount),
        'disputed_amount' => 0,
        'carrier_fee_amount' => $carrierFeeAmount,
        'cod_fee_amount' => $codFeeAmount,
        'return_fee_amount' => $returnFeeAmount,
        'net_amount' => $netAmount,
        'collected_at' => $collectedAt,
        'remitted_at' => $status === CodSettlement::STATUS_SETTLED || $status === CodSettlement::STATUS_REMITTED ? now()->subHours(6) : null,
        'settled_at' => $status === CodSettlement::STATUS_SETTLED ? now()->subHours(3) : null,
    ]);

    return [
        'order' => $fixture['order'],
        'shipmentRecord' => $shipmentRecord,
        'codSettlement' => $codSettlement,
    ];
}

function createManualCodReceivableOrderFixture(array $overrides = []): array
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
        'method' => $paymentMethod = $overrides['payment_method'] ?? 'cashondelivery',
        'method_title' => $overrides['payment_title'] ?? core()->getConfigData('sales.payment_methods.'.$paymentMethod.'.title') ?? str($paymentMethod)->replace('_', ' ')->title()->value(),
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
        'method' => $paymentMethod,
        'method_title' => $overrides['payment_title'] ?? ($paymentMethod === 'cashondelivery' ? 'Cash On Delivery' : str($paymentMethod)->replace('_', ' ')->title()->value()),
    ]);

    $order->refresh();

    return [
        'order' => $order,
    ];
}
