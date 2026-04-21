<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Platform\CommerceCore\Mail\Admin\Shipment\OperationalUpdateNotification as AdminOperationalUpdateNotification;
use Platform\CommerceCore\Mail\Shop\Shipment\OperationalUpdateNotification as ShopOperationalUpdateNotification;
use Platform\CommerceCore\Models\CodSettlement;
use Platform\CommerceCore\Models\SettlementBatch;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Models\ShipmentCommunication;
use Platform\CommerceCore\Models\ShipmentEvent;
use Platform\CommerceCore\Models\ShipmentRecord;
use Platform\CommerceCore\Jobs\SyncShipmentTrackingJob;
use Platform\CommerceCore\Services\CustomerShipmentTrackingService;
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
use function Pest\Laravel\postJson;

uses(AdminTestCase::class);

beforeEach(function () {
    setAdminShippingMode('advanced_pro');
});

it('syncs an operational shipment record from native shipment creation', function () {
    $fixture = createShipmentRecordFixture();

    ShipmentCarrier::query()->create([
        'code' => 'steadfast',
        'name' => $fixture['carrier_title'],
        'supports_cod' => true,
        'default_cod_fee_type' => 'flat',
        'default_cod_fee_amount' => 55,
        'default_return_fee_amount' => 120,
        'default_payout_method' => 'bkash',
        'is_active' => true,
    ]);

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
    $shipmentRecord = ShipmentRecord::query()->where('native_shipment_id', $nativeShipment->id)->firstOrFail();

    expect($shipmentRecord->status)->toBe(ShipmentRecord::STATUS_HANDED_TO_CARRIER)
        ->and($shipmentRecord->shipment_carrier_id)->not->toBeNull()
        ->and($shipmentRecord->tracking_number)->toBe($fixture['track_number'])
        ->and((string) $shipmentRecord->cod_amount_expected)->toBe((string) number_format((float) $fixture['order']->base_grand_total, 2, '.', ''))
        ->and(ShipmentEvent::query()->where('shipment_record_id', $shipmentRecord->id)->count())->toBe(1);
});

it('shows a courier-first shipment drawer on the admin order page', function () {
    $fixture = createShipmentRecordFixture();

    ShipmentCarrier::query()->create([
        'code' => 'steadfast',
        'name' => 'Steadfast Courier',
        'tracking_url_template' => 'https://carrier.example/track/{tracking_number}',
        'supports_cod' => true,
        'is_active' => true,
    ]);

    $this->loginAsAdmin();

    get(route('admin.sales.orders.view', $fixture['order']->id))
        ->assertOk()
        ->assertSeeText('Courier Service')
        ->assertSeeText('Select saved courier')
        ->assertSeeText('Public Tracking Link (Optional)')
        ->assertDontSeeText('Carrier Name');
});

it('registers a native shipment with a saved courier and public tracking override', function () {
    $fixture = createShipmentRecordFixture();

    $carrier = ShipmentCarrier::query()->create([
        'code' => 'steadfast',
        'name' => 'Steadfast Courier',
        'tracking_url_template' => 'https://carrier.example/track/{tracking_number}',
        'supports_cod' => true,
        'default_cod_fee_type' => 'flat',
        'default_cod_fee_amount' => 55,
        'default_return_fee_amount' => 120,
        'default_payout_method' => 'bkash',
        'is_active' => true,
    ]);

    $this->loginAsAdmin();

    postJson(route('admin.sales.shipments.store', $fixture['order']->id), [
        'shipment' => [
            'source' => $fixture['source'],
            'items' => $fixture['items'],
            'carrier_id' => $carrier->id,
            'track_number' => $fixture['track_number'],
            'public_tracking_url' => 'https://tracking.example/shipments/'.$fixture['track_number'],
        ],
    ])->assertRedirect(route('admin.sales.orders.view', $fixture['order']->id));

    $nativeShipment = Shipment::query()->latest('id')->firstOrFail();
    $shipmentRecord = ShipmentRecord::query()->where('native_shipment_id', $nativeShipment->id)->firstOrFail();
    $timeline = app(CustomerShipmentTrackingService::class)->forOrder($fixture['order']->fresh())->first();

    expect($nativeShipment->carrier_title)->toBe('Steadfast Courier')
        ->and($shipmentRecord->shipment_carrier_id)->toBe($carrier->id)
        ->and($shipmentRecord->carrier_name_snapshot)->toBe('Steadfast Courier')
        ->and($shipmentRecord->public_tracking_url)->toBe('https://tracking.example/shipments/'.$fixture['track_number'])
        ->and($timeline['carrier_tracking_url'])->toBe('https://tracking.example/shipments/'.$fixture['track_number']);
});

it('shows shipment ops in the admin order view and shipment ops pages', function () {
    $fixture = createShipmentRecordFixture();

    $shipmentRecord = ShipmentRecord::query()->create([
        'order_id' => $fixture['order']->id,
        'status' => ShipmentRecord::STATUS_IN_TRANSIT,
        'carrier_name_snapshot' => 'Manual Carrier',
        'tracking_number' => 'TRACK-123',
        'recipient_name' => $fixture['order']->customer_full_name,
        'recipient_phone' => $fixture['order']->shipping_address->phone,
        'recipient_address' => $fixture['order']->shipping_address->address,
        'destination_country' => $fixture['order']->shipping_address->country,
        'destination_region' => $fixture['order']->shipping_address->state,
        'destination_city' => $fixture['order']->shipping_address->city,
        'handed_over_at' => now(),
    ]);

    ShipmentEvent::query()->create([
        'shipment_record_id' => $shipmentRecord->id,
        'event_type' => 'status_updated',
        'status_after_event' => ShipmentRecord::STATUS_IN_TRANSIT,
        'event_at' => now(),
    ]);

    $this->loginAsAdmin();

    get(route('admin.sales.shipment-operations.index'))
        ->assertOk()
        ->assertSeeText('Shipment Operations');

    get(route('admin.sales.shipment-operations.view', $shipmentRecord))
        ->assertOk()
        ->assertSeeText('TRACK-123')
        ->assertSeeText('In Transit');

    get(route('admin.sales.orders.view', $fixture['order']->id))
        ->assertOk()
        ->assertSeeText('Shipment Ops')
        ->assertSeeText('TRACK-123');
});

it('shows shipment, cod settlement, and settlement batch summaries on the admin order view', function () {
    $fixture = createShipmentRecordFixture();

    $shipmentRecord = ShipmentRecord::query()->create([
        'order_id' => $fixture['order']->id,
        'status' => ShipmentRecord::STATUS_DELIVERED,
        'carrier_name_snapshot' => 'Steadfast Courier',
        'tracking_number' => 'TRACK-321',
        'recipient_name' => $fixture['order']->customer_full_name,
        'recipient_phone' => $fixture['order']->shipping_address->phone,
        'recipient_address' => $fixture['order']->shipping_address->address,
        'cod_amount_expected' => 1200,
        'cod_amount_collected' => 1200,
        'carrier_fee_amount' => 60,
        'cod_fee_amount' => 25,
        'net_remittable_amount' => 1115,
        'handed_over_at' => now()->subDay(),
        'delivered_at' => now()->subHours(2),
    ]);

    ShipmentEvent::query()->create([
        'shipment_record_id' => $shipmentRecord->id,
        'event_type' => ShipmentEvent::EVENT_SHIPMENT_CREATED,
        'status_after_event' => ShipmentRecord::STATUS_DELIVERED,
        'event_at' => now()->subDay(),
    ]);

    $codSettlement = CodSettlement::query()->create([
        'shipment_record_id' => $shipmentRecord->id,
        'order_id' => $fixture['order']->id,
        'status' => CodSettlement::STATUS_REMITTED,
        'expected_amount' => 1200,
        'collected_amount' => 1200,
        'remitted_amount' => 1115,
        'carrier_fee_amount' => 60,
        'cod_fee_amount' => 25,
        'net_amount' => 1115,
        'remitted_at' => now()->subHour(),
    ]);

    $settlementBatch = SettlementBatch::query()->create([
        'reference' => 'BATCH-ORDER-001',
        'status' => SettlementBatch::STATUS_REMITTED,
        'gross_expected_amount' => 1200,
        'gross_remitted_amount' => 1115,
        'total_short_amount' => 0,
        'total_deductions_amount' => 85,
        'net_amount' => 1115,
        'remitted_at' => now()->subHour(),
    ]);

    $settlementBatch->items()->create([
        'cod_settlement_id' => $codSettlement->id,
        'expected_amount' => 1115,
        'remitted_amount' => 1115,
        'adjustment_amount' => 0,
        'short_amount' => 0,
    ]);

    $this->loginAsAdmin();

    get(route('admin.sales.orders.view', $fixture['order']->id))
        ->assertOk()
        ->assertSeeText('Shipment Ops')
        ->assertSeeText('COD Settlements')
        ->assertSeeText('Settlement Batches')
        ->assertSeeText('TRACK-321')
        ->assertSeeText('BATCH-ORDER-001');
});

it('updates shipment ops status and appends a timeline event', function () {
    $fixture = createShipmentRecordFixture();

    $shipmentRecord = ShipmentRecord::query()->create([
        'order_id' => $fixture['order']->id,
        'status' => ShipmentRecord::STATUS_HANDED_TO_CARRIER,
        'carrier_name_snapshot' => 'Manual Carrier',
        'tracking_number' => 'TRACK-999',
        'recipient_name' => $fixture['order']->customer_full_name,
        'recipient_phone' => $fixture['order']->shipping_address->phone,
        'recipient_address' => $fixture['order']->shipping_address->address,
        'cod_amount_expected' => $fixture['order']->base_grand_total,
        'handed_over_at' => now(),
    ]);

    ShipmentEvent::query()->create([
        'shipment_record_id' => $shipmentRecord->id,
        'event_type' => 'shipment_created',
        'status_after_event' => ShipmentRecord::STATUS_HANDED_TO_CARRIER,
        'event_at' => now()->subMinute(),
    ]);

    $this->loginAsAdmin();

    post(route('admin.sales.shipment-operations.update-status', $shipmentRecord), [
        'status' => ShipmentRecord::STATUS_DELIVERED,
        'note' => 'Delivered by local rider.',
    ])->assertRedirect(route('admin.sales.shipment-operations.view', $shipmentRecord));

    $shipmentRecord->refresh();

    expect($shipmentRecord->status)->toBe(ShipmentRecord::STATUS_DELIVERED)
        ->and($shipmentRecord->delivered_at)->not->toBeNull()
        ->and((string) $shipmentRecord->cod_amount_collected)->toBe((string) number_format((float) $fixture['order']->base_grand_total, 2, '.', ''))
        ->and(ShipmentEvent::query()->where('shipment_record_id', $shipmentRecord->id)->count())->toBe(2);
});

it('records an operational event without changing shipment status', function () {
    $fixture = createShipmentRecordFixture();

    $shipmentRecord = ShipmentRecord::query()->create([
        'order_id' => $fixture['order']->id,
        'status' => ShipmentRecord::STATUS_IN_TRANSIT,
        'carrier_name_snapshot' => 'Manual Carrier',
        'tracking_number' => 'TRACK-741',
        'recipient_name' => $fixture['order']->customer_full_name,
        'recipient_phone' => $fixture['order']->shipping_address->phone,
        'recipient_address' => $fixture['order']->shipping_address->address,
        'handed_over_at' => now(),
    ]);

    ShipmentEvent::query()->create([
        'shipment_record_id' => $shipmentRecord->id,
        'event_type' => ShipmentEvent::EVENT_SHIPMENT_CREATED,
        'status_after_event' => ShipmentRecord::STATUS_IN_TRANSIT,
        'event_at' => now()->subMinute(),
    ]);

    $this->loginAsAdmin();

    post(route('admin.sales.shipment-operations.store-event', $shipmentRecord), [
        'event_type' => ShipmentEvent::EVENT_ARRIVED_DESTINATION_HUB,
        'note' => 'Parcel scanned at the destination hub.',
    ])->assertRedirect(route('admin.sales.shipment-operations.view', $shipmentRecord));

    $shipmentRecord->refresh();
    $latestEvent = ShipmentEvent::query()
        ->where('shipment_record_id', $shipmentRecord->id)
        ->latest('id')
        ->firstOrFail();

    expect($shipmentRecord->status)->toBe(ShipmentRecord::STATUS_IN_TRANSIT)
        ->and($latestEvent->event_type)->toBe(ShipmentEvent::EVENT_ARRIVED_DESTINATION_HUB)
        ->and($latestEvent->status_after_event)->toBe(ShipmentRecord::STATUS_IN_TRANSIT)
        ->and($latestEvent->note)->toBe('Parcel scanned at the destination hub.');
});

it('records an operational event and can advance shipment status', function () {
    $fixture = createShipmentRecordFixture();

    $shipmentRecord = ShipmentRecord::query()->create([
        'order_id' => $fixture['order']->id,
        'status' => ShipmentRecord::STATUS_IN_TRANSIT,
        'carrier_name_snapshot' => 'Manual Carrier',
        'tracking_number' => 'TRACK-852',
        'recipient_name' => $fixture['order']->customer_full_name,
        'recipient_phone' => $fixture['order']->shipping_address->phone,
        'recipient_address' => $fixture['order']->shipping_address->address,
        'handed_over_at' => now(),
    ]);

    ShipmentEvent::query()->create([
        'shipment_record_id' => $shipmentRecord->id,
        'event_type' => ShipmentEvent::EVENT_ARRIVED_DESTINATION_HUB,
        'status_after_event' => ShipmentRecord::STATUS_IN_TRANSIT,
        'event_at' => now()->subMinutes(2),
    ]);

    $this->loginAsAdmin();

    post(route('admin.sales.shipment-operations.store-event', $shipmentRecord), [
        'event_type' => ShipmentEvent::EVENT_DELIVERY_ATTEMPTED,
        'status_after_event' => ShipmentRecord::STATUS_OUT_FOR_DELIVERY,
        'note' => 'Parcel moved to last-mile delivery queue.',
    ])->assertRedirect(route('admin.sales.shipment-operations.view', $shipmentRecord));

    $shipmentRecord->refresh();
    $latestEvent = ShipmentEvent::query()
        ->where('shipment_record_id', $shipmentRecord->id)
        ->latest('id')
        ->firstOrFail();

    expect($shipmentRecord->status)->toBe(ShipmentRecord::STATUS_OUT_FOR_DELIVERY)
        ->and($shipmentRecord->returned_at)->toBeNull()
        ->and($latestEvent->event_type)->toBe(ShipmentEvent::EVENT_DELIVERY_ATTEMPTED)
        ->and($latestEvent->status_after_event)->toBe(ShipmentRecord::STATUS_OUT_FOR_DELIVERY);
});

it('records a delivery failure with attempt metadata and reattempt flag', function () {
    $fixture = createShipmentRecordFixture();

    $shipmentRecord = ShipmentRecord::query()->create([
        'order_id' => $fixture['order']->id,
        'status' => ShipmentRecord::STATUS_OUT_FOR_DELIVERY,
        'carrier_name_snapshot' => 'Manual Carrier',
        'tracking_number' => 'TRACK-963',
        'recipient_name' => $fixture['order']->customer_full_name,
        'recipient_phone' => $fixture['order']->shipping_address->phone,
        'recipient_address' => $fixture['order']->shipping_address->address,
        'handed_over_at' => now()->subDay(),
    ]);

    ShipmentEvent::query()->create([
        'shipment_record_id' => $shipmentRecord->id,
        'event_type' => ShipmentEvent::EVENT_ARRIVED_DESTINATION_HUB,
        'status_after_event' => ShipmentRecord::STATUS_OUT_FOR_DELIVERY,
        'event_at' => now()->subHour(),
    ]);

    $this->loginAsAdmin();

    post(route('admin.sales.shipment-operations.record-delivery-failure', $shipmentRecord), [
        'failure_reason' => ShipmentRecord::FAILURE_REASON_CUSTOMER_UNREACHABLE,
        'requires_reattempt' => '1',
        'note' => 'Courier called the customer twice without response.',
    ])->assertRedirect(route('admin.sales.shipment-operations.view', $shipmentRecord));

    $shipmentRecord->refresh();
    $latestEvent = ShipmentEvent::query()
        ->where('shipment_record_id', $shipmentRecord->id)
        ->latest('id')
        ->firstOrFail();

    expect($shipmentRecord->status)->toBe(ShipmentRecord::STATUS_DELIVERY_FAILED)
        ->and($shipmentRecord->delivery_attempt_count)->toBe(1)
        ->and($shipmentRecord->delivery_failure_reason)->toBe(ShipmentRecord::FAILURE_REASON_CUSTOMER_UNREACHABLE)
        ->and($shipmentRecord->requires_reattempt)->toBeTrue()
        ->and($shipmentRecord->last_delivery_attempt_at)->not->toBeNull()
        ->and($latestEvent->event_type)->toBe(ShipmentEvent::EVENT_CUSTOMER_UNREACHABLE)
        ->and(Arr::get($latestEvent->meta, 'attempt_count'))->toBe(1)
        ->and(Arr::get($latestEvent->meta, 'failure_reason'))->toBe(ShipmentRecord::FAILURE_REASON_CUSTOMER_UNREACHABLE)
        ->and(Arr::get($latestEvent->meta, 'requires_reattempt'))->toBeTrue();
});

it('approves a shipment reattempt and returns the shipment to out for delivery', function () {
    $fixture = createShipmentRecordFixture();

    $shipmentRecord = ShipmentRecord::query()->create([
        'order_id' => $fixture['order']->id,
        'status' => ShipmentRecord::STATUS_DELIVERY_FAILED,
        'carrier_name_snapshot' => 'Manual Carrier',
        'tracking_number' => 'TRACK-357',
        'recipient_name' => $fixture['order']->customer_full_name,
        'recipient_phone' => $fixture['order']->shipping_address->phone,
        'recipient_address' => $fixture['order']->shipping_address->address,
        'delivery_attempt_count' => 1,
        'delivery_failure_reason' => ShipmentRecord::FAILURE_REASON_CUSTOMER_UNREACHABLE,
        'requires_reattempt' => true,
        'last_delivery_attempt_at' => now()->subHour(),
        'handed_over_at' => now()->subDay(),
    ]);

    ShipmentEvent::query()->create([
        'shipment_record_id' => $shipmentRecord->id,
        'event_type' => ShipmentEvent::EVENT_CUSTOMER_UNREACHABLE,
        'status_after_event' => ShipmentRecord::STATUS_DELIVERY_FAILED,
        'event_at' => now()->subHour(),
        'meta' => [
            'attempt_count' => 1,
            'failure_reason' => ShipmentRecord::FAILURE_REASON_CUSTOMER_UNREACHABLE,
            'requires_reattempt' => true,
        ],
    ]);

    $this->loginAsAdmin();

    post(route('admin.sales.shipment-operations.approve-reattempt', $shipmentRecord), [
        'note' => 'Customer confirmed availability for tomorrow.',
    ])->assertRedirect(route('admin.sales.shipment-operations.view', $shipmentRecord));

    $shipmentRecord->refresh();
    $latestEvent = ShipmentEvent::query()
        ->where('shipment_record_id', $shipmentRecord->id)
        ->latest('id')
        ->firstOrFail();

    expect($shipmentRecord->status)->toBe(ShipmentRecord::STATUS_OUT_FOR_DELIVERY)
        ->and($shipmentRecord->requires_reattempt)->toBeFalse()
        ->and($latestEvent->event_type)->toBe(ShipmentEvent::EVENT_REATTEMPT_APPROVED)
        ->and($latestEvent->status_after_event)->toBe(ShipmentRecord::STATUS_OUT_FOR_DELIVERY);
});

it('supports initiating and completing a shipment return', function () {
    $fixture = createShipmentRecordFixture();

    $shipmentRecord = ShipmentRecord::query()->create([
        'order_id' => $fixture['order']->id,
        'status' => ShipmentRecord::STATUS_DELIVERY_FAILED,
        'carrier_name_snapshot' => 'Manual Carrier',
        'tracking_number' => 'TRACK-654',
        'recipient_name' => $fixture['order']->customer_full_name,
        'recipient_phone' => $fixture['order']->shipping_address->phone,
        'recipient_address' => $fixture['order']->shipping_address->address,
        'delivery_attempt_count' => 2,
        'delivery_failure_reason' => ShipmentRecord::FAILURE_REASON_CUSTOMER_REFUSED,
        'requires_reattempt' => false,
        'last_delivery_attempt_at' => now()->subHours(2),
        'handed_over_at' => now()->subDays(2),
    ]);

    ShipmentEvent::query()->create([
        'shipment_record_id' => $shipmentRecord->id,
        'event_type' => ShipmentEvent::EVENT_CUSTOMER_REFUSED,
        'status_after_event' => ShipmentRecord::STATUS_DELIVERY_FAILED,
        'event_at' => now()->subHours(2),
        'meta' => [
            'attempt_count' => 2,
            'failure_reason' => ShipmentRecord::FAILURE_REASON_CUSTOMER_REFUSED,
            'requires_reattempt' => false,
        ],
    ]);

    $this->loginAsAdmin();

    post(route('admin.sales.shipment-operations.initiate-return', $shipmentRecord), [
        'note' => 'Courier instructed to return the parcel to origin.',
    ])->assertRedirect(route('admin.sales.shipment-operations.view', $shipmentRecord));

    $shipmentRecord->refresh();

    expect($shipmentRecord->status)->toBe(ShipmentRecord::STATUS_DELIVERY_FAILED)
        ->and($shipmentRecord->return_initiated_at)->not->toBeNull()
        ->and($shipmentRecord->requires_reattempt)->toBeFalse();

    post(route('admin.sales.shipment-operations.complete-return', $shipmentRecord), [
        'note' => 'Parcel received back at warehouse.',
    ])->assertRedirect(route('admin.sales.shipment-operations.view', $shipmentRecord));

    $shipmentRecord->refresh();
    $latestEvent = ShipmentEvent::query()
        ->where('shipment_record_id', $shipmentRecord->id)
        ->latest('id')
        ->firstOrFail();

    expect($shipmentRecord->status)->toBe(ShipmentRecord::STATUS_RETURNED)
        ->and($shipmentRecord->returned_at)->not->toBeNull()
        ->and($latestEvent->event_type)->toBe(ShipmentEvent::EVENT_RETURN_COMPLETED)
        ->and($latestEvent->status_after_event)->toBe(ShipmentRecord::STATUS_RETURNED);
});

it('queues customer and admin shipment communications for out for delivery events', function () {
    Mail::fake();

    $fixture = createShipmentRecordFixture();

    $shipmentRecord = ShipmentRecord::query()->create([
        'order_id' => $fixture['order']->id,
        'status' => ShipmentRecord::STATUS_IN_TRANSIT,
        'carrier_name_snapshot' => 'Manual Carrier',
        'tracking_number' => 'TRACK-NOTIFY-001',
        'recipient_name' => $fixture['order']->customer_full_name,
        'recipient_phone' => $fixture['order']->shipping_address->phone,
        'recipient_address' => $fixture['order']->shipping_address->address,
        'handed_over_at' => now()->subDay(),
    ]);

    ShipmentEvent::query()->create([
        'shipment_record_id' => $shipmentRecord->id,
        'event_type' => ShipmentEvent::EVENT_ARRIVED_DESTINATION_HUB,
        'status_after_event' => ShipmentRecord::STATUS_IN_TRANSIT,
        'event_at' => now()->subHour(),
    ]);

    $this->loginAsAdmin();

    post(route('admin.sales.shipment-operations.store-event', $shipmentRecord), [
        'event_type' => ShipmentEvent::EVENT_DELIVERY_ATTEMPTED,
        'status_after_event' => ShipmentRecord::STATUS_OUT_FOR_DELIVERY,
        'note' => 'Rider assigned for the final delivery route.',
    ])->assertRedirect(route('admin.sales.shipment-operations.view', $shipmentRecord));

    Mail::assertQueued(ShopOperationalUpdateNotification::class);
    Mail::assertQueued(AdminOperationalUpdateNotification::class);

    expect(ShipmentCommunication::query()->where('shipment_record_id', $shipmentRecord->id)->count())->toBe(2)
        ->and(ShipmentCommunication::query()->where('shipment_record_id', $shipmentRecord->id)->where('status', ShipmentCommunication::STATUS_QUEUED)->count())->toBe(2)
        ->and(ShipmentCommunication::query()->where('shipment_record_id', $shipmentRecord->id)->where('notification_key', ShipmentCommunication::KEY_OUT_FOR_DELIVERY)->count())->toBe(2);

    get(route('admin.sales.shipment-operations.view', $shipmentRecord))
        ->assertOk()
        ->assertSeeText('Communications')
        ->assertSeeText('Out for Delivery')
        ->assertSeeText('Queued');
});

it('records skipped shipment communications when the notification is disabled', function () {
    Mail::fake();

    setShipmentNotificationConfig('sales.shipment_notifications.customer_delivered_email', 0);
    setShipmentNotificationConfig('sales.shipment_notifications.admin_delivered_email', 0);

    $fixture = createShipmentRecordFixture();

    $shipmentRecord = ShipmentRecord::query()->create([
        'order_id' => $fixture['order']->id,
        'status' => ShipmentRecord::STATUS_OUT_FOR_DELIVERY,
        'carrier_name_snapshot' => 'Manual Carrier',
        'tracking_number' => 'TRACK-NOTIFY-002',
        'recipient_name' => $fixture['order']->customer_full_name,
        'recipient_phone' => $fixture['order']->shipping_address->phone,
        'recipient_address' => $fixture['order']->shipping_address->address,
        'handed_over_at' => now()->subDay(),
    ]);

    ShipmentEvent::query()->create([
        'shipment_record_id' => $shipmentRecord->id,
        'event_type' => ShipmentEvent::EVENT_DELIVERY_ATTEMPTED,
        'status_after_event' => ShipmentRecord::STATUS_OUT_FOR_DELIVERY,
        'event_at' => now()->subHour(),
    ]);

    $this->loginAsAdmin();

    post(route('admin.sales.shipment-operations.update-status', $shipmentRecord), [
        'status' => ShipmentRecord::STATUS_DELIVERED,
        'note' => 'Delivered successfully to the customer.',
    ])->assertRedirect(route('admin.sales.shipment-operations.view', $shipmentRecord));

    Mail::assertNothingQueued();

    expect(ShipmentCommunication::query()->where('shipment_record_id', $shipmentRecord->id)->count())->toBe(2)
        ->and(ShipmentCommunication::query()->where('shipment_record_id', $shipmentRecord->id)->where('status', ShipmentCommunication::STATUS_SKIPPED)->count())->toBe(2);
});

it('queues shipment communications when a return is initiated', function () {
    Mail::fake();

    $fixture = createShipmentRecordFixture();

    $shipmentRecord = ShipmentRecord::query()->create([
        'order_id' => $fixture['order']->id,
        'status' => ShipmentRecord::STATUS_DELIVERY_FAILED,
        'carrier_name_snapshot' => 'Manual Carrier',
        'tracking_number' => 'TRACK-NOTIFY-003',
        'recipient_name' => $fixture['order']->customer_full_name,
        'recipient_phone' => $fixture['order']->shipping_address->phone,
        'recipient_address' => $fixture['order']->shipping_address->address,
        'delivery_attempt_count' => 1,
        'delivery_failure_reason' => ShipmentRecord::FAILURE_REASON_CUSTOMER_REFUSED,
        'last_delivery_attempt_at' => now()->subHours(3),
        'handed_over_at' => now()->subDay(),
    ]);

    ShipmentEvent::query()->create([
        'shipment_record_id' => $shipmentRecord->id,
        'event_type' => ShipmentEvent::EVENT_CUSTOMER_REFUSED,
        'status_after_event' => ShipmentRecord::STATUS_DELIVERY_FAILED,
        'event_at' => now()->subHours(3),
    ]);

    $this->loginAsAdmin();

    post(route('admin.sales.shipment-operations.initiate-return', $shipmentRecord), [
        'note' => 'Courier branch instructed to return the parcel.',
    ])->assertRedirect(route('admin.sales.shipment-operations.view', $shipmentRecord));

    Mail::assertQueued(ShopOperationalUpdateNotification::class);
    Mail::assertQueued(AdminOperationalUpdateNotification::class);

    expect(ShipmentCommunication::query()
        ->where('shipment_record_id', $shipmentRecord->id)
        ->where('notification_key', ShipmentCommunication::KEY_RETURN_INITIATED)
        ->count())->toBe(2);
});

it('keeps placeholder driver sync behavior for non-integrated carriers', function () {
    $fixture = createShipmentRecordFixture();

    $carrier = ShipmentCarrier::query()->create([
        'code' => 'custom_api',
        'name' => 'Custom API',
        'integration_driver' => 'custom_api',
        'tracking_sync_enabled' => true,
        'is_active' => true,
    ]);

    $shipmentRecord = ShipmentRecord::query()->create([
        'order_id' => $fixture['order']->id,
        'shipment_carrier_id' => $carrier->id,
        'status' => ShipmentRecord::STATUS_IN_TRANSIT,
        'carrier_name_snapshot' => $carrier->name,
        'tracking_number' => 'TRACK-SYNC-001',
        'recipient_name' => $fixture['order']->customer_full_name,
        'recipient_phone' => $fixture['order']->shipping_address->phone,
        'recipient_address' => $fixture['order']->shipping_address->address,
        'handed_over_at' => now(),
    ]);

    $this->loginAsAdmin();

    post(route('admin.sales.shipment-operations.sync-tracking', $shipmentRecord))
        ->assertRedirect(route('admin.sales.shipment-operations.view', $shipmentRecord));

    $shipmentRecord->refresh();

    expect($shipmentRecord->last_tracking_synced_at)->not->toBeNull()
        ->and($shipmentRecord->last_tracking_sync_status)->toBe('pending')
        ->and($shipmentRecord->last_tracking_sync_message)->toContain('foundation is configured');
});

it('syncs pathao tracking and updates shipment status when mapped status changes', function () {
    $fixture = createShipmentRecordFixture();

    Mail::fake();

    Http::fake([
        'https://courier-api-sandbox.pathao.com/aladdin/api/v1/issue-token' => Http::response([
            'access_token' => 'pathao-access-token',
        ], 200),
        'https://courier-api-sandbox.pathao.com/aladdin/api/v1/orders/PA-TRACK-001' => Http::response([
            'data' => [
                'message' => 'Order details fetched successfully.',
                'data' => [
                    'consignment_id' => 'PA-TRACK-001',
                    'order_status' => 'Out for Delivery',
                    'order_status_slug' => 'out_for_delivery',
                    'updated_at' => '2026-04-20 12:30:00',
                    'invoice_id' => 'INV-123',
                ],
            ],
        ], 200),
    ]);

    $carrier = ShipmentCarrier::query()->create([
        'code' => 'pathao',
        'name' => 'Pathao Courier',
        'integration_driver' => 'pathao',
        'tracking_sync_enabled' => true,
        'api_base_url' => 'https://courier-api-sandbox.pathao.com/aladdin/api/v1',
        'api_store_id' => 169218,
        'api_key' => 'client-id',
        'api_secret' => 'client-secret',
        'api_username' => 'merchant@example.com',
        'api_password' => 'merchant-password',
        'contact_name' => 'Merchant Contact',
        'contact_phone' => '01711111111',
        'is_active' => true,
    ]);

    $shipmentRecord = ShipmentRecord::query()->create([
        'order_id' => $fixture['order']->id,
        'shipment_carrier_id' => $carrier->id,
        'status' => ShipmentRecord::STATUS_IN_TRANSIT,
        'carrier_name_snapshot' => $carrier->name,
        'tracking_number' => 'PA-TRACK-001',
        'carrier_consignment_id' => 'PA-TRACK-001',
        'recipient_name' => $fixture['order']->customer_full_name,
        'recipient_phone' => $fixture['order']->shipping_address->phone,
        'recipient_address' => $fixture['order']->shipping_address->address,
        'destination_city' => 'Dhaka',
        'destination_region' => 'Dhaka',
        'destination_country' => 'Bangladesh',
        'handed_over_at' => now(),
    ]);

    $this->loginAsAdmin();

    post(route('admin.sales.shipment-operations.sync-tracking', $shipmentRecord))
        ->assertRedirect(route('admin.sales.shipment-operations.view', $shipmentRecord));

    $shipmentRecord->refresh();

    $latestEvent = ShipmentEvent::query()
        ->where('shipment_record_id', $shipmentRecord->id)
        ->latest('id')
        ->firstOrFail();

    expect($shipmentRecord->status)->toBe(ShipmentRecord::STATUS_OUT_FOR_DELIVERY)
        ->and($shipmentRecord->last_tracking_synced_at)->not->toBeNull()
        ->and($shipmentRecord->last_tracking_sync_status)->toBe('synced')
        ->and($shipmentRecord->last_tracking_sync_message)->toContain('Pathao tracking synced')
        ->and($latestEvent->note)->toContain('Pathao tracking sync');

    Http::assertSentCount(2);

    Http::assertSent(fn ($request) => $request->url() === 'https://courier-api-sandbox.pathao.com/aladdin/api/v1/issue-token'
        && $request['client_id'] === 'client-id'
        && $request['client_secret'] === 'client-secret'
        && $request['username'] === 'merchant@example.com'
        && $request['password'] === 'merchant-password');

    Http::assertSent(fn ($request) => $request->method() === 'GET'
        && $request->url() === 'https://courier-api-sandbox.pathao.com/aladdin/api/v1/orders/PA-TRACK-001');

    Mail::assertQueued(ShopOperationalUpdateNotification::class);
    Mail::assertQueued(AdminOperationalUpdateNotification::class);
});

it('syncs steadfast tracking and updates shipment status when mapped status changes', function () {
    $fixture = createShipmentRecordFixture();

    Mail::fake();

    Http::fake([
        'https://api.steadfast.test/*' => Http::response([
            'delivery_status' => 'out_for_delivery',
        ], 200),
    ]);

    $carrier = ShipmentCarrier::query()->create([
        'code' => 'steadfast',
        'name' => 'Steadfast Courier',
        'integration_driver' => 'steadfast',
        'tracking_sync_enabled' => true,
        'api_base_url' => 'https://api.steadfast.test/track/{tracking_number}',
        'api_key' => 'test-key',
        'api_secret' => 'test-secret',
        'is_active' => true,
    ]);

    $shipmentRecord = ShipmentRecord::query()->create([
        'order_id' => $fixture['order']->id,
        'shipment_carrier_id' => $carrier->id,
        'status' => ShipmentRecord::STATUS_IN_TRANSIT,
        'carrier_name_snapshot' => $carrier->name,
        'tracking_number' => 'TRACK-STEADFAST-001',
        'recipient_name' => $fixture['order']->customer_full_name,
        'recipient_phone' => $fixture['order']->shipping_address->phone,
        'recipient_address' => $fixture['order']->shipping_address->address,
        'handed_over_at' => now(),
    ]);

    $this->loginAsAdmin();

    post(route('admin.sales.shipment-operations.sync-tracking', $shipmentRecord))
        ->assertRedirect(route('admin.sales.shipment-operations.view', $shipmentRecord));

    $shipmentRecord->refresh();

    $latestEvent = ShipmentEvent::query()
        ->where('shipment_record_id', $shipmentRecord->id)
        ->latest('id')
        ->firstOrFail();

    expect($shipmentRecord->status)->toBe(ShipmentRecord::STATUS_OUT_FOR_DELIVERY)
        ->and($shipmentRecord->last_tracking_synced_at)->not->toBeNull()
        ->and($shipmentRecord->last_tracking_sync_status)->toBe('synced')
        ->and($shipmentRecord->last_tracking_sync_message)->toContain('mapped to')
        ->and($latestEvent->note)->toContain('Steadfast tracking sync');

    Mail::assertQueued(ShopOperationalUpdateNotification::class);
    Mail::assertQueued(AdminOperationalUpdateNotification::class);
});

it('stores carrier booking references on shipment ops without triggering shipment notifications', function () {
    $fixture = createShipmentRecordFixture();

    Mail::fake();

    $carrier = ShipmentCarrier::query()->create([
        'code' => 'steadfast',
        'name' => 'Steadfast Courier',
        'integration_driver' => 'steadfast',
        'tracking_sync_enabled' => true,
        'is_active' => true,
    ]);

    $shipmentRecord = ShipmentRecord::query()->create([
        'order_id' => $fixture['order']->id,
        'shipment_carrier_id' => $carrier->id,
        'status' => ShipmentRecord::STATUS_IN_TRANSIT,
        'carrier_name_snapshot' => $carrier->name,
        'tracking_number' => 'TRACK-BOOKING-001',
        'recipient_name' => $fixture['order']->customer_full_name,
        'recipient_phone' => $fixture['order']->shipping_address->phone,
        'recipient_address' => $fixture['order']->shipping_address->address,
        'handed_over_at' => now(),
    ]);

    $this->loginAsAdmin();

    post(route('admin.sales.shipment-operations.update-booking-references', $shipmentRecord), [
        'carrier_booking_reference' => 'BOOK-123',
        'carrier_consignment_id' => 'CONSIGN-123',
        'carrier_invoice_reference' => 'INV-123',
        'carrier_booked_at' => '2026-04-19 18:30:00',
        'note' => 'Mapped from the courier booking panel.',
    ])->assertRedirect(route('admin.sales.shipment-operations.view', $shipmentRecord));

    $shipmentRecord->refresh();

    $latestEvent = ShipmentEvent::query()
        ->where('shipment_record_id', $shipmentRecord->id)
        ->latest('id')
        ->firstOrFail();

    expect($shipmentRecord->carrier_booking_reference)->toBe('BOOK-123')
        ->and($shipmentRecord->carrier_consignment_id)->toBe('CONSIGN-123')
        ->and($shipmentRecord->carrier_invoice_reference)->toBe('INV-123')
        ->and($shipmentRecord->carrier_booked_at?->format('Y-m-d H:i:s'))->toBe('2026-04-19 18:30:00')
        ->and($latestEvent->event_type)->toBe(ShipmentEvent::EVENT_BOOKING_REFERENCES_UPDATED)
        ->and($latestEvent->status_after_event)->toBeNull()
        ->and(data_get($latestEvent->meta, 'carrier_consignment_id'))->toBe('CONSIGN-123')
        ->and(ShipmentCommunication::query()->where('shipment_record_id', $shipmentRecord->id)->count())->toBe(0);

    Mail::assertNothingQueued();
});

it('books a pathao consignment from shipment ops and persists returned courier references', function () {
    $fixture = createShipmentRecordFixture();

    Mail::fake();

    $carrier = ShipmentCarrier::query()->create([
        'code' => 'pathao',
        'name' => 'Pathao Courier',
        'integration_driver' => 'pathao',
        'tracking_sync_enabled' => true,
        'api_base_url' => 'https://courier-api-sandbox.pathao.com/aladdin/api/v1',
        'api_store_id' => 169218,
        'api_key' => 'client-id',
        'api_secret' => 'client-secret',
        'api_username' => 'merchant@example.com',
        'api_password' => 'merchant-password',
        'contact_name' => 'Merchant Contact',
        'contact_phone' => '01711111111',
        'is_active' => true,
    ]);

    $shipmentRecord = ShipmentRecord::query()->create([
        'order_id' => $fixture['order']->id,
        'shipment_carrier_id' => $carrier->id,
        'status' => ShipmentRecord::STATUS_HANDED_TO_CARRIER,
        'carrier_name_snapshot' => $carrier->name,
        'recipient_name' => $fixture['order']->customer_full_name,
        'recipient_phone' => $fixture['order']->shipping_address->phone,
        'recipient_address' => 'House 58, Khansamarchock',
        'destination_city' => 'Dhaka',
        'destination_region' => 'Dhaka',
        'destination_country' => 'Bangladesh',
        'cod_amount_expected' => 499,
        'handed_over_at' => now(),
    ]);

    $merchantOrderId = sprintf('%s-S%s', $fixture['order']->increment_id, $shipmentRecord->id);

    Http::fake([
        'https://courier-api-sandbox.pathao.com/aladdin/api/v1/issue-token' => Http::response([
            'access_token' => 'pathao-access-token',
        ], 200),
        'https://courier-api-sandbox.pathao.com/aladdin/api/v1/city-list' => Http::response([
            'data' => [
                ['id' => 1, 'name' => 'Dhaka'],
                ['id' => 2, 'name' => 'Chattogram'],
            ],
        ], 200),
        'https://courier-api-sandbox.pathao.com/aladdin/api/v1/cities/1/zone-list' => Http::response([
            'data' => [
                ['id' => 11, 'name' => 'Dhanmondi'],
            ],
        ], 200),
        'https://courier-api-sandbox.pathao.com/aladdin/api/v1/zones/11/area-list' => Http::response([
            'data' => [
                ['id' => 21, 'name' => 'Dhanmondi'],
            ],
        ], 200),
        'https://courier-api-sandbox.pathao.com/aladdin/api/v1/orders' => Http::response([
            'message' => 'Order Created Successfully',
            'data' => [
                'order_id' => 'PATHAO-ORDER-8899',
                'consignment_id' => 'PA-10001',
                'tracking_code' => 'PA-10001',
                'created_at' => '2026-04-20T10:30:00.000000Z',
            ],
        ], 200),
    ]);

    $this->loginAsAdmin();

    post(route('admin.sales.shipment-operations.book-with-carrier', $shipmentRecord), [
        'note' => 'Call customer before delivery.',
    ])->assertRedirect(route('admin.sales.shipment-operations.view', $shipmentRecord));

    $shipmentRecord->refresh();

    $latestEvent = ShipmentEvent::query()
        ->where('shipment_record_id', $shipmentRecord->id)
        ->latest('id')
        ->firstOrFail();

    expect($shipmentRecord->tracking_number)->toBe('PA-10001')
        ->and($shipmentRecord->carrier_booking_reference)->toBe('PATHAO-ORDER-8899')
        ->and($shipmentRecord->carrier_consignment_id)->toBe('PA-10001')
        ->and($shipmentRecord->carrier_invoice_reference)->toBe($merchantOrderId)
        ->and($shipmentRecord->carrier_booked_at?->toIso8601String())->toContain('2026-04-20T10:30:00')
        ->and($latestEvent->event_type)->toBe(ShipmentEvent::EVENT_CARRIER_BOOKED)
        ->and($latestEvent->status_after_event)->toBeNull()
        ->and(data_get($latestEvent->meta, 'merchant_order_id'))->toBe($merchantOrderId)
        ->and(ShipmentCommunication::query()->where('shipment_record_id', $shipmentRecord->id)->count())->toBe(0);

    Http::assertSentCount(5);

    Http::assertSent(fn ($request) => $request->url() === 'https://courier-api-sandbox.pathao.com/aladdin/api/v1/issue-token'
        && $request['client_id'] === 'client-id'
        && $request['client_secret'] === 'client-secret'
        && $request['username'] === 'merchant@example.com'
        && $request['password'] === 'merchant-password');

    Http::assertSent(fn ($request) => $request->url() === 'https://courier-api-sandbox.pathao.com/aladdin/api/v1/orders'
        && $request['store_id'] === 169218
        && $request['merchant_order_id'] === $merchantOrderId
        && $request['sender_name'] === 'Merchant Contact'
        && $request['sender_phone'] === '01711111111'
        && $request['recipient_name'] === $fixture['order']->customer_full_name
        && $request['recipient_phone'] === $fixture['order']->shipping_address->phone
        && $request['recipient_city'] === 1
        && $request['recipient_zone'] === 11
        && $request['recipient_area'] === 21
        && (float) $request['amount_to_collect'] === 499.0);

    Mail::assertNothingQueued();
});

it('books a steadfast consignment from shipment ops and persists returned courier references', function () {
    $fixture = createShipmentRecordFixture();

    Mail::fake();

    $carrier = ShipmentCarrier::query()->create([
        'code' => 'steadfast',
        'name' => 'Steadfast Courier',
        'integration_driver' => 'steadfast',
        'tracking_sync_enabled' => true,
        'api_base_url' => 'https://api.steadfast.test',
        'api_key' => 'test-key',
        'api_secret' => 'test-secret',
        'is_active' => true,
    ]);

    $shipmentRecord = ShipmentRecord::query()->create([
        'order_id' => $fixture['order']->id,
        'shipment_carrier_id' => $carrier->id,
        'status' => ShipmentRecord::STATUS_HANDED_TO_CARRIER,
        'carrier_name_snapshot' => $carrier->name,
        'recipient_name' => $fixture['order']->customer_full_name,
        'recipient_phone' => $fixture['order']->shipping_address->phone,
        'recipient_address' => $fixture['order']->shipping_address->address,
        'destination_city' => $fixture['order']->shipping_address->city,
        'destination_region' => $fixture['order']->shipping_address->state,
        'destination_country' => $fixture['order']->shipping_address->country,
        'cod_amount_expected' => 499,
        'handed_over_at' => now(),
    ]);

    $expectedInvoice = sprintf('%s-S%s', $fixture['order']->increment_id, $shipmentRecord->id);

    Http::fake([
        'https://api.steadfast.test/create_order' => Http::response([
            'status' => 200,
            'message' => 'Consignment has been created successfully.',
            'consignment' => [
                'consignment_id' => 1424107,
                'invoice' => $expectedInvoice,
                'tracking_code' => '15BAEB8A',
                'created_at' => '2026-04-19T07:05:31.000000Z',
            ],
        ], 200),
    ]);

    $this->loginAsAdmin();

    post(route('admin.sales.shipment-operations.book-with-carrier', $shipmentRecord), [
        'note' => 'Fragile item. Call before delivery.',
    ])->assertRedirect(route('admin.sales.shipment-operations.view', $shipmentRecord));

    $shipmentRecord->refresh();

    $latestEvent = ShipmentEvent::query()
        ->where('shipment_record_id', $shipmentRecord->id)
        ->latest('id')
        ->firstOrFail();

    expect($shipmentRecord->tracking_number)->toBe('15BAEB8A')
        ->and($shipmentRecord->carrier_consignment_id)->toBe('1424107')
        ->and($shipmentRecord->carrier_invoice_reference)->toBe($expectedInvoice)
        ->and($shipmentRecord->carrier_booked_at?->toIso8601String())->toContain('2026-04-19T07:05:31')
        ->and($latestEvent->event_type)->toBe(ShipmentEvent::EVENT_CARRIER_BOOKED)
        ->and($latestEvent->status_after_event)->toBeNull()
        ->and(data_get($latestEvent->meta, 'external_message'))->toBe('Consignment has been created successfully.')
        ->and(ShipmentCommunication::query()->where('shipment_record_id', $shipmentRecord->id)->count())->toBe(0);

    Http::assertSent(function ($request) use ($expectedInvoice) {
        return $request->url() === 'https://api.steadfast.test/create_order'
            && $request['invoice'] === $expectedInvoice
            && $request['recipient_name'] !== null
            && $request['recipient_phone'] !== null
            && $request['recipient_address'] !== null
            && (float) $request['cod_amount'] === 499.0
            && $request['note'] === 'Fragile item. Call before delivery.';
    });

    Mail::assertNothingQueued();
});

it('does not create a duplicate automated courier booking when a shipment is already booked', function () {
    $fixture = createShipmentRecordFixture();

    Http::fake();

    $carrier = ShipmentCarrier::query()->create([
        'code' => 'steadfast',
        'name' => 'Steadfast Courier',
        'integration_driver' => 'steadfast',
        'tracking_sync_enabled' => true,
        'api_base_url' => 'https://api.steadfast.test',
        'api_key' => 'test-key',
        'api_secret' => 'test-secret',
        'is_active' => true,
    ]);

    $shipmentRecord = ShipmentRecord::query()->create([
        'order_id' => $fixture['order']->id,
        'shipment_carrier_id' => $carrier->id,
        'status' => ShipmentRecord::STATUS_HANDED_TO_CARRIER,
        'carrier_name_snapshot' => $carrier->name,
        'carrier_consignment_id' => 'CONSIGN-EXISTING',
        'tracking_number' => 'TRACK-EXISTING',
        'recipient_name' => $fixture['order']->customer_full_name,
        'recipient_phone' => $fixture['order']->shipping_address->phone,
        'recipient_address' => $fixture['order']->shipping_address->address,
        'handed_over_at' => now(),
    ]);

    $this->loginAsAdmin();

    post(route('admin.sales.shipment-operations.book-with-carrier', $shipmentRecord), [])
        ->assertRedirect(route('admin.sales.shipment-operations.view', $shipmentRecord))
        ->assertSessionHas('warning', 'Carrier booking already exists for this shipment.');

    Http::assertNothingSent();
});

it('queues shipment tracking sync jobs from the command', function () {
    $fixture = createShipmentRecordFixture();

    $carrier = ShipmentCarrier::query()->create([
        'code' => 'pathao',
        'name' => 'Pathao',
        'integration_driver' => 'pathao',
        'tracking_sync_enabled' => true,
        'is_active' => true,
    ]);

    $shipmentRecord = ShipmentRecord::query()->create([
        'order_id' => $fixture['order']->id,
        'shipment_carrier_id' => $carrier->id,
        'status' => ShipmentRecord::STATUS_IN_TRANSIT,
        'carrier_name_snapshot' => $carrier->name,
        'tracking_number' => 'TRACK-CMD-001',
        'recipient_name' => $fixture['order']->customer_full_name,
        'recipient_phone' => $fixture['order']->shipping_address->phone,
        'recipient_address' => $fixture['order']->shipping_address->address,
        'handed_over_at' => now(),
    ]);

    Bus::fake();

    $this->artisan('platform:shipments:sync-tracking', [
        '--carrier' => 'pathao',
    ])->assertExitCode(0);

    Bus::assertDispatched(SyncShipmentTrackingJob::class, fn (SyncShipmentTrackingJob $job) => $job->shipmentRecordId === $shipmentRecord->id);
});

function createShipmentRecordFixture(): array
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

function setShipmentNotificationConfig(string $code, mixed $value): void
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

function setAdminShippingMode(string $mode): void
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
