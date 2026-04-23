<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Models\ShipmentEvent;
use Platform\CommerceCore\Models\ShipmentHandoverBatch;
use Platform\CommerceCore\Models\ShipmentRecord;
use Platform\CommerceCore\Services\ManualToShipService;
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
    Schema::disableForeignKeyConstraints();

    try {
        ShipmentEvent::query()->delete();
        ShipmentRecord::query()->delete();
        ShipmentHandoverBatch::query()->delete();
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

it('shows the to ship page with needs booking and parcel ready for handover layers', function () {
    $needsBookingFixture = createManualToShipFixture();
    $readyFixture = createManualToShipFixture();

    $carrier = ShipmentCarrier::query()->create([
        'code' => 'steadfast_manual_to_ship_test',
        'name' => 'Steadfast Courier',
        'supports_cod' => true,
        'is_active' => true,
    ]);

    ShipmentRecord::query()->create([
        'order_id' => $readyFixture['order']->id,
        'shipment_carrier_id' => $carrier->id,
        'status' => ShipmentRecord::STATUS_READY_FOR_PICKUP,
        'carrier_name_snapshot' => $carrier->name,
        'tracking_number' => 'READY-SAME-SCREEN-001',
        'recipient_name' => $readyFixture['order']->customer_full_name,
        'recipient_phone' => $readyFixture['order']->shipping_address->phone,
        'recipient_address' => $readyFixture['order']->shipping_address->address,
        'destination_country' => $readyFixture['order']->shipping_address->country,
        'destination_region' => $readyFixture['order']->shipping_address->state,
        'destination_city' => $readyFixture['order']->shipping_address->city,
        'packed_at' => now()->subHour(),
        'package_count' => 1,
        'handover_mode' => ShipmentRecord::HANDOVER_MODE_COURIER_PICKUP,
    ]);

    $this->loginAsAdmin();

    get(route('admin.sales.to-ship.index'))
        ->assertOk()
        ->assertSeeText('To Ship')
        ->assertSeeText('Needs Booking')
        ->assertSeeText('Parcel Ready for Handover')
        ->assertSeeText((string) $needsBookingFixture['order']->increment_id)
        ->assertSeeText('READY-SAME-SCREEN-001')
        ->assertSeeText('Create Handover Batch')
        ->assertSeeText('Book Shipment');
});

it('books a shipment into parcel ready for handover instead of in delivery', function () {
    $fixture = createManualToShipFixture();

    $carrier = ShipmentCarrier::query()->create([
        'code' => 'pathao_manual_to_ship_test',
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
            'workflow_stage' => 'ready_for_handover',
            'carrier_id' => $carrier->id,
            'track_number' => 'TRACK-BASIC-001',
            'public_tracking_url' => 'https://pathao.example/track/TRACK-BASIC-001',
            'stock_checked' => 1,
            'package_count' => 2,
            'package_weight_kg' => '1.50',
            'package_dimensions' => '12 x 8 x 5 in',
            'handover_mode' => ShipmentRecord::HANDOVER_MODE_COURIER_PICKUP,
            'is_fragile' => 1,
            'special_handling' => 'Glass items inside.',
            'internal_note' => 'Packed by warehouse team.',
            'courier_note' => 'Pickup from front desk.',
        ],
    ])->assertRedirect(route('admin.sales.to-ship.index').'#parcel-ready-for-handover');

    $nativeShipment = Shipment::query()->latest('id')->firstOrFail();
    $shipmentRecord = ShipmentRecord::query()->where('native_shipment_id', $nativeShipment->id)->firstOrFail();

    expect($shipmentRecord->status)->toBe(ShipmentRecord::STATUS_READY_FOR_PICKUP)
        ->and($shipmentRecord->shipment_carrier_id)->toBe($carrier->id)
        ->and($shipmentRecord->tracking_number)->toBe('TRACK-BASIC-001')
        ->and($shipmentRecord->public_tracking_url)->toBe('https://pathao.example/track/TRACK-BASIC-001')
        ->and($shipmentRecord->stock_checked)->toBeTrue()
        ->and($shipmentRecord->package_count)->toBe(2)
        ->and((string) $shipmentRecord->package_weight_kg)->toBe('1.50')
        ->and($shipmentRecord->package_dimensions)->toBe('12 x 8 x 5 in')
        ->and($shipmentRecord->handover_mode)->toBe(ShipmentRecord::HANDOVER_MODE_COURIER_PICKUP)
        ->and($shipmentRecord->is_fragile)->toBeTrue()
        ->and($shipmentRecord->special_handling)->toBe('Glass items inside.')
        ->and($shipmentRecord->internal_note)->toBe('Packed by warehouse team.')
        ->and($shipmentRecord->courier_note)->toBe('Pickup from front desk.')
        ->and($shipmentRecord->handed_over_at)->toBeNull();

    $manualToShipService = app(ManualToShipService::class);
    $needsBookingOrderIds = collect($manualToShipService->paginateNeedsBookingOrders(100)->items())
        ->map(fn (array $row) => $row['order']->id)
        ->all();
    $readyShipmentIds = collect($manualToShipService->paginateReadyShipments(100)->items())
        ->map(fn (ShipmentRecord $record) => $record->id)
        ->all();

    expect($needsBookingOrderIds)->not->toContain($fixture['order']->id)
        ->and($readyShipmentIds)->toContain($shipmentRecord->id);

    get(route('admin.sales.to-ship.index'))
        ->assertOk()
        ->assertSeeText('Parcel Ready for Handover')
        ->assertSeeText((string) $fixture['order']->increment_id)
        ->assertSeeText('Pathao Courier')
        ->assertSeeText('TRACK-BASIC-001');

    get(route('admin.sales.shipped-orders.index'))
        ->assertOk()
        ->assertDontSeeText('TRACK-BASIC-001');
});

it('prints parcel documents from the booking flow', function () {
    $fixture = createManualToShipFixture();

    $carrier = ShipmentCarrier::query()->create([
        'code' => 'print_manual_to_ship_test',
        'name' => 'Print Courier',
        'supports_cod' => true,
        'is_active' => true,
    ]);

    $this->loginAsAdmin();

    post(route('admin.sales.to-ship.print-documents', [$fixture['order']->id, 'document' => 'both']), [
        'shipment' => [
            'carrier_id' => $carrier->id,
            'track_number' => 'TRACK-PRINT-001',
            'public_tracking_url' => 'https://print.example/TRACK-PRINT-001',
            'stock_checked' => 1,
            'package_count' => 1,
            'handover_mode' => ShipmentRecord::HANDOVER_MODE_STAFF_DROPOFF,
        ],
    ])->assertOk()
        ->assertSeeText('Parcel Label')
        ->assertSeeText('Invoice')
        ->assertSeeText('TRACK-PRINT-001')
        ->assertSeeText((string) $fixture['order']->increment_id);
});

it('returns a warning response for print preview when required booking fields are missing', function () {
    $fixture = createManualToShipFixture();

    $this->loginAsAdmin();

    post(route('admin.sales.to-ship.print-documents', [$fixture['order']->id, 'document' => 'label']), [
        'shipment' => [
            'package_count' => 1,
        ],
    ], [
        'X-Requested-With' => 'XMLHttpRequest',
        'Accept' => 'application/json',
    ])->assertStatus(422)
        ->assertJsonPath('message', 'Complete the required booking fields before opening print preview.')
        ->assertJsonValidationErrors([
            'shipment.carrier_id',
            'shipment.track_number',
            'shipment.handover_mode',
        ]);
});

it('creates a draft handover batch, prints a manifest, and confirms handover into in delivery', function () {
    $fixture = createManualToShipFixture();

    $carrier = ShipmentCarrier::query()->create([
        'code' => 'handover_manual_to_ship_test',
        'name' => 'Steadfast Courier',
        'supports_cod' => true,
        'is_active' => true,
    ]);

    $shipmentRecord = ShipmentRecord::query()->create([
        'order_id' => $fixture['order']->id,
        'shipment_carrier_id' => $carrier->id,
        'status' => ShipmentRecord::STATUS_READY_FOR_PICKUP,
        'carrier_name_snapshot' => $carrier->name,
        'tracking_number' => 'READY-001',
        'recipient_name' => $fixture['order']->customer_full_name,
        'recipient_phone' => $fixture['order']->shipping_address->phone,
        'recipient_address' => $fixture['order']->shipping_address->address,
        'destination_country' => $fixture['order']->shipping_address->country,
        'destination_region' => $fixture['order']->shipping_address->state,
        'destination_city' => $fixture['order']->shipping_address->city,
        'cod_amount_expected' => 1499,
        'stock_checked' => true,
        'packed_at' => now()->subHour(),
        'package_count' => 2,
        'handover_mode' => ShipmentRecord::HANDOVER_MODE_COURIER_PICKUP,
        'internal_note' => 'Packed and waiting near dispatch desk.',
    ]);

    $this->loginAsAdmin();

    post(route('admin.sales.to-ship.create-handover-batch'), [
        'shipment_record_ids' => [$shipmentRecord->id],
        'handover_type' => ShipmentHandoverBatch::TYPE_COURIER_PICKUP,
        'handover_at' => now()->format('Y-m-d H:i:s'),
        'receiver_name' => 'Driver Rahim',
        'notes' => 'Morning pickup batch',
    ])->assertRedirect(route('admin.sales.to-ship.index').'#parcel-ready-for-handover');

    $batch = ShipmentHandoverBatch::query()->latest('id')->firstOrFail();
    $shipmentRecord->refresh();

    expect($batch->reference)->toStartWith('HB-')
        ->and($batch->shipment_carrier_id)->toBe($carrier->id)
        ->and($batch->parcel_count)->toBe(2)
        ->and((float) $batch->total_cod_amount)->toBe(1499.0)
        ->and($shipmentRecord->handover_batch_id)->toBe($batch->id);

    post(route('admin.sales.to-ship.print-manifest'), [
        'shipment_record_ids' => [$shipmentRecord->id],
        'handover_type' => ShipmentHandoverBatch::TYPE_COURIER_PICKUP,
        'handover_at' => now()->format('Y-m-d H:i:s'),
        'receiver_name' => 'Driver Rahim',
        'notes' => 'Morning pickup batch',
    ])->assertOk()
        ->assertSeeText('Handover Sheet / Manifest')
        ->assertSeeText('READY-001')
        ->assertSeeText((string) $fixture['order']->increment_id);

    post(route('admin.sales.to-ship.confirm-handover'), [
        'shipment_record_ids' => [$shipmentRecord->id],
        'handover_type' => ShipmentHandoverBatch::TYPE_COURIER_PICKUP,
        'handover_at' => now()->format('Y-m-d H:i:s'),
        'receiver_name' => 'Driver Rahim',
        'notes' => 'Morning pickup batch',
    ])->assertRedirect(route('admin.sales.shipped-orders.index'));

    $shipmentRecord->refresh();
    $batch->refresh();

    expect($shipmentRecord->status)->toBe(ShipmentRecord::STATUS_HANDED_TO_CARRIER)
        ->and($shipmentRecord->handed_over_at)->not->toBeNull()
        ->and($shipmentRecord->handover_mode)->toBe(ShipmentHandoverBatch::TYPE_COURIER_PICKUP)
        ->and($shipmentRecord->handover_batch_id)->toBe($batch->id)
        ->and($batch->confirmed_at)->not->toBeNull();

    get(route('admin.sales.shipped-orders.index'))
        ->assertOk()
        ->assertSeeText('READY-001')
        ->assertSeeText('Steadfast Courier');
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
