<?php

use Webkul\Admin\Tests\AdminTestCase;
use Webkul\Core\Models\CoreConfig;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(AdminTestCase::class);

it('shows one unified shipping settings hub from configuration index', function () {
    $this->loginAsAdmin();

    get(route('admin.configuration.index'))
        ->assertOk()
        ->assertSeeText('Shipping')
        ->assertDontSeeText(trans('admin::app.configuration.index.sales.shipping-methods.title'))
        ->assertDontSeeText('Shipment Notifications')
        ->assertDontSeeText('Shipping Workflow');
});

it('shows pickup points, courier services, checkout methods, courier operations, and shipment notifications on the unified shipping page', function () {
    $this->loginAsAdmin();

    get(route('admin.configuration.index', ['slug' => 'sales', 'slug2' => 'shipping']))
        ->assertOk()
        ->assertSeeText('Shipping')
        ->assertSeeText('Origin & Fulfillment')
        ->assertSeeText('Pickup Points')
        ->assertSeeText('Manage Pickup Points')
        ->assertSeeText('Courier Services')
        ->assertSeeText('Manage Courier Services')
        ->assertSeeText('Checkout Shipping Methods')
        ->assertSeeText('Courier Operations')
        ->assertSeeText('Shipment Notifications')
        ->assertSeeText('Free Shipping')
        ->assertSeeText('Flat Rate Shipping')
        ->assertSeeText('Courier')
        ->assertSeeText('Manual Basic')
        ->assertSeeText('Advanced Pro');
});

it('saves shipping workflow and shipment notification settings from the unified shipping page', function () {
    $this->loginAsAdmin();

    post(route('admin.configuration.store', ['slug' => 'sales', 'slug2' => 'shipping']), shippingConfigurationPayload([
        'sales' => [
            'shipment_notifications' => [
                'customer_delivered_email' => 0,
            ],
            'shipping_workflow' => [
                'shipping_mode' => 'advanced_pro',
            ],
        ],
    ]))->assertRedirect();

    $workflowConfig = CoreConfig::query()->where('code', 'sales.shipping_workflow.shipping_mode')->first();
    $notificationConfig = CoreConfig::query()->where('code', 'sales.shipment_notifications.customer_delivered_email')->first();

    expect($workflowConfig)->not->toBeNull()
        ->and($workflowConfig->value)->toBe('advanced_pro')
        ->and($notificationConfig)->not->toBeNull()
        ->and($notificationConfig->value)->toBe('0');
});

function shippingConfigurationPayload(array $override = []): array
{
    $payload = [
        'channel' => 'default',
        'locale' => core()->getDefaultChannel()->default_locale->code,
        'keys' => collect(config('core'))
            ->filter(fn (array $item) => in_array($item['key'], [
                'sales.shipping.origin',
                'sales.shipping.courier_services',
                'sales.carriers.free',
                'sales.carriers.flatrate',
                'sales.carriers.courier',
                'sales.shipment_notifications',
                'sales.shipping_workflow',
            ], true))
            ->map(fn (array $item) => json_encode($item))
            ->values()
            ->all(),
        'sales' => [
            'shipping' => [
                'origin' => [
                    'country' => 'BD',
                    'state' => 'Dhaka',
                    'city' => 'Dhaka',
                    'address' => 'House 10, Road 12',
                    'zipcode' => '1207',
                    'store_name' => 'Default Warehouse',
                    'vat_number' => 'VAT-001',
                    'contact' => '01700000000',
                    'bank_details' => 'Bank details',
                ],
            ],
            'carriers' => [
                'free' => [
                    'active' => 0,
                    'title' => 'Free Shipping',
                    'description' => 'Free Shipping',
                ],
                'flatrate' => [
                    'active' => 0,
                    'title' => 'Flat Rate Shipping',
                    'description' => 'Flat Rate Shipping',
                    'default_rate' => 120,
                    'type' => 'per_order',
                ],
                'courier' => [
                    'active' => 1,
                    'title' => 'Courier',
                    'description' => 'District-based delivery charges',
                    'default_rate' => 120,
                    'district_rates' => "Dhaka=60\nChattogram=120",
                    'dhaka_district' => 'Dhaka',
                    'dhaka_title' => 'Dhaka Delivery',
                    'dhaka_rate' => 60,
                    'outside_dhaka_title' => 'Outside Dhaka Delivery',
                    'outside_dhaka_rate' => 120,
                ],
            ],
            'shipment_notifications' => [
                'customer_out_for_delivery_email' => 1,
                'customer_delivered_email' => 1,
                'customer_delivery_failed_email' => 1,
                'customer_return_initiated_email' => 1,
                'customer_returned_email' => 1,
                'admin_out_for_delivery_email' => 1,
                'admin_delivered_email' => 1,
                'admin_delivery_failed_email' => 1,
                'admin_return_initiated_email' => 1,
                'admin_returned_email' => 1,
            ],
            'shipping_workflow' => [
                'shipping_mode' => 'manual_basic',
            ],
        ],
    ];

    return array_replace_recursive($payload, $override);
}
