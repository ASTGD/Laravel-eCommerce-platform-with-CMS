<?php

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Webkul\Customer\Models\Customer;
use Webkul\Sales\Models\DownloadableLinkPurchased;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;
use Webkul\Shop\CacheProfiles\SafeStorefrontCacheProfile;
use Webkul\User\Models\Admin;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(TestCase::class);

it('redirects the admin dashboard to login after logout', function () {
    $password = 'admin-password';
    $admin = Admin::factory()->create([
        'password' => Hash::make($password),
    ]);

    loginAdminViaForm($admin, $password);

    $dashboardResponse = get(route('admin.dashboard.index'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.dashboard.index.title'));

    assertNoStoreHeader($dashboardResponse);

    $logoutResponse = delete(route('admin.session.destroy'))
        ->assertRedirectToRoute('admin.session.create');

    assertNoStoreHeader($logoutResponse);

    $expiredResponse = get(route('admin.dashboard.index'))
        ->assertRedirectToRoute('admin.session.create');

    assertNoStoreHeader($expiredResponse);
});

it('redirects the admin dashboard to login for an expired or invalid session', function () {
    auth()->guard('admin')->logout();

    $response = get(route('admin.dashboard.index'))
        ->assertRedirectToRoute('admin.session.create');

    assertNoStoreHeader($response);
});

it('redirects customer account pages to login after logout', function () {
    $password = 'customer-password';
    $customer = Customer::factory()->create([
        'password' => Hash::make($password),
    ]);

    loginCustomerViaForm($customer, $password);

    $accountResponse = get(route('shop.customers.account.index'))
        ->assertOk();

    assertNoStoreHeader($accountResponse);

    $logoutResponse = delete(route('shop.customer.session.destroy'))
        ->assertRedirectToRoute('shop.home.index');

    assertNoStoreHeader($logoutResponse);

    $expiredResponse = get(route('shop.customers.account.index'))
        ->assertRedirectToRoute('shop.customer.session.index');

    assertNoStoreHeader($expiredResponse);
});

it('excludes protected and authenticated requests from response cache', function () {
    config(['responsecache.enabled' => true]);

    $profile = app(SafeStorefrontCacheProfile::class);

    expect($profile->enabled(Request::create('/admin', 'GET')))->toBeFalse()
        ->and($profile->enabled(Request::create('/customer/account', 'GET')))->toBeFalse()
        ->and($profile->enabled(Request::create('/checkout/cart', 'GET')))->toBeFalse()
        ->and($profile->enabled(Request::create('/payment/sslcommerz/sslcommerz/success', 'GET')))->toBeFalse();

    actingAs(Customer::factory()->create(), 'customer');

    expect($profile->enabled(Request::create('/public-catalog-page', 'GET')))->toBeFalse();
});

it('does not cache no-store responses', function () {
    config(['responsecache.enabled' => true]);

    $response = new Response('protected', 200, [
        'Cache-Control' => 'no-store, no-cache, private, must-revalidate, max-age=0',
    ]);

    expect(app(SafeStorefrontCacheProfile::class)->shouldCacheResponse($response))->toBeFalse();
});

it('does not expose another customers downloadable product file', function () {
    $owner = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $owner->id,
        'customer_email' => $owner->email,
        'customer_first_name' => $owner->first_name,
        'customer_last_name' => $owner->last_name,
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
    ]);

    $download = DownloadableLinkPurchased::query()->create([
        'product_name' => 'Private Download',
        'name' => 'Private File',
        'type' => 'file',
        'file' => 'private-downloads/customer-file.pdf',
        'download_bought' => 1,
        'download_used' => 0,
        'download_canceled' => 0,
        'status' => 'available',
        'customer_id' => $owner->id,
        'order_id' => $order->id,
        'order_item_id' => $orderItem->id,
    ]);

    actingAs($otherCustomer, 'customer');

    $response = get(route('shop.customers.account.downloadable_products.download', $download->id))
        ->assertNotFound();

    assertNoStoreHeader($response);
});

it('throttles repeated failed admin login attempts', function () {
    $email = 'missing-admin-'.uniqid().'@example.com';

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        post(route('admin.session.store'), [
            'email' => $email,
            'password' => 'wrong-password',
        ])->assertRedirect();
    }

    post(route('admin.session.store'), [
        'email' => $email,
        'password' => 'wrong-password',
    ])
        ->assertTooManyRequests()
        ->assertSee('astgd-ecommerce-logo.webp')
        ->assertSee('Too many login attempts');
});

it('throttles repeated failed customer login attempts while preserving successful login', function () {
    $password = 'correct-password';
    $customer = Customer::factory()->create([
        'email' => 'throttled-customer-'.uniqid().'@example.com',
        'password' => Hash::make($password),
    ]);

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        post(route('shop.customer.session.create'), [
            'email' => 'missing-'.$customer->email,
            'password' => 'wrong-password',
        ])->assertRedirect();
    }

    post(route('shop.customer.session.create'), [
        'email' => 'missing-'.$customer->email,
        'password' => 'wrong-password',
    ])->assertTooManyRequests();

    post(route('shop.customer.session.create'), [
        'email' => $customer->email,
        'password' => $password,
    ])->assertRedirectToRoute('shop.customers.account.index');
});

function assertNoStoreHeader($response): void
{
    expect(strtolower((string) $response->headers->get('Cache-Control')))->toContain('no-store');
}

function loginAdminViaForm(Admin $admin, string $password): void
{
    post(route('admin.session.store'), [
        'email' => $admin->email,
        'password' => $password,
    ])->assertRedirect();
}

function loginCustomerViaForm(Customer $customer, string $password): void
{
    post(route('shop.customer.session.create'), [
        'email' => $customer->email,
        'password' => $password,
    ])->assertRedirect();
}
