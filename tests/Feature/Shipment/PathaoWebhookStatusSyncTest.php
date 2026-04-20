<?php

use Illuminate\Support\Facades\Mail;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Models\ShipmentEvent;
use Platform\CommerceCore\Models\ShipmentRecord;
use Tests\TestCase;
use Webkul\Sales\Models\Order;

use function Pest\Laravel\postJson;

uses(TestCase::class);

it('syncs a pathao webhook payload by consignment id', function () {
    Mail::fake();

    $fixture = createPathaoWebhookFixture();

    postJson(
        route('commerce-core.webhooks.shipment-carriers.pathao', $fixture['carrier']),
        [
            'consignment_id' => $fixture['shipmentRecord']->carrier_consignment_id,
            'order_status' => 'Out for Delivery',
        ],
        [
            'X-PATHAO-Signature' => 'pathao-hook-secret',
        ],
    )->assertOk()
        ->assertJson([
            'status' => 'ok',
            'shipment_record_id' => $fixture['shipmentRecord']->id,
            'external_status' => 'Out for Delivery',
        ])
        ->assertHeader('X-Pathao-Merchant-Webhook-Integration-Secret', 'pathao-hook-secret');

    $fixture['shipmentRecord']->refresh();

    $latestEvent = ShipmentEvent::query()
        ->where('shipment_record_id', $fixture['shipmentRecord']->id)
        ->latest('id')
        ->firstOrFail();

    expect($fixture['shipmentRecord']->status)->toBe(ShipmentRecord::STATUS_OUT_FOR_DELIVERY)
        ->and($fixture['shipmentRecord']->last_tracking_synced_at)->not->toBeNull()
        ->and($fixture['shipmentRecord']->last_tracking_sync_status)->toBe('synced')
        ->and($fixture['shipmentRecord']->last_tracking_sync_message)->toContain('Pathao webhook synced')
        ->and($latestEvent->note)->toContain('Pathao webhook mapped');
});

it('syncs a pathao webhook payload by invoice reference when consignment id is absent', function () {
    Mail::fake();

    $fixture = createPathaoWebhookFixture([
        'carrier_invoice_reference' => 'PATHAO-INV-1001',
    ]);

    postJson(
        route('commerce-core.webhooks.shipment-carriers.pathao', $fixture['carrier']),
        [
            'invoice_id' => 'PATHAO-INV-1001',
            'order_status_slug' => 'delivered',
        ],
        [
            'X-PATHAO-Signature' => 'pathao-hook-secret',
        ],
    )->assertOk()
        ->assertJson([
            'status' => 'ok',
            'shipment_record_id' => $fixture['shipmentRecord']->id,
            'external_status' => 'delivered',
        ])
        ->assertHeader('X-Pathao-Merchant-Webhook-Integration-Secret', 'pathao-hook-secret');

    $fixture['shipmentRecord']->refresh();

    expect($fixture['shipmentRecord']->status)->toBe(ShipmentRecord::STATUS_DELIVERED)
        ->and($fixture['shipmentRecord']->delivered_at)->not->toBeNull()
        ->and($fixture['shipmentRecord']->last_tracking_sync_status)->toBe('synced');
});

it('rejects pathao webhook requests with an invalid signature', function () {
    $fixture = createPathaoWebhookFixture();

    postJson(
        route('commerce-core.webhooks.shipment-carriers.pathao', $fixture['carrier']),
        [
            'consignment_id' => $fixture['shipmentRecord']->carrier_consignment_id,
            'order_status' => 'out_for_delivery',
        ],
        [
            'X-PATHAO-Signature' => 'wrong-secret',
        ],
    )->assertUnauthorized()
        ->assertJson([
            'status' => 'unauthorized',
        ]);

    $fixture['shipmentRecord']->refresh();

    expect($fixture['shipmentRecord']->status)->toBe(ShipmentRecord::STATUS_IN_TRANSIT);
});

it('accepts unmapped pathao webhook statuses without changing shipment state', function () {
    $fixture = createPathaoWebhookFixture();

    postJson(
        route('commerce-core.webhooks.shipment-carriers.pathao', $fixture['carrier']),
        [
            'consignment_id' => $fixture['shipmentRecord']->carrier_consignment_id,
            'status' => 'handover_pending',
        ],
        [
            'X-PATHAO-Signature' => 'pathao-hook-secret',
        ],
    )->assertStatus(202)
        ->assertJson([
            'status' => 'accepted',
            'shipment_record_id' => $fixture['shipmentRecord']->id,
            'external_status' => 'handover_pending',
        ])
        ->assertHeader('X-Pathao-Merchant-Webhook-Integration-Secret', 'pathao-hook-secret');

    $fixture['shipmentRecord']->refresh();

    expect($fixture['shipmentRecord']->status)->toBe(ShipmentRecord::STATUS_IN_TRANSIT)
        ->and($fixture['shipmentRecord']->last_tracking_sync_status)->toBe('pending')
        ->and($fixture['shipmentRecord']->last_tracking_sync_message)->toContain('no local status mapping exists yet');
});

it('does not duplicate timeline events when the webhook repeats the current mapped status', function () {
    $fixture = createPathaoWebhookFixture([
        'status' => ShipmentRecord::STATUS_OUT_FOR_DELIVERY,
    ]);

    $initialEventCount = ShipmentEvent::query()
        ->where('shipment_record_id', $fixture['shipmentRecord']->id)
        ->count();

    postJson(
        route('commerce-core.webhooks.shipment-carriers.pathao', $fixture['carrier']),
        [
            'consignment_id' => $fixture['shipmentRecord']->carrier_consignment_id,
            'order_status_slug' => 'out_for_delivery',
        ],
        [
            'X-PATHAO-Signature' => 'pathao-hook-secret',
        ],
    )->assertOk()
        ->assertJson([
            'status' => 'ok',
        ]);

    $fixture['shipmentRecord']->refresh();

    expect(ShipmentEvent::query()->where('shipment_record_id', $fixture['shipmentRecord']->id)->count())->toBe($initialEventCount)
        ->and($fixture['shipmentRecord']->last_tracking_sync_message)->toContain('already matches');
});

function createPathaoWebhookFixture(array $shipmentOverrides = []): array
{
    $order = Order::factory()->create([
        'status' => Order::STATUS_PROCESSING,
        'customer_first_name' => 'Shafin',
        'customer_last_name' => 'Mia',
        'customer_email' => 'shafin@example.com',
    ]);

    $carrier = ShipmentCarrier::query()->create([
        'code' => 'pathao',
        'name' => 'Pathao Courier',
        'integration_driver' => 'pathao',
        'tracking_sync_enabled' => true,
        'webhook_secret' => 'pathao-hook-secret',
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

    $shipmentRecord = ShipmentRecord::query()->create(array_merge([
        'order_id' => $order->id,
        'shipment_carrier_id' => $carrier->id,
        'status' => ShipmentRecord::STATUS_IN_TRANSIT,
        'carrier_name_snapshot' => $carrier->name,
        'tracking_number' => 'PA-TRACK-001',
        'carrier_consignment_id' => 'PA-TRACK-001',
        'carrier_invoice_reference' => 'PA-INV-001',
        'recipient_name' => 'Shafin Mia',
        'recipient_phone' => '01723872851',
        'recipient_address' => 'House 58, Khansamarchock',
        'destination_city' => 'Dhaka',
        'destination_region' => 'Dhaka',
        'destination_country' => 'Bangladesh',
        'cod_amount_expected' => 499,
        'handed_over_at' => now()->subDay(),
    ], $shipmentOverrides));

    return [
        'order' => $order,
        'carrier' => $carrier,
        'shipmentRecord' => $shipmentRecord,
    ];
}
