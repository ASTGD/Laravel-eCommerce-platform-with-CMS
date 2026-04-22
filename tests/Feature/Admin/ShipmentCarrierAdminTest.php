<?php

use Platform\CommerceCore\Models\ShipmentCarrier;
use Webkul\Admin\Tests\AdminTestCase;
use Webkul\Core\Models\CoreConfig;

use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

uses(AdminTestCase::class);

it('shows the courier service admin index', function () {
    $this->loginAsAdmin();

    get(route('admin.sales.carriers.index'))
        ->assertOk()
        ->assertSeeText('Courier Services')
        ->assertSeeText('Add Courier Service');
});

it('shows a manual-first courier form in manual basic mode', function () {
    setShipmentCarrierShippingMode('manual_basic');

    $this->loginAsAdmin();

    get(route('admin.sales.carriers.create'))
        ->assertOk()
        ->assertSeeText('Add Courier Service')
        ->assertSeeText('Basic Information')
        ->assertSeeText('Courier Name')
        ->assertSeeText('Courier Code')
        ->assertSeeText('Contact Person')
        ->assertSeeText('Phone')
        ->assertSeeText('Address')
        ->assertSeeText('Tracking URL Template')
        ->assertSeeText('Supports COD')
        ->assertSeeText('Active')
        ->assertDontSeeText('Automation & API Connection (Pro)')
        ->assertDontSeeText('API URL')
        ->assertDontSeeText('Status Update Secret');
});

it('creates a manual courier service in manual basic mode', function () {
    setShipmentCarrierShippingMode('manual_basic');

    $this->loginAsAdmin();

    post(route('admin.sales.carriers.store'), [
        'name' => 'Sundarban Express',
        'code' => '',
        'contact_name' => 'Mr. Handover',
        'contact_phone' => '01711111111',
        'address' => 'Rajshahi courier office',
        'tracking_url_template' => 'https://track.example.com/{tracking_number}',
        'supports_cod' => 1,
        'is_active' => 1,
    ])->assertRedirect(route('admin.sales.carriers.create'));

    $carrier = ShipmentCarrier::query()->where('name', 'Sundarban Express')->firstOrFail();

    expect($carrier->code)->toBe('sundarban_express')
        ->and($carrier->integration_driver)->toBe('manual')
        ->and($carrier->contact_name)->toBe('Mr. Handover')
        ->and($carrier->contact_phone)->toBe('01711111111')
        ->and($carrier->address)->toBe('Rajshahi courier office')
        ->and($carrier->tracking_url_template)->toBe('https://track.example.com/{tracking_number}')
        ->and($carrier->supports_cod)->toBeTrue()
        ->and($carrier->is_active)->toBeTrue();
});

it('creates and updates a steadfast courier service in advanced pro mode', function () {
    setShipmentCarrierShippingMode('advanced_pro');

    $this->loginAsAdmin();

    post(route('admin.sales.carriers.store'), [
        'name' => 'Steadfast Courier',
        'code' => 'steadfast_courier_admin_test',
        'courier_service' => 'steadfast',
        'tracking_url_template' => 'https://carrier.example/track/{tracking_number}',
        'tracking_sync_enabled' => 1,
        'api_base_url' => 'https://api.steadfast.test',
        'api_username' => 'steadfast-api',
        'api_password' => 'top-secret-password',
        'api_key' => 'steadfast-key',
        'api_secret' => 'steadfast-secret',
        'webhook_secret' => 'steadfast-webhook',
        'supports_cod' => 1,
        'default_cod_fee_type' => 'flat',
        'default_cod_fee_amount' => '55.50',
        'default_return_fee_amount' => '120.00',
        'default_payout_method' => 'bkash',
        'sort_order' => 10,
        'is_active' => 1,
    ])->assertRedirect(route('admin.sales.carriers.create'));

    $carrier = ShipmentCarrier::query()->where('code', 'steadfast_courier_admin_test')->firstOrFail();

    expect($carrier->name)->toBe('Steadfast Courier')
        ->and($carrier->integration_driver)->toBe('steadfast')
        ->and($carrier->tracking_sync_enabled)->toBeTrue()
        ->and($carrier->api_base_url)->toBe('https://api.steadfast.test')
        ->and($carrier->api_username)->toBe('steadfast-api')
        ->and($carrier->api_password)->toBe('top-secret-password')
        ->and($carrier->api_key)->toBe('steadfast-key')
        ->and($carrier->api_secret)->toBe('steadfast-secret')
        ->and($carrier->webhook_secret)->toBe('steadfast-webhook')
        ->and($carrier->supports_cod)->toBeTrue()
        ->and((string) $carrier->default_cod_fee_amount)->toBe('55.50');

    put(route('admin.sales.carriers.update', $carrier), [
        'name' => 'Steadfast BD',
        'courier_service' => 'steadfast',
        'tracking_url_template' => 'https://carrier.example/trace/{tracking_number}',
        'tracking_sync_enabled' => 1,
        'api_base_url' => 'https://custom-api.steadfast.test',
        'api_username' => 'custom-user',
        'api_password' => '',
        'api_key' => '',
        'api_secret' => '',
        'webhook_secret' => '',
        'supports_cod' => 0,
        'default_return_fee_amount' => '140.00',
        'sort_order' => 5,
        'is_active' => 1,
    ])->assertRedirect(route('admin.sales.carriers.edit', $carrier));

    expect($carrier->fresh()->name)->toBe('Steadfast BD')
        ->and($carrier->fresh()->integration_driver)->toBe('steadfast')
        ->and($carrier->fresh()->tracking_sync_enabled)->toBeTrue()
        ->and($carrier->fresh()->api_base_url)->toBe('https://custom-api.steadfast.test')
        ->and($carrier->fresh()->api_username)->toBe('custom-user')
        ->and($carrier->fresh()->api_password)->toBe('top-secret-password')
        ->and($carrier->fresh()->api_key)->toBe('steadfast-key')
        ->and($carrier->fresh()->api_secret)->toBe('steadfast-secret')
        ->and($carrier->fresh()->webhook_secret)->toBe('steadfast-webhook')
        ->and($carrier->fresh()->supports_cod)->toBeFalse()
        ->and($carrier->fresh()->default_payout_method)->toBeNull()
        ->and((string) $carrier->fresh()->default_cod_fee_amount)->toBe('0.00');
});

it('creates a pathao courier service with pathao account fields in advanced pro mode', function () {
    setShipmentCarrierShippingMode('advanced_pro');

    $this->loginAsAdmin();

    post(route('admin.sales.carriers.store'), [
        'name' => 'Pathao',
        'code' => 'pathao_admin_test',
        'courier_service' => 'pathao',
        'contact_name' => 'Dhaka Hub',
        'contact_phone' => '01711111111',
        'address' => 'Dhaka pickup point',
        'api_base_url' => 'https://merchant.pathao.test/api/v1',
        'api_store_id' => 77,
        'api_username' => 'pathao-merchant-user',
        'api_password' => 'pathao-password',
        'api_key' => 'pathao-client-id',
        'api_secret' => 'pathao-client-secret',
        'tracking_sync_enabled' => 1,
        'supports_cod' => 1,
        'default_payout_method' => 'bank_transfer',
        'is_active' => 1,
    ])->assertRedirect(route('admin.sales.carriers.create'));

    $carrier = ShipmentCarrier::query()->where('code', 'pathao_admin_test')->firstOrFail();

    expect($carrier->integration_driver)->toBe('pathao')
        ->and($carrier->contact_phone)->toBe('01711111111')
        ->and($carrier->address)->toBe('Dhaka pickup point')
        ->and($carrier->api_store_id)->toBe(77)
        ->and($carrier->api_username)->toBe('pathao-merchant-user')
        ->and($carrier->tracking_sync_enabled)->toBeTrue();
});

it('preserves advanced connection data when editing a courier in manual basic mode', function () {
    setShipmentCarrierShippingMode('manual_basic');

    $carrier = ShipmentCarrier::query()->create([
        'code' => 'pathao_partner',
        'name' => 'Pathao Partner',
        'integration_driver' => 'pathao',
        'tracking_sync_enabled' => true,
        'api_base_url' => 'https://merchant.pathao.test/api/v1',
        'api_store_id' => 25,
        'api_username' => 'stored-user',
        'api_password' => 'stored-password',
        'api_key' => 'stored-key',
        'api_secret' => 'stored-secret',
        'webhook_secret' => 'stored-webhook',
        'supports_cod' => true,
        'is_active' => true,
    ]);

    $this->loginAsAdmin();

    put(route('admin.sales.carriers.update', $carrier), [
        'name' => 'Pathao Partner Updated',
        'code' => '',
        'contact_name' => 'Manual Team',
        'contact_phone' => '01888888888',
        'address' => 'Updated branch office',
        'tracking_url_template' => 'https://track.example.com/{tracking_number}',
        'supports_cod' => 1,
        'is_active' => 1,
    ])->assertRedirect(route('admin.sales.carriers.edit', $carrier));

    $carrier->refresh();

    expect($carrier->name)->toBe('Pathao Partner Updated')
        ->and($carrier->code)->toBe('pathao_partner')
        ->and($carrier->contact_name)->toBe('Manual Team')
        ->and($carrier->contact_phone)->toBe('01888888888')
        ->and($carrier->address)->toBe('Updated branch office')
        ->and($carrier->integration_driver)->toBe('pathao')
        ->and($carrier->tracking_sync_enabled)->toBeTrue()
        ->and($carrier->api_base_url)->toBe('https://merchant.pathao.test/api/v1')
        ->and($carrier->api_store_id)->toBe(25)
        ->and($carrier->api_username)->toBe('stored-user')
        ->and($carrier->api_password)->toBe('stored-password')
        ->and($carrier->api_key)->toBe('stored-key')
        ->and($carrier->api_secret)->toBe('stored-secret')
        ->and($carrier->webhook_secret)->toBe('stored-webhook');
});

it('keeps a legacy driver when editing it as manual in advanced pro mode', function () {
    setShipmentCarrierShippingMode('advanced_pro');

    $carrier = ShipmentCarrier::query()->create([
        'code' => 'legacy_partner',
        'name' => 'Legacy Partner',
        'integration_driver' => 'custom_api',
        'tracking_sync_enabled' => true,
        'supports_cod' => true,
        'default_payout_method' => 'bkash',
        'is_active' => true,
    ]);

    $this->loginAsAdmin();

    put(route('admin.sales.carriers.update', $carrier), [
        'courier_service' => 'manual_other',
        'name' => 'Legacy Partner Updated',
        'tracking_url_template' => 'https://legacy.example/track/{tracking_number}',
        'supports_cod' => 1,
        'default_payout_method' => 'cash',
        'is_active' => 1,
    ])->assertRedirect(route('admin.sales.carriers.edit', $carrier));

    expect($carrier->fresh()->integration_driver)->toBe('custom_api')
        ->and($carrier->fresh()->code)->toBe('legacy_partner')
        ->and($carrier->fresh()->name)->toBe('Legacy Partner Updated');
});

it('uses business-friendly validation for missing pathao connection details', function () {
    setShipmentCarrierShippingMode('advanced_pro');

    $this->loginAsAdmin();

    post(route('admin.sales.carriers.store'), [
        'courier_service' => 'pathao',
        'name' => 'Pathao Validation',
    ])->assertSessionHasErrors([
        'contact_phone' => 'Enter the phone number linked to your Pathao merchant account.',
        'api_store_id' => 'Enter the Pathao store ID from your merchant account.',
        'api_username' => 'Enter the Pathao username from your merchant account.',
        'api_password' => 'Enter the Pathao password from your merchant account.',
        'api_key' => 'Enter the API key provided for this courier connection.',
        'api_secret' => 'Enter the API secret provided for this courier connection.',
    ]);
});

it('deletes a shipment carrier from admin', function () {
    $carrier = ShipmentCarrier::query()->create([
        'code' => 'paperfly',
        'name' => 'Paperfly',
        'is_active' => true,
    ]);

    $this->loginAsAdmin();

    delete(route('admin.sales.carriers.destroy', $carrier))
        ->assertRedirect(route('admin.sales.carriers.index'));

    expect(ShipmentCarrier::query()->find($carrier->id))->toBeNull();
});

function setShipmentCarrierShippingMode(string $mode): void
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
