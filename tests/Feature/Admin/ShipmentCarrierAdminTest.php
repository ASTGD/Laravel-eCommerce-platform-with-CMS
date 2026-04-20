<?php

use Platform\CommerceCore\Models\ShipmentCarrier;
use Webkul\Admin\Tests\AdminTestCase;

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

it('shows the courier-first create form', function () {
    $this->loginAsAdmin();

    get(route('admin.sales.carriers.create'))
        ->assertOk()
        ->assertSeeText('Add Courier Service')
        ->assertSeeText('Courier Service')
        ->assertSeeText('Manual / Other')
        ->assertDontSeeText('Integration Driver')
        ->assertDontSeeText('Code');
});

it('creates and updates a steadfast courier service from admin', function () {
    $this->loginAsAdmin();

    post(route('admin.sales.carriers.store'), [
        'name' => 'Steadfast Courier',
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
    ])->assertRedirect();

    $carrier = ShipmentCarrier::query()->where('code', 'steadfast_courier')->firstOrFail();

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

it('creates a pathao courier service with pathao account fields', function () {
    $this->loginAsAdmin();

    post(route('admin.sales.carriers.store'), [
        'name' => 'Pathao',
        'courier_service' => 'pathao',
        'contact_name' => 'Dhaka Hub',
        'contact_phone' => '01711111111',
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
    ])->assertRedirect();

    $carrier = ShipmentCarrier::query()->where('code', 'pathao')->firstOrFail();

    expect($carrier->integration_driver)->toBe('pathao')
        ->and($carrier->contact_phone)->toBe('01711111111')
        ->and($carrier->api_store_id)->toBe(77)
        ->and($carrier->api_username)->toBe('pathao-merchant-user')
        ->and($carrier->tracking_sync_enabled)->toBeTrue();
});

it('keeps a legacy driver when editing it as manual or other', function () {
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

it('uses business-friendly validation for missing pathao account details', function () {
    $this->loginAsAdmin();

    post(route('admin.sales.carriers.store'), [
        'courier_service' => 'pathao',
        'name' => 'Pathao',
    ])->assertSessionHasErrors([
        'contact_phone' => 'Enter the pickup phone number from your Pathao merchant account.',
        'api_store_id' => 'Enter the Pathao store ID from your merchant account.',
        'api_username' => 'Enter the Pathao username from your merchant account.',
        'api_password' => 'Enter the Pathao password from your merchant account.',
        'api_key' => 'Enter the API key provided by the courier.',
        'api_secret' => 'Enter the API secret provided by the courier.',
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
