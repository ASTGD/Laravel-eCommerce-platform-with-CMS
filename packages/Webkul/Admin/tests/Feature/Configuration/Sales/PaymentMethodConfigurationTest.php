<?php

use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;

use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

it('renders payment method configuration as default and custom tabs', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.configuration.index', ['sales', 'payment_methods']))
        ->assertOk()
        ->assertSeeText('These are Default Payment Methods')
        ->assertSeeText('These are Custom Payment Methods')
        ->assertDontSeeText('bKash Gateway')
        ->assertDontSeeText('SSLCommerz Gateway')
        ->assertSeeText('Configure bKash payment and gateway settings.')
        ->assertSeeText('Configure SSLCommerz payment and gateway settings.')
        ->assertDontSee('<v-tabs', false)
        ->assertDontSee('sales[payment_methods][mode][channel]', false)
        ->assertSee('data-payment-method-tabs', false)
        ->assertSee('data-payment-method-tab="default"', false)
        ->assertSee('data-payment-method-tab="custom"', false)
        ->assertSee('data-tab-panel="default"', false)
        ->assertSee('data-tab-panel="custom"', false);
});

it('saves custom payment method configuration without crashing on gateway groups', function () {
    $channel = Channel::query()->with('default_locale')->firstOrFail();

    $locale = $channel->default_locale ?: Locale::query()->firstOrFail();

    $this->loginAsAdmin();

    postJson(route('admin.configuration.store', ['sales', 'payment_methods']), [
        'channel' => $channel->code,
        'locale' => $locale->code,
        'sales' => [
            'payment_methods' => [
                'sslcommerz_gateway' => [
                    'sandbox' => '1',
                    'store_id' => 'test_store',
                    'store_password' => 'test_password',
                    'request_timeout' => '30',
                    'strict_amount_validation' => '1',
                    'log_payloads' => '1',
                ],
                'sslcommerz' => [
                    'active' => '1',
                    'title' => 'SSLCommerz',
                    'description' => 'Pay online with SSLCommerz',
                    'sort' => '1',
                ],
            ],
        ],
    ])
        ->assertRedirect()
        ->assertSessionHas('success', trans('admin::app.configuration.index.save-message'));

    $this->assertDatabaseHas('core_config', [
        'code' => 'sales.payment_methods.sslcommerz_gateway.store_id',
        'channel_code' => $channel->code,
        'value' => 'test_store',
    ]);

    $this->assertDatabaseHas('core_config', [
        'code' => 'sales.payment_methods.sslcommerz.active',
        'channel_code' => $channel->code,
        'value' => '1',
    ]);
});

it('allows saving custom payment settings when a default payment method is already enabled', function () {
    $channel = Channel::query()->with('default_locale')->firstOrFail();

    $locale = $channel->default_locale ?: Locale::query()->firstOrFail();

    \Webkul\Core\Models\CoreConfig::query()->updateOrCreate(
        [
            'code' => 'sales.payment_methods.moneytransfer.active',
            'channel_code' => $channel->code,
        ],
        [
            'value' => '1',
        ],
    );

    $this->loginAsAdmin();

    postJson(route('admin.configuration.store', ['sales', 'payment_methods']), [
        'channel' => $channel->code,
        'locale' => $locale->code,
        'sales' => [
            'payment_methods' => [
                'sslcommerz_gateway' => [
                    'sandbox' => '1',
                    'store_id' => 'saved-with-default-still-enabled',
                ],
            ],
        ],
    ])
        ->assertRedirect()
        ->assertSessionHas('success', trans('admin::app.configuration.index.save-message'));

    $this->assertDatabaseHas('core_config', [
        'code' => 'sales.payment_methods.sslcommerz_gateway.store_id',
        'channel_code' => $channel->code,
        'value' => 'saved-with-default-still-enabled',
    ]);
});

it('saves default and custom payment changes together from the same submission', function () {
    $channel = Channel::query()->with('default_locale')->firstOrFail();

    $locale = $channel->default_locale ?: Locale::query()->firstOrFail();

    $this->loginAsAdmin();

    postJson(route('admin.configuration.store', ['sales', 'payment_methods']), [
        'channel' => $channel->code,
        'locale' => $locale->code,
        'sales' => [
            'payment_methods' => [
                'moneytransfer' => [
                    'active' => '0',
                    'title' => 'Money Transfer',
                    'description' => 'Money Transfer',
                    'sort' => '7',
                ],
                'sslcommerz_gateway' => [
                    'sandbox' => '1',
                    'store_id' => 'mixed-save-store',
                    'store_password' => 'test_password',
                    'request_timeout' => '30',
                    'strict_amount_validation' => '1',
                    'log_payloads' => '1',
                ],
                'sslcommerz' => [
                    'active' => '1',
                    'title' => 'SSLCommerz',
                    'description' => 'Pay online with SSLCommerz',
                    'sort' => '1',
                ],
                'bkash_gateway' => [
                    'sandbox' => '1',
                    'sandbox_base_url' => 'https://tokenized.sandbox.bka.sh/v1.2.0-beta',
                    'username' => 'sandbox-user',
                    'password' => 'sandbox-password',
                    'app_key' => 'sandbox-app-key',
                    'app_secret' => 'sandbox-app-secret',
                    'request_timeout' => '30',
                    'strict_amount_validation' => '1',
                    'log_payloads' => '1',
                ],
                'bkash' => [
                    'active' => '1',
                    'title' => 'bKash',
                    'description' => 'Pay directly with bKash',
                    'sort' => '2',
                ],
            ],
        ],
    ])
        ->assertRedirect()
        ->assertSessionHas('success', trans('admin::app.configuration.index.save-message'));

    $this->assertDatabaseHas('core_config', [
        'code' => 'sales.payment_methods.moneytransfer.active',
        'channel_code' => $channel->code,
        'value' => '0',
    ]);

    $this->assertDatabaseHas('core_config', [
        'code' => 'sales.payment_methods.sslcommerz.active',
        'channel_code' => $channel->code,
        'value' => '1',
    ]);

    $this->assertDatabaseHas('core_config', [
        'code' => 'sales.payment_methods.bkash.active',
        'channel_code' => $channel->code,
        'value' => '1',
    ]);
});
