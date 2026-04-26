<?php

use Platform\CommerceCore\Models\AffiliateClick;
use Platform\CommerceCore\Models\AffiliateCommission;
use Platform\CommerceCore\Models\AffiliateOrderAttribution;
use Platform\CommerceCore\Models\AffiliatePayout;
use Platform\CommerceCore\Models\AffiliateProfile;
use Platform\CommerceCore\Services\Affiliates\AffiliateProfileService;
use Tests\TestCase;
use Webkul\Customer\Models\Customer;
use Webkul\Sales\Models\Order;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(TestCase::class);

it('shows a public affiliate program page with guest login and register calls to action', function () {
    get(route('shop.affiliate-program.index'))
        ->assertOk()
        ->assertSeeText('Affiliate Program')
        ->assertSeeText('Login to Apply')
        ->assertSeeText('Register')
        ->assertSee(route('shop.customer.session.index', ['redirect_to' => 'account']))
        ->assertSee(route('shop.customers.register.index'));
});

it('routes logged in customers from the public affiliate page into the account application flow', function () {
    $customer = Customer::factory()->create();

    actingAs($customer, 'customer');

    get(route('shop.affiliate-program.index'))
        ->assertOk()
        ->assertSeeText('Go to Affiliate Application')
        ->assertSee(route('shop.customers.account.affiliate.index'));
});

it('shows the affiliate application form to a logged in customer without a profile', function () {
    $customer = Customer::factory()->create();

    actingAs($customer, 'customer');

    get(route('shop.customers.account.affiliate.index'))
        ->assertOk()
        ->assertSeeText('Affiliate Application')
        ->assertSeeText('Submit Application');
});

it('creates a pending affiliate profile from the customer portal application', function () {
    config()->set('commerce_affiliate.approval_required', true);

    $customer = Customer::factory()->create();

    actingAs($customer, 'customer');

    post(route('shop.customers.account.affiliate.apply'), [
        'application_note' => 'I will promote products through my review website.',
        'website_url' => 'https://affiliate.example.test',
        'social_profiles_text' => 'https://facebook.example/test-page',
        'payout_method' => 'bank_transfer',
        'payout_reference' => 'Account 123456',
        'terms_accepted' => '1',
    ])->assertRedirectToRoute('shop.customers.account.affiliate.index')
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    $profile = AffiliateProfile::query()->where('customer_id', $customer->id)->first();

    expect($profile)->not->toBeNull()
        ->and($profile->status)->toBe(AffiliateProfile::STATUS_PENDING)
        ->and($profile->application_source)->toBe('customer_portal')
        ->and($profile->application_note)->toBe('I will promote products through my review website.')
        ->and($profile->website_url)->toBe('https://affiliate.example.test')
        ->and($profile->social_profiles)->toBe(['text' => 'https://facebook.example/test-page'])
        ->and($profile->payout_method)->toBe('bank_transfer')
        ->and($profile->payout_reference)->toBe('Account 123456')
        ->and($profile->terms_accepted_at)->not->toBeNull();

    get(route('shop.customers.account.affiliate.index'))
        ->assertOk()
        ->assertSeeText('Application under review')
        ->assertDontSeeText('Submit Application');
});

it('allows a rejected customer to resubmit and returns the profile to pending', function () {
    $customer = Customer::factory()->create();
    $profileService = app(AffiliateProfileService::class);
    $profile = $profileService->apply($customer, [
        'application_note' => 'Initial application',
        'terms_accepted' => true,
    ]);

    $profileService->reject($profile, reason: 'Please provide more audience details.');

    actingAs($customer, 'customer');

    get(route('shop.customers.account.affiliate.index'))
        ->assertOk()
        ->assertSeeText('Application was not approved')
        ->assertSeeText('Resubmit Application');

    post(route('shop.customers.account.affiliate.apply'), [
        'application_note' => 'Updated audience details for review.',
        'terms_accepted' => '1',
    ])->assertRedirectToRoute('shop.customers.account.affiliate.index')
        ->assertSessionHasNoErrors();

    expect($profile->refresh()->status)->toBe(AffiliateProfile::STATUS_PENDING)
        ->and($profile->application_note)->toBe('Updated audience details for review.');
});

it('does not expose the full dashboard for pending or suspended affiliates', function () {
    $customer = Customer::factory()->create();
    $profileService = app(AffiliateProfileService::class);
    $profile = $profileService->apply($customer, [
        'application_note' => 'Pending application',
        'terms_accepted' => true,
    ]);

    actingAs($customer, 'customer');

    get(route('shop.customers.account.affiliate.index'))
        ->assertOk()
        ->assertSeeText('Application under review')
        ->assertDontSeeText('Affiliate Dashboard')
        ->assertDontSeeText('Request payout');

    $profileService->suspend($profile);

    get(route('shop.customers.account.affiliate.index'))
        ->assertOk()
        ->assertSeeText('Affiliate account suspended')
        ->assertDontSeeText('Affiliate Dashboard')
        ->assertDontSeeText('Affiliate Application');
});

it('shows the approved affiliate dashboard without using a separate affiliate account', function () {
    $customer = Customer::factory()->create();
    $profileService = app(AffiliateProfileService::class);
    $profile = $profileService->approve($profileService->apply($customer, [
        'application_note' => 'Ready to promote',
        'terms_accepted' => true,
    ]));
    createAffiliatePortalCommission($profile, AffiliateCommission::STATUS_APPROVED, 125);
    AffiliateClick::query()->create([
        'affiliate_profile_id' => $profile->id,
        'referral_code' => $profile->referral_code,
        'clicked_at' => now(),
    ]);

    actingAs($customer, 'customer');

    get(route('shop.customers.account.affiliate.index'))
        ->assertOk()
        ->assertSeeText('Affiliate Dashboard')
        ->assertSeeText($profile->referral_code)
        ->assertSee($profile->referral_url)
        ->assertSeeText('Total clicks')
        ->assertSeeText('Attributed orders')
        ->assertSeeText('Request payout')
        ->assertDontSeeText('Submit Application');
});

it('shows copyable referral tools and builds tracked links for active affiliates', function () {
    $customer = Customer::factory()->create();
    $profile = activeAffiliateCustomerPortalProfileFor($customer);

    actingAs($customer, 'customer');

    get(route('shop.customers.account.affiliate.index', ['target_path' => '/products/example']))
        ->assertOk()
        ->assertSeeText('Copy Code')
        ->assertSeeText('Copy Link')
        ->assertSeeText('Simple link builder')
        ->assertSee($profile->referral_code)
        ->assertSee(url('/products/example').'?ref='.$profile->referral_code);
});

it('replaces stale referral parameters when building active affiliate links', function () {
    $customer = Customer::factory()->create();
    $profile = activeAffiliateCustomerPortalProfileFor($customer);

    actingAs($customer, 'customer');

    get(route('shop.customers.account.affiliate.index', ['target_path' => '/products/example?color=red&ref=OLD-CODE']))
        ->assertOk()
        ->assertSee(url('/products/example').'?color=red&amp;ref='.$profile->referral_code, false);
});

it('rejects external referral link builder targets and falls back to the homepage link', function () {
    $customer = Customer::factory()->create();
    $profile = activeAffiliateCustomerPortalProfileFor($customer);

    actingAs($customer, 'customer');

    get(route('shop.customers.account.affiliate.index', ['target_path' => 'https://external.example.test/page']))
        ->assertOk()
        ->assertSeeText('Referral links can only point to this storefront.')
        ->assertSee(url('/').'?ref='.$profile->referral_code);
});

it('lets an active affiliate request a withdrawal from available approved commissions', function () {
    config()->set('commerce_affiliate.minimum_payout_amount', 50);

    $customer = Customer::factory()->create();
    $profile = activeAffiliateCustomerPortalProfileFor($customer);

    createAffiliatePortalCommission($profile, AffiliateCommission::STATUS_APPROVED, 150);

    actingAs($customer, 'customer');

    post(route('shop.customers.account.affiliate.withdrawals.store'), [
        'amount' => 75,
        'payout_method' => 'bank_transfer',
        'payout_reference' => 'Bank account 123',
        'notes' => 'Please pay this week.',
    ])->assertRedirectToRoute('shop.customers.account.affiliate.index')
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    $payout = AffiliatePayout::query()->where('affiliate_profile_id', $profile->id)->first();

    expect($payout)->not->toBeNull()
        ->and($payout->status)->toBe(AffiliatePayout::STATUS_REQUESTED)
        ->and((float) $payout->amount)->toBe(75.0)
        ->and($payout->requested_by_customer_id)->toBe($customer->id)
        ->and($payout->payout_method)->toBe('bank_transfer')
        ->and($payout->payout_reference)->toStartWith('AP-')
        ->and($payout->meta)->toBe(['payout_account_details' => 'Bank account 123'])
        ->and($payout->notes)->toBe('Please pay this week.');
});

it('blocks withdrawal requests when the affiliate profile is not active', function () {
    $customer = Customer::factory()->create();
    app(AffiliateProfileService::class)->apply($customer, [
        'application_note' => 'Pending review.',
        'terms_accepted' => true,
    ]);

    actingAs($customer, 'customer');

    post(route('shop.customers.account.affiliate.withdrawals.store'), [
        'amount' => 75,
    ])->assertRedirectToRoute('shop.customers.account.affiliate.index')
        ->assertSessionHas('warning');

    expect(AffiliatePayout::query()->exists())->toBeFalse();
});

function activeAffiliateCustomerPortalProfileFor(Customer $customer): AffiliateProfile
{
    $profileService = app(AffiliateProfileService::class);

    return $profileService->approve($profileService->apply($customer, [
        'application_note' => 'Ready to promote.',
        'payout_method' => 'bank_transfer',
        'payout_reference' => 'Saved account',
        'terms_accepted' => true,
    ]));
}

function createAffiliatePortalCommission(AffiliateProfile $profile, string $status, float $amount): AffiliateCommission
{
    $buyer = Customer::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $buyer->id,
        'base_grand_total' => 1000,
        'grand_total' => 1000,
        'base_currency_code' => 'USD',
        'order_currency_code' => 'USD',
    ]);
    $attribution = AffiliateOrderAttribution::query()->create([
        'affiliate_profile_id' => $profile->id,
        'order_id' => $order->id,
        'referral_code' => $profile->referral_code,
        'attribution_source' => 'session',
        'status' => AffiliateOrderAttribution::STATUS_ATTRIBUTED,
        'attributed_at' => now(),
    ]);

    return AffiliateCommission::query()->create([
        'affiliate_profile_id' => $profile->id,
        'affiliate_order_attribution_id' => $attribution->id,
        'order_id' => $order->id,
        'status' => $status,
        'commission_type' => 'percentage',
        'commission_rate' => 10,
        'order_amount' => 1000,
        'commission_amount' => $amount,
        'currency' => 'USD',
        'approved_at' => $status === AffiliateCommission::STATUS_APPROVED ? now() : null,
    ]);
}
