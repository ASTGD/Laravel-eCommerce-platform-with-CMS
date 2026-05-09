<?php

use Illuminate\Support\Facades\Route;
use Platform\CommerceCore\Models\AffiliateCommission;
use Platform\CommerceCore\Models\AffiliatePayout;
use Platform\CommerceCore\Models\AffiliateProfile;
use Platform\CommerceCore\Services\Affiliates\AffiliatePayoutService;
use Platform\CommerceCore\Services\Affiliates\AffiliateProfileService;
use Platform\CommerceCore\Services\Affiliates\AffiliateSettingsService;
use Webkul\Admin\Tests\AdminTestCase;
use Webkul\Customer\Models\Customer;
use Webkul\Sales\Models\Order;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(AdminTestCase::class);

it('shows the affiliate admin area with status buckets and without excluded MVP features', function () {
    $this->loginAsAdmin();

    $adminMenu = collect(config('menu.admin'));
    $affiliateMenu = $adminMenu->firstWhere('key', 'affiliates');
    $affiliateOverviewMenu = $adminMenu->firstWhere('key', 'affiliates.reports');
    $affiliateProfilesMenu = $adminMenu->firstWhere('key', 'affiliates.profiles');

    expect($affiliateMenu['route'] ?? null)->toBe('admin.affiliates.overview.index')
        ->and($affiliateOverviewMenu['name'] ?? null)->toBe('Overview')
        ->and($affiliateOverviewMenu['route'] ?? null)->toBe('admin.affiliates.overview.index')
        ->and($affiliateOverviewMenu['permission_key'] ?? null)->toBe('affiliates.reports')
        ->and($affiliateProfilesMenu['name'] ?? null)->toBe('My Affiliate');

    $affiliateProfileService = app(AffiliateProfileService::class);

    $pendingCustomer = Customer::factory()->create([
        'first_name' => 'Pending',
        'last_name' => 'Affiliate',
    ]);

    $affiliateProfileService->apply($pendingCustomer, [
        'application_note' => 'I want to promote the store.',
        'terms_accepted' => true,
    ]);

    $activeCustomer = Customer::factory()->create([
        'first_name' => 'Active',
        'last_name' => 'Affiliate',
    ]);

    $affiliateProfileService->approve($affiliateProfileService->apply($activeCustomer, [
        'application_note' => 'Ready to promote.',
        'terms_accepted' => true,
    ]));

    $suspendedCustomer = Customer::factory()->create([
        'first_name' => 'Suspended',
        'last_name' => 'Affiliate',
    ]);

    $affiliateProfileService->suspend($affiliateProfileService->approve($affiliateProfileService->apply($suspendedCustomer, [
        'application_note' => 'Temporarily paused.',
        'terms_accepted' => true,
    ])), null, 'Paused by policy.');

    get(route('admin.affiliates.overview.index'))
        ->assertOk()
        ->assertSeeText('Affiliate Overview')
        ->assertSeeText('Overview')
        ->assertSeeText('My Affiliate')
        ->assertSeeText('Payouts')
        ->assertDontSeeText('Reports')
        ->assertDontSeeText('Email Affiliate')
        ->assertDontSeeText('Banner')
        ->assertDontSeeText('Text Ad');

    get(route('admin.affiliates.profiles.index'))
        ->assertOk()
        ->assertSeeText('My Affiliate')
        ->assertSeeText('Overview')
        ->assertSeeText('All Affiliates')
        ->assertSeeText('Active Affiliate')
        ->assertSeeText('Suspended Affiliate')
        ->assertSeeText('Add Affiliate')
        ->assertSeeText('Payouts')
        ->assertSeeText('Pending Affiliate')
        ->assertSeeText('Pending')
        ->assertSeeText('Active')
        ->assertSeeText('Suspended')
        ->assertDontSeeText('Rejected')
        ->assertDontSeeText('Reports')
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
        ->assertSeeText('Affiliate Profile')
        ->assertSeeText('Active Partner')
        ->assertSeeText('Affiliate Identity')
        ->assertSeeText('Referral Tools')
        ->assertSeeText('Overview')
        ->assertSeeText('Commissions')
        ->assertSeeText('Payouts')
        ->assertSeeText('Traffic & Referrals')
        ->assertSeeText('Affiliate Profile')
        ->assertDontSeeText('Profile / Application')
        ->assertSeeText('Activity Log')
        ->assertSee('data-affiliate-profile-tabs', false)
        ->assertSee('data-affiliate-tab-trigger="commissions"', false)
        ->assertSee('data-affiliate-tab-panel="payouts"', false)
        ->assertSeeText('Referral Code')
        ->assertSeeText('Unique Visitors')
        ->assertSeeText('Available Balance')
        ->assertSeeText('Create Payout');

    get(route('admin.affiliates.profiles.show', [
        'affiliateProfile' => $profile,
        'tab' => 'commissions',
    ]))
        ->assertOk()
        ->assertSeeText('Commission Ledger')
        ->assertSeeText('Commission Status')
        ->assertSeeText('Approval Date')
        ->assertDontSeeText('Paid Amount')
        ->assertDontSeeText('Remaining Amount')
        ->assertDontSeeText('Display Status');

    get(route('admin.affiliates.profiles.show', [
        'affiliateProfile' => $profile,
        'tab' => 'payouts',
    ]))
        ->assertOk()
        ->assertSeeText('Payout Operations')
        ->assertSeeText('Create Paid Payout');

    get(route('admin.affiliates.profiles.show', [
        'affiliateProfile' => $profile,
        'tab' => 'traffic',
    ]))
        ->assertOk()
        ->assertSeeText('Recent Referral Activity');

    post(route('admin.affiliates.profiles.payouts.store', $profile), [
        'amount' => 60,
        'currency' => 'USD',
        'payout_method' => 'mobile_banking',
        'payout_reference' => 'MOBILE-PAID-1001',
        'transaction_reference' => 'MFS-TXN-1001',
        'admin_notes' => 'Manual mobile banking payout.',
    ])->assertRedirect(route('admin.affiliates.profiles.show', $profile));

    $payout = AffiliatePayout::query()->where('affiliate_profile_id', $profile->id)->first();

    expect($payout)->not->toBeNull()
        ->and($payout->status)->toBe(AffiliatePayout::STATUS_PAID)
        ->and((float) $payout->amount)->toBe(60.0)
        ->and($payout->payout_method)->toBe('mobile_banking')
        ->and($payout->payout_reference)->toBe('MOBILE-PAID-1001')
        ->and($payout->transaction_reference)->toBe('MFS-TXN-1001');
});

it('hides payout creation controls for non-active affiliate profiles', function () {
    $this->loginAsAdmin();

    $profile = app(AffiliateProfileService::class)->apply(Customer::factory()->create(), [
        'application_note' => 'Waiting for review.',
        'terms_accepted' => true,
    ]);

    get(route('admin.affiliates.profiles.show', $profile))
        ->assertOk()
        ->assertSeeText('Pending')
        ->assertDontSeeText('Create Payout')
        ->assertDontSeeText('Create Paid Payout');
});

it('keeps commission records focused on earnings and shows payout allocation details under payouts', function () {
    $this->loginAsAdmin();

    config()->set('commerce_affiliate.minimum_payout_amount', 1);

    $profile = app(AffiliateProfileService::class)->approve(
        app(AffiliateProfileService::class)->apply(Customer::factory()->create(), [
            'application_note' => 'Needs clear commission payout history.',
            'terms_accepted' => true,
        ]),
    );

    $fullyPaidCommission = createApprovedAffiliateCommission($profile, 25);
    $fullyPaidPayout = app(AffiliatePayoutService::class)->requestPayout($profile, 25, [
        'currency' => 'USD',
        'payout_method' => 'bank_transfer',
    ]);
    app(AffiliatePayoutService::class)->markPaid($fullyPaidPayout, reference: 'BANK-FULL-PAID', transactionReference: 'TXN-FULL-PAID');

    $partiallyPaidCommission = createApprovedAffiliateCommission($profile, 110);
    $partialPayout = app(AffiliatePayoutService::class)->requestPayout($profile, 40, [
        'currency' => 'USD',
        'payout_method' => 'bank_transfer',
    ]);
    app(AffiliatePayoutService::class)->markPaid($partialPayout, reference: 'BANK-PARTIAL-PAID', transactionReference: 'TXN-PARTIAL-PAID');

    createAffiliateAdminCommission($profile, AffiliateCommission::STATUS_PENDING, 15);
    createAffiliateAdminCommission($profile, AffiliateCommission::STATUS_REVERSED, 20);

    expect($fullyPaidCommission->refresh()->status)->toBe(AffiliateCommission::STATUS_PAID)
        ->and($partiallyPaidCommission->refresh()->status)->toBe(AffiliateCommission::STATUS_APPROVED);

    get(route('admin.affiliates.profiles.show', [
        'affiliateProfile' => $profile,
        'tab' => 'commissions',
    ]))
        ->assertOk()
        ->assertSeeText('Commission Records')
        ->assertSeeText('Commission Amount')
        ->assertSeeText('Commission Status')
        ->assertSeeText('Approval Date')
        ->assertSeeText('Pending Approval')
        ->assertSeeText('Approved')
        ->assertSeeText('Reversed')
        ->assertSeeText('Reverse')
        ->assertSeeText('$110.00')
        ->assertDontSeeText('Paid Amount')
        ->assertDontSeeText('Remaining Amount')
        ->assertDontSeeText('Display Status')
        ->assertDontSeeText('Partially Paid')
        ->assertDontSeeText('See Transactions');

    get(route('admin.affiliates.profiles.show', [
        'affiliateProfile' => $profile,
        'tab' => 'payouts',
    ]))
        ->assertOk()
        ->assertSeeText('Payout History')
        ->assertSeeText('Covered Commissions')
        ->assertSeeText('See Allocations')
        ->assertSeeText('BANK-PARTIAL-PAID')
        ->assertSeeText('Transaction No')
        ->assertSeeText('TXN-PARTIAL-PAID')
        ->assertSeeText('Bank Transfer')
        ->assertSeeText('Allocation Amount')
        ->assertSeeText('Allocation Status')
        ->assertSeeText('$40.00');
});

it('lets admin approve and reverse individual affiliate commissions in manual mode', function () {
    $this->loginAsAdmin();

    app(AffiliateSettingsService::class)->update([
        'approval_required' => true,
        'default_commission_type' => 'percentage',
        'default_commission_value' => 10,
        'commission_approval_mode' => 'manual',
        'cookie_window_days' => 30,
        'minimum_payout_amount' => 1,
        'payout_methods' => [
            'bank_transfer' => 'Bank Transfer',
        ],
        'terms_text' => 'Affiliate terms.',
    ]);

    $profile = app(AffiliateProfileService::class)->approve(
        app(AffiliateProfileService::class)->apply(Customer::factory()->create(), [
            'application_note' => 'Manual commission affiliate.',
            'terms_accepted' => true,
        ]),
    );
    $pendingCommission = createAffiliateAdminCommission($profile, AffiliateCommission::STATUS_PENDING, 110);

    get(route('admin.affiliates.profiles.show', [
        'affiliateProfile' => $profile,
        'tab' => 'commissions',
    ]))
        ->assertOk()
        ->assertSeeText('Commission approval is set to Manual')
        ->assertSeeText('Approve')
        ->assertSeeText('Reverse');

    expect(app(AffiliatePayoutService::class)->balanceFor($profile)['available_balance'])->toBe(0.0);

    post(route('admin.affiliates.commissions.approve', $pendingCommission))
        ->assertRedirect(route('admin.affiliates.profiles.show', [
            'affiliateProfile' => $profile->id,
            'tab' => 'commissions',
        ]));

    expect($pendingCommission->refresh()->status)->toBe(AffiliateCommission::STATUS_APPROVED)
        ->and(app(AffiliatePayoutService::class)->balanceFor($profile)['available_balance'])->toBe(110.0);

    $secondCommission = createAffiliateAdminCommission($profile, AffiliateCommission::STATUS_PENDING, 40);

    post(route('admin.affiliates.commissions.reverse', $secondCommission), [
        'reason' => 'Manual review failed.',
    ])->assertRedirect(route('admin.affiliates.profiles.show', [
        'affiliateProfile' => $profile->id,
        'tab' => 'commissions',
    ]));

    expect($secondCommission->refresh()->status)->toBe(AffiliateCommission::STATUS_REVERSED)
        ->and($secondCommission->reversal_reason)->toBe('Manual review failed.')
        ->and(app(AffiliatePayoutService::class)->balanceFor($profile)['available_balance'])->toBe(110.0);
});

it('shows automatic commission mode without prominent manual approve actions', function () {
    $this->loginAsAdmin();

    app(AffiliateSettingsService::class)->update([
        'approval_required' => true,
        'default_commission_type' => 'percentage',
        'default_commission_value' => 10,
        'commission_approval_mode' => 'automatic',
        'cookie_window_days' => 30,
        'minimum_payout_amount' => 1,
        'payout_methods' => [
            'bank_transfer' => 'Bank Transfer',
        ],
        'terms_text' => 'Affiliate terms.',
    ]);

    $profile = app(AffiliateProfileService::class)->approve(
        app(AffiliateProfileService::class)->apply(Customer::factory()->create(), [
            'application_note' => 'Automatic commission affiliate.',
            'terms_accepted' => true,
        ]),
    );
    createAffiliateAdminCommission($profile, AffiliateCommission::STATUS_PENDING, 110);

    get(route('admin.affiliates.profiles.show', [
        'affiliateProfile' => $profile,
        'tab' => 'commissions',
    ]))
        ->assertOk()
        ->assertSeeText('Commission approval is set to Automatic')
        ->assertDontSeeText('Pending commissions require admin approval');
});

it('keeps referral codes stable and does not expose regeneration controls', function () {
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
        ->assertSeeText('Referral Code')
        ->assertSeeText('Copy Code')
        ->assertSeeText('Copy Link')
        ->assertSee('data-affiliate-copy-value="'.$profile->referral_code.'"', false)
        ->assertSee('data-affiliate-copy-value="'.$profile->referral_url.'"', false)
        ->assertDontSeeText('Regenerate')
        ->assertDontSeeText('Regenerate Referral Code')
        ->assertSee($profile->referral_url);

    $profile->refresh();

    expect($profile->referral_code)->toBe($oldCode)
        ->and(Route::has('admin.affiliates.profiles.regenerate-referral-code'))->toBeFalse();
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
        'transaction_reference' => 'TXN-2002',
    ])->assertRedirect(route('admin.affiliates.payouts.index', ['status' => AffiliatePayout::STATUS_PAID]));

    expect($payout->refresh()->status)->toBe(AffiliatePayout::STATUS_PAID)
        ->and($payout->payout_reference)->toBe('BANK-PAID-2002')
        ->and($payout->transaction_reference)->toBe('TXN-2002');
});

function createApprovedAffiliateCommission(AffiliateProfile $profile, float $amount): AffiliateCommission
{
    return createAffiliateAdminCommission($profile, AffiliateCommission::STATUS_APPROVED, $amount);
}

function createAffiliateAdminCommission(AffiliateProfile $profile, string $status, float $amount): AffiliateCommission
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
        'status' => $status,
        'commission_type' => 'fixed',
        'commission_rate' => $amount,
        'order_amount' => $amount * 10,
        'commission_amount' => $amount,
        'currency' => 'USD',
        'eligible_at' => $status === AffiliateCommission::STATUS_APPROVED ? now() : null,
        'approved_at' => $status === AffiliateCommission::STATUS_APPROVED ? now() : null,
        'reversed_at' => $status === AffiliateCommission::STATUS_REVERSED ? now() : null,
    ]);
}
