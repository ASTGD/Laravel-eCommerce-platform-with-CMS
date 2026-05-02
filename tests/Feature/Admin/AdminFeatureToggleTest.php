<?php

use Platform\CommerceCore\Support\AdminFeatureToggle;
use Webkul\Admin\Tests\AdminTestCase;
use Webkul\Core\Facades\SystemConfig;
use Webkul\Core\Menu;
use Webkul\Core\Menu\MenuItem;
use Webkul\Core\Models\CoreConfig;
use Webkul\Customer\Models\Customer;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(AdminTestCase::class);

it('hides optional admin modules by default', function () {
    $this->loginAsAdmin();

    $items = app(Menu::class)->getItems(Menu::ADMIN);

    expect(adminMenuContainsKeyForOptionalFeatureTest($items, 'sales.bookings'))->toBeFalse()
        ->and(adminMenuContainsKeyForOptionalFeatureTest($items, 'sales.rma'))->toBeFalse()
        ->and(adminMenuContainsKeyForOptionalFeatureTest($items, 'marketing'))->toBeFalse()
        ->and(adminMenuContainsKeyForOptionalFeatureTest($items, 'customers.reviews'))->toBeFalse()
        ->and(adminMenuContainsKeyForOptionalFeatureTest($items, 'customers.gdpr_requests'))->toBeFalse();
});

it('hides optional customer account menu items by default', function () {
    $items = app(Menu::class)->getItems(Menu::CUSTOMER);

    expect(adminMenuContainsKeyForOptionalFeatureTest($items, 'account.reviews'))->toBeFalse()
        ->and(adminMenuContainsKeyForOptionalFeatureTest($items, 'account.gdpr_data_request'))->toBeFalse();
});

it('blocks disabled optional admin module routes', function () {
    $this->loginAsAdmin();

    get(route('admin.sales.bookings.index'))->assertNotFound();
    get(route('admin.sales.rma.requests.index'))->assertNotFound();
    get(route('admin.marketing.promotions.catalog_rules.index'))->assertNotFound();
    get(route('admin.customers.customers.review.index'))->assertNotFound();
    get(route('admin.customers.gdpr.index'))->assertNotFound();
});

it('blocks disabled optional customer account routes', function () {
    actingAs(Customer::factory()->create(), 'customer');

    get(route('shop.customers.account.reviews.index'))->assertNotFound();
    get(route('shop.customers.account.gdpr.index'))->assertNotFound();
});

it('shows optional module toggles in one clear configuration screen', function () {
    $this->loginAsAdmin();

    get(route('admin.configuration.index', ['general', 'admin_modules']))
        ->assertOk()
        ->assertSeeText('Module Visibility')
        ->assertSee('label="Enable Booking"', false)
        ->assertSee('label="Enable Product Return"', false)
        ->assertSee('label="Enable Marketing"', false)
        ->assertSee('label="Enable Customer Reviews"', false)
        ->assertSee('label="Enable GDPR Data Requests"', false);
});

it('shows and allows optional admin modules when enabled', function () {
    enableAdminFeature(AdminFeatureToggle::BOOKING);
    enableAdminFeature(AdminFeatureToggle::PRODUCT_RETURN);
    enableAdminFeature(AdminFeatureToggle::MARKETING);
    enableAdminFeature(AdminFeatureToggle::CUSTOMER_REVIEWS);
    enableAdminFeature(AdminFeatureToggle::GDPR_DATA_REQUESTS);

    $this->loginAsAdmin();

    $items = app(Menu::class)->getItems(Menu::ADMIN);

    expect(adminMenuContainsKeyForOptionalFeatureTest($items, 'sales.bookings'))->toBeTrue()
        ->and(adminMenuContainsKeyForOptionalFeatureTest($items, 'sales.rma'))->toBeTrue()
        ->and(adminMenuContainsKeyForOptionalFeatureTest($items, 'marketing'))->toBeTrue()
        ->and(adminMenuContainsKeyForOptionalFeatureTest($items, 'customers.reviews'))->toBeTrue()
        ->and(adminMenuContainsKeyForOptionalFeatureTest($items, 'customers.gdpr_requests'))->toBeTrue()
        ->and(adminMenuNameForOptionalFeatureTest($items, 'sales.rma'))->toBe('Product Return');

    get(route('admin.sales.bookings.index'))->assertOk();
    get(route('admin.sales.rma.requests.index'))->assertOk();
    get(route('admin.marketing.promotions.catalog_rules.index'))->assertOk();
    get(route('admin.customers.customers.review.index'))->assertOk();
    get(route('admin.customers.gdpr.index'))->assertOk();
});

it('shows optional customer account menu items when enabled', function () {
    enableAdminFeature(AdminFeatureToggle::CUSTOMER_REVIEWS);
    enableAdminFeature(AdminFeatureToggle::GDPR_DATA_REQUESTS);

    $items = app(Menu::class)->getItems(Menu::CUSTOMER);

    expect(adminMenuContainsKeyForOptionalFeatureTest($items, 'account.reviews'))->toBeTrue()
        ->and(adminMenuContainsKeyForOptionalFeatureTest($items, 'account.gdpr_data_request'))->toBeTrue();
});

function enableAdminFeature(string $feature): void
{
    $code = match ($feature) {
        AdminFeatureToggle::BOOKING => 'general.admin_modules.visibility.booking_enabled',
        AdminFeatureToggle::PRODUCT_RETURN => 'general.admin_modules.visibility.product_return_enabled',
        AdminFeatureToggle::MARKETING => 'general.admin_modules.visibility.marketing_enabled',
        AdminFeatureToggle::CUSTOMER_REVIEWS => 'general.admin_modules.visibility.customer_reviews_enabled',
        AdminFeatureToggle::GDPR_DATA_REQUESTS => 'general.admin_modules.visibility.gdpr_data_requests_enabled',
    };

    CoreConfig::query()->updateOrCreate(
        [
            'code' => $code,
            'channel_code' => null,
            'locale_code' => null,
        ],
        ['value' => '1'],
    );

    if ($feature === AdminFeatureToggle::GDPR_DATA_REQUESTS) {
        CoreConfig::query()->updateOrCreate(
            [
                'code' => 'general.gdpr.settings.enabled',
                'channel_code' => core()->getRequestedChannelCode(),
                'locale_code' => core()->getRequestedLocaleCode(),
            ],
            ['value' => '1'],
        );
    }

    SystemConfig::clearResolvedInstance(Webkul\Core\SystemConfig::class);
    app()->forgetInstance(Webkul\Core\SystemConfig::class);
}

function adminMenuContainsKeyForOptionalFeatureTest(iterable $items, string $key): bool
{
    return adminMenuNameForOptionalFeatureTest($items, $key) !== null;
}

function adminMenuNameForOptionalFeatureTest(iterable $items, string $key): ?string
{
    foreach ($items as $item) {
        if (! $item instanceof MenuItem) {
            continue;
        }

        if ($item->getKey() === $key) {
            return $item->getName();
        }

        $name = adminMenuNameForOptionalFeatureTest($item->getChildren(), $key);

        if ($name !== null) {
            return $name;
        }
    }

    return null;
}
