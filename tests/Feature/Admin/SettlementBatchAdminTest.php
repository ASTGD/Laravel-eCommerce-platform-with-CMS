<?php

use Illuminate\Support\Arr;
use Illuminate\Http\UploadedFile;
use Platform\CommerceCore\Models\CodSettlement;
use Platform\CommerceCore\Models\SettlementBatch;
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

use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

uses(AdminTestCase::class);

it('creates a settlement batch and attaches many cod settlements to one payout', function () {
    $this->loginAsAdmin();

    $settlements = createSettlementBatchSettlements(2);
    $first = $settlements[0];
    $second = $settlements[1];

    post(route('admin.sales.settlement-batches.store'), [
        'reference' => 'BATCH-20260418-01',
        'shipment_carrier_id' => $first->shipment_carrier_id,
        'payout_method' => 'bank_transfer',
        'status' => SettlementBatch::STATUS_REMITTED,
        'settlement_ids' => [$first->id, $second->id],
        'remitted_amounts' => [
            $first->id => $first->net_amount,
            $second->id => $second->net_amount - 20,
        ],
        'adjustment_amounts' => [
            $first->id => 0,
            $second->id => 0,
        ],
        'item_notes' => [
            $second->id => 'Courier payout came short by 20.',
        ],
        'notes' => 'Initial courier payout statement received.',
    ])->assertRedirect();

    $batch = SettlementBatch::query()->with('items.codSettlement')->latest('id')->firstOrFail();

    expect($batch->items)->toHaveCount(2)
        ->and($batch->reference)->toBe('BATCH-20260418-01')
        ->and((string) $batch->total_short_amount)->toBe('20.00')
        ->and($first->fresh()->status)->toBe(CodSettlement::STATUS_REMITTED)
        ->and($second->fresh()->status)->toBe(CodSettlement::STATUS_REMITTED);
});

it('shows the settlement batches index and detail screens', function () {
    $this->loginAsAdmin();

    $batch = createSettlementBatchFixture();

    get(route('admin.sales.settlement-batches.index'))
        ->assertOk()
        ->assertSeeText('Settlement Batches');

    get(route('admin.sales.settlement-batches.view', $batch))
        ->assertOk()
        ->assertSeeText($batch->reference)
        ->assertSeeText('Batch Items')
        ->assertSeeText('Totals')
        ->assertSeeText('Reconciliation Health');
});

it('updates a settlement batch and syncs linked cod settlements', function () {
    $this->loginAsAdmin();

    $batch = createSettlementBatchFixture();

    post(route('admin.sales.settlement-batches.update', $batch), [
        'reference' => $batch->reference,
        'payout_method' => $batch->payout_method,
        'status' => SettlementBatch::STATUS_RECONCILED,
        'notes' => 'Final reconciliation completed.',
    ])->assertRedirect(route('admin.sales.settlement-batches.view', $batch));

    $batch->refresh();
    $settledItem = $batch->items()->with('codSettlement')->where('short_amount', 0)->firstOrFail();
    $shortItem = $batch->items()->with('codSettlement')->where('short_amount', '>', 0)->firstOrFail();

    expect($batch->status)->toBe(SettlementBatch::STATUS_RECONCILED)
        ->and($batch->received_at)->not->toBeNull()
        ->and($settledItem->codSettlement->fresh()->status)->toBe(CodSettlement::STATUS_SETTLED)
        ->and($shortItem->codSettlement->fresh()->status)->toBe(CodSettlement::STATUS_SHORT_SETTLED);
});

it('imports a settlement batch from csv and auto-syncs cod settlements', function () {
    $this->loginAsAdmin();

    $settlements = createSettlementBatchSettlements(2);
    $first = $settlements[0]->fresh(['shipmentRecord']);
    $second = $settlements[1]->fresh(['shipmentRecord']);
    $secondNet = (float) $second->net_amount;
    $shortBy = min(20.0, max(1.0, round($secondNet / 2, 2)));
    $secondRemitted = max(0, $secondNet - $shortBy);

    $csv = implode(PHP_EOL, [
        'tracking_number,remitted_amount,adjustment_amount,item_note',
        sprintf(
            '%s,%s,0,Imported in full remittance run',
            $first->shipmentRecord->tracking_number,
            number_format((float) $first->net_amount, 2, '.', ''),
        ),
        sprintf(
            '%s,%s,0,Imported short remittance run',
            $second->shipmentRecord->tracking_number,
            number_format($secondRemitted, 2, '.', ''),
        ),
    ]);

    $importFile = UploadedFile::fake()->createWithContent('settlement-import.csv', $csv);

    post(route('admin.sales.settlement-batches.import-store'), [
        'reference' => 'BATCH-CSV-20260419-01',
        'shipment_carrier_id' => $first->shipment_carrier_id,
        'payout_method' => 'bank_transfer',
        'status' => SettlementBatch::STATUS_RECONCILED,
        'notes' => 'Courier CSV import completed.',
        'import_file' => $importFile,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $batch = SettlementBatch::query()->with('items.codSettlement')->latest('id')->firstOrFail();

    expect($batch->reference)->toBe('BATCH-CSV-20260419-01')
        ->and($batch->items)->toHaveCount(2)
        ->and((float) $batch->total_short_amount)->toEqualWithDelta($shortBy, 0.01)
        ->and($first->fresh()->status)->toBe(CodSettlement::STATUS_SETTLED)
        ->and($second->fresh()->status)->toBe(CodSettlement::STATUS_SHORT_SETTLED);
});

it('rejects settlement csv import when any row cannot be matched', function () {
    $this->loginAsAdmin();

    $settlement = createSettlementBatchSettlements(1)[0]->fresh(['shipmentRecord']);

    $csv = implode(PHP_EOL, [
        'tracking_number,remitted_amount,adjustment_amount,item_note',
        sprintf(
            '%s,%s,0,Valid row',
            $settlement->shipmentRecord->tracking_number,
            number_format((float) $settlement->net_amount, 2, '.', ''),
        ),
        'NOT-FOUND-TRACKING,10.00,0,Invalid row',
    ]);

    $importFile = UploadedFile::fake()->createWithContent('settlement-import-invalid.csv', $csv);

    post(route('admin.sales.settlement-batches.import-store'), [
        'reference' => 'BATCH-CSV-20260419-02',
        'shipment_carrier_id' => $settlement->shipment_carrier_id,
        'status' => SettlementBatch::STATUS_RECONCILED,
        'import_file' => $importFile,
    ])->assertSessionHasErrors(['import_file']);

    expect(SettlementBatch::query()->where('reference', 'BATCH-CSV-20260419-02')->exists())->toBeFalse();
});

it('requires a note when marking a settlement batch disputed', function () {
    $this->loginAsAdmin();

    $batch = createSettlementBatchFixture();

    post(route('admin.sales.settlement-batches.update', $batch), [
        'reference' => $batch->reference,
        'payout_method' => $batch->payout_method,
        'status' => SettlementBatch::STATUS_DISPUTED,
        'notes' => '',
    ])->assertSessionHasErrors(['notes']);

    expect($batch->fresh()->status)->toBe(SettlementBatch::STATUS_REMITTED);
});

function createSettlementBatchFixture(): SettlementBatch
{
    $settlements = createSettlementBatchSettlements(2);
    $first = $settlements[0];
    $second = $settlements[1];

    post(route('admin.sales.settlement-batches.store'), [
        'reference' => 'BATCH-'.fake()->numerify('######'),
        'shipment_carrier_id' => $first->shipment_carrier_id,
        'payout_method' => 'bank_transfer',
        'status' => SettlementBatch::STATUS_REMITTED,
        'settlement_ids' => [$first->id, $second->id],
        'remitted_amounts' => [
            $first->id => $first->net_amount,
            $second->id => $second->net_amount - 15,
        ],
        'adjustment_amounts' => [
            $first->id => 0,
            $second->id => 0,
        ],
        'item_notes' => [
            $second->id => 'Short on first payout statement.',
        ],
        'notes' => 'Courier payout batch seeded for testing.',
    ])->assertRedirect();

    return SettlementBatch::query()->latest('id')->firstOrFail();
}

function createSettlementBatchSettlements(int $count): array
{
    $settlements = [];

    for ($i = 0; $i < $count; $i++) {
        $fixture = createSettlementBatchShipmentFixture();

        postJson(route('admin.sales.shipments.store', $fixture['order']->id), [
            'shipment' => [
                'source' => $fixture['source'],
                'items' => $fixture['items'],
                'carrier_title' => $fixture['carrier_title'],
                'track_number' => $fixture['track_number'],
            ],
        ])->assertRedirect(route('admin.sales.orders.view', $fixture['order']->id));

        $settlements[] = CodSettlement::query()->latest('id')->firstOrFail();
    }

    return $settlements;
}

function createSettlementBatchShipmentFixture(): array
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

    ShipmentCarrier::query()->firstOrCreate([
        'code' => 'steadfast',
    ], [
        'name' => 'Steadfast Courier',
        'supports_cod' => true,
        'default_cod_fee_type' => 'flat',
        'default_cod_fee_amount' => 0,
        'default_return_fee_amount' => 0,
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
