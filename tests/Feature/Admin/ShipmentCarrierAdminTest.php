<?php

use Platform\CommerceCore\Models\ShipmentCarrier;
use Webkul\Admin\Tests\AdminTestCase;

use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

uses(AdminTestCase::class);

it('shows the carrier admin index', function () {
    $this->loginAsAdmin();

    get(route('admin.sales.carriers.index'))
        ->assertOk()
        ->assertSeeText('Carriers')
        ->assertSeeText('Add Carrier');
});

it('creates and updates a shipment carrier from admin', function () {
    $this->loginAsAdmin();

    post(route('admin.sales.carriers.store'), [
        'code' => 'steadfast',
        'name' => 'Steadfast Courier',
        'contact_name' => 'Ops Desk',
        'contact_phone' => '01700000000',
        'contact_email' => 'ops@steadfast.test',
        'tracking_url_template' => 'https://carrier.example/track/{tracking_number}',
        'integration_driver' => 'steadfast',
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

    $carrier = ShipmentCarrier::query()->where('code', 'steadfast')->firstOrFail();

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
        'code' => 'steadfast',
        'name' => 'Steadfast BD',
        'contact_name' => 'Ops Lead',
        'contact_phone' => '01700000001',
        'contact_email' => 'bd@steadfast.test',
        'tracking_url_template' => 'https://carrier.example/trace/{tracking_number}',
        'integration_driver' => 'custom_api',
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
        ->and($carrier->fresh()->integration_driver)->toBe('custom_api')
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
