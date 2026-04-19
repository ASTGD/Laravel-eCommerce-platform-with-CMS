<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;
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

it('syncs carrier tracking for a shipment record from admin shipment ops', function () {
    $fixture = createShipmentRecordFixture();

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
