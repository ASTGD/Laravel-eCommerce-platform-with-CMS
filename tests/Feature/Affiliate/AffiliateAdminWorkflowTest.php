<?php

use Platform\CommerceCore\Models\AffiliateCommission;
use Platform\CommerceCore\Models\AffiliatePayout;
use Platform\CommerceCore\Models\AffiliateProfile;
use Platform\CommerceCore\Services\Affiliates\AffiliatePayoutService;
use Platform\CommerceCore\Services\Affiliates\AffiliateProfileService;
use Platform\CommerceCore\Services\Affiliates\ReferralAttributionService;
use Webkul\Admin\Tests\AdminTestCase;
use Webkul\Customer\Models\Customer;
use Webkul\Sales\Models\Order;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(AdminTestCase::class);

it('shows the affiliate admin area with status buckets and without excluded MVP features', function () {
    $this->loginAsAdmin();

    $customer = Customer::factory()->create([
        'first_name' => 'Pending',
        'last_name' => 'Affiliate',
    ]);

    app(AffiliateProfileService::class)->apply($customer, [
        'application_note' => 'I want to promote the store.',
        'terms_accepted' => true,
    ]);

    get(route('admin.affiliates.profiles.index'))
        ->assertOk()
        ->assertSeeText('Affiliates')
        ->assertSeeText('My Affiliates')
        ->assertSeeText('Add Affiliate')
        ->assertSeeText('Payouts')
        ->assertSeeText('Pending Affiliate')
        ->assertSeeText('Pending')
        ->assertDontSeeText('Email Affiliate')
        ->assertDontSeeText('Banner')
        ->assertDontSeeText('Text Ad');
});

it('lets admin create an active affiliate profile for an existing customer account', function () {
    $this->loginAsAdmin();

    $customer = Customer::factory()->create([
        'first_name' => 'Admin',
        'last_name' => 'Created',
        'email' => 'admin-created-affiliate@example.test',
    ]);

    get(route('admin.affiliates.profiles.create'))
        ->assertOk()
        ->assertSeeText('Add Affiliate')
        ->assertSeeText('Admin Created')
        ->assertSeeText('Create Affiliate');

    post(route('admin.affiliates.profiles.store'), [
        'customer_id' => $customer->id,
        'status' => AffiliateProfile::STATUS_ACTIVE,
        'referral_code' => 'vip partner 01',
        'application_note' => 'Created directly from admin.',
        'website_url' => 'https://partner.example.test',
        'social_profiles_text' => 'https://social.example.test/partner',
        'payout_method' => 'bank_transfer',
        'payout_reference' => 'Bank account 123',
    ])->assertSessionHasNoErrors();

    $profile = AffiliateProfile::query()->where('customer_id', $customer->id)->first();

    expect($profile)->not->toBeNull()
        ->and($profile->status)->toBe(AffiliateProfile::STATUS_ACTIVE)
        ->and($profile->referral_code)->toBe('VIPPARTNER01')
        ->and($profile->application_source)->toBe('admin_created')
        ->and($profile->application_note)->toBe('Created directly from admin.')
        ->and($profile->social_profiles)->toBe(['text' => 'https://social.example.test/partner'])
        ->and($profile->payout_method)->toBe('bank_transfer')
        ->and($profile->payout_reference)->toBe('Bank account 123')
        ->and($profile->approved_at)->not->toBeNull();
});

it('keeps admin-created pending affiliates in the normal review flow', function () {
    $this->loginAsAdmin();

    $customer = Customer::factory()->create();

    post(route('admin.affiliates.profiles.store'), [
        'customer_id' => $customer->id,
        'status' => AffiliateProfile::STATUS_PENDING,
        'application_note' => 'Needs review.',
    ])->assertSessionHasNoErrors();

    $profile = AffiliateProfile::query()->where('customer_id', $customer->id)->first();

    expect($profile)->not->toBeNull()
        ->and($profile->status)->toBe(AffiliateProfile::STATUS_PENDING)
        ->and($profile->application_source)->toBe('admin_created')
        ->and($profile->approved_at)->toBeNull()
        ->and($profile->referral_code)->not->toBeEmpty();

    get(route('admin.affiliates.profiles.index', ['status' => AffiliateProfile::STATUS_PENDING]))
        ->assertOk()
        ->assertSeeText($profile->referral_code);
});

it('lets admin approve, suspend, reactivate, and reject affiliate profiles from the shared profile record', function () {
    $this->loginAsAdmin();

    $customer = Customer::factory()->create();
    $profile = app(AffiliateProfileService::class)->apply($customer, [
        'application_note' => 'Please review my affiliate application.',
        'terms_accepted' => true,
    ]);

    post(route('admin.affiliates.profiles.approve', $profile))
        ->assertRedirect(route('admin.affiliates.profiles.show', $profile));

    expect($profile->refresh()->status)->toBe(AffiliateProfile::STATUS_ACTIVE);

    post(route('admin.affiliates.profiles.suspend', $profile), [
        'reason' => 'Policy review.',
    ])->assertRedirect(route('admin.affiliates.profiles.show', $profile));

    expect($profile->refresh()->status)->toBe(AffiliateProfile::STATUS_SUSPENDED)
        ->and($profile->suspension_reason)->toBe('Policy review.');

    post(route('admin.affiliates.profiles.reactivate', $profile))
        ->assertRedirect(route('admin.affiliates.profiles.show', $profile));

    expect($profile->refresh()->status)->toBe(AffiliateProfile::STATUS_ACTIVE);

    post(route('admin.affiliates.profiles.reject', $profile), [
        'reason' => 'Rejected after manual review.',
    ])->assertRedirect(route('admin.affiliates.profiles.show', $profile));

    expect($profile->refresh()->status)->toBe(AffiliateProfile::STATUS_REJECTED)
        ->and($profile->rejection_reason)->toBe('Rejected after manual review.');
});

it('shows profile summaries and lets admin add a paid payout record from the profile page', function () {
    $this->loginAsAdmin();

    $customer = Customer::factory()->create([
        'first_name' => 'Active',
        'last_name' => 'Partner',
    ]);

    $profile = app(AffiliateProfileService::class)->approve(
        app(AffiliateProfileService::class)->apply($customer, [
            'application_note' => 'Ready to promote.',
            'terms_accepted' => true,
        ]),
    );

    createApprovedAffiliateCommission($profile, 100);

    get(route('admin.affiliates.profiles.show', $profile))
        ->assertOk()
        ->assertSeeText('Active Partner')
        ->assertSeeText('Referral Code')
        ->assertSeeText('Available Balance')
        ->assertSeeText('Add Payout Record');

    post(route('admin.affiliates.profiles.payouts.store', $profile), [
        'amount' => 60,
        'currency' => 'USD',
        'payout_method' => 'bank_transfer',
        'payout_reference' => 'BANK-PAID-1001',
        'admin_notes' => 'Manual bank payout.',
    ])->assertRedirect(route('admin.affiliates.profiles.show', $profile));

    $payout = AffiliatePayout::query()->where('affiliate_profile_id', $profile->id)->first();

    expect($payout)->not->toBeNull()
        ->and($payout->status)->toBe(AffiliatePayout::STATUS_PAID)
        ->and((float) $payout->amount)->toBe(60.0)
        ->and($payout->payout_reference)->toBe('BANK-PAID-1001');
});

it('lets admin regenerate a referral code and invalidates the old code for new attribution', function () {
    $this->loginAsAdmin();

    $customer = Customer::factory()->create();
    $profile = app(AffiliateProfileService::class)->approve(
        app(AffiliateProfileService::class)->apply($customer, [
            'application_note' => 'Ready to promote.',
            'terms_accepted' => true,
        ]),
    );
    $oldCode = $profile->referral_code;

    get(route('admin.affiliates.profiles.show', $profile))
        ->assertOk()
        ->assertSeeText('Regenerate Referral Code')
        ->assertSee($profile->referral_url);

    post(route('admin.affiliates.profiles.regenerate-referral-code', $profile))
        ->assertRedirect(route('admin.affiliates.profiles.show', $profile));

    $profile->refresh();

    expect($profile->referral_code)->not->toBe($oldCode)
        ->and(data_get($profile->meta, 'previous_referral_codes'))->toContain($oldCode)
        ->and(app(ReferralAttributionService::class)->findActiveProfileByCode($oldCode))->toBeNull()
        ->and(app(ReferralAttributionService::class)->findActiveProfileByCode($profile->referral_code)?->is($profile))->toBeTrue();
});

it('shows payout status buckets and lets admin approve and complete withdrawal requests', function () {
    $this->loginAsAdmin();

    $customer = Customer::factory()->create();
    $profile = app(AffiliateProfileService::class)->approve(
        app(AffiliateProfileService::class)->apply($customer, [
            'application_note' => 'Payout requester.',
            'terms_accepted' => true,
        ]),
    );

    createApprovedAffiliateCommission($profile, 120);

    $payout = app(AffiliatePayoutService::class)->requestPayout($profile, 80, [
        'currency' => 'USD',
        'payout_method' => 'bank_transfer',
    ]);

    get(route('admin.affiliates.payouts.index', ['status' => AffiliatePayout::STATUS_REQUESTED]))
        ->assertOk()
        ->assertSeeText('Affiliate Payouts')
        ->assertSeeText('Requested')
        ->assertSeeText('Approve')
        ->assertSeeText('Mark Paid');

    post(route('admin.affiliates.payouts.approve', $payout))
        ->assertRedirect(route('admin.affiliates.payouts.index', ['status' => AffiliatePayout::STATUS_APPROVED]));

    expect($payout->refresh()->status)->toBe(AffiliatePayout::STATUS_APPROVED);

    post(route('admin.affiliates.payouts.mark-paid', $payout), [
        'payout_reference' => 'BANK-PAID-2002',
    ])->assertRedirect(route('admin.affiliates.payouts.index', ['status' => AffiliatePayout::STATUS_PAID]));

    expect($payout->refresh()->status)->toBe(AffiliatePayout::STATUS_PAID)
        ->and($payout->payout_reference)->toBe('BANK-PAID-2002');
});

function createApprovedAffiliateCommission(AffiliateProfile $profile, float $amount): AffiliateCommission
{
    $order = Order::factory()->create([
        'customer_id' => Customer::factory()->create()->id,
        'base_sub_total' => $amount * 10,
        'base_grand_total' => $amount * 10,
        'base_currency_code' => 'USD',
        'order_currency_code' => 'USD',
    ]);

    return AffiliateCommission::query()->create([
        'affiliate_profile_id' => $profile->id,
        'order_id' => $order->id,
        'status' => AffiliateCommission::STATUS_APPROVED,
        'commission_type' => 'fixed',
        'commission_rate' => $amount,
        'order_amount' => $amount * 10,
        'commission_amount' => $amount,
        'currency' => 'USD',
        'eligible_at' => now(),
        'approved_at' => now(),
    ]);
}
