<?php

use Platform\CommerceCore\Models\AffiliateClick;
use Platform\CommerceCore\Models\AffiliateCommission;
use Platform\CommerceCore\Models\AffiliateOrderAttribution;
use Platform\CommerceCore\Models\AffiliatePayout;
use Platform\CommerceCore\Models\AffiliateProfile;
use Platform\CommerceCore\Models\AffiliateSetting;
use Platform\CommerceCore\Services\Affiliates\AffiliateProfileService;
use Platform\CommerceCore\Services\Affiliates\AffiliateSettingsService;
use Webkul\Admin\Tests\AdminTestCase;
use Webkul\Customer\Models\Customer;
use Webkul\Sales\Models\Order;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(AdminTestCase::class);

it('shows affiliate reports from shared traffic sales commission and payout records', function () {
    $this->loginAsAdmin();

    AffiliateSetting::query()->delete();

    $profile = phaseSevenActiveAffiliateProfile();
    $click = AffiliateClick::query()->create([
        'affiliate_profile_id' => $profile->id,
        'referral_code' => $profile->referral_code,
        'landing_url' => url('/?ref='.$profile->referral_code),
        'clicked_at' => now(),
    ]);

    $order = Order::factory()->create([
        'customer_id' => Customer::factory()->create()->id,
        'base_sub_total' => 500,
        'base_grand_total' => 550,
        'base_currency_code' => 'USD',
        'order_currency_code' => 'USD',
    ]);

    $attribution = AffiliateOrderAttribution::query()->create([
        'affiliate_profile_id' => $profile->id,
        'affiliate_click_id' => $click->id,
        'order_id' => $order->id,
        'referral_code' => $profile->referral_code,
        'attribution_source' => 'cookie',
        'status' => AffiliateOrderAttribution::STATUS_ATTRIBUTED,
        'attributed_at' => now(),
        'expires_at' => now()->addDays(30),
    ]);

    AffiliateCommission::query()->create([
        'affiliate_profile_id' => $profile->id,
        'affiliate_order_attribution_id' => $attribution->id,
        'order_id' => $order->id,
        'status' => AffiliateCommission::STATUS_APPROVED,
        'commission_type' => 'percentage',
        'commission_rate' => 10,
        'order_amount' => 500,
        'commission_amount' => 50,
        'currency' => 'USD',
        'eligible_at' => now(),
        'approved_at' => now(),
    ]);

    AffiliatePayout::query()->create([
        'affiliate_profile_id' => $profile->id,
        'status' => AffiliatePayout::STATUS_REQUESTED,
        'amount' => 25,
        'currency' => 'USD',
        'payout_method' => 'bank_transfer',
        'requested_at' => now(),
    ]);

    get(route('admin.affiliates.reports.index'))
        ->assertOk()
        ->assertSeeText('Affiliate Reports')
        ->assertSeeText('Total Clicks')
        ->assertSeeText('Attributed Orders')
        ->assertSeeText('Statistics Graph')
        ->assertSeeText('Top Affiliates')
        ->assertSeeText($profile->referral_code)
        ->assertDontSeeText('Email Affiliate')
        ->assertDontSeeText('Banner')
        ->assertDontSeeText('Text Ad');
});

it('lets admin save affiliate settings used by the shared affiliate domain', function () {
    $this->loginAsAdmin();

    AffiliateSetting::query()->delete();

    get(route('admin.affiliates.settings.index'))
        ->assertOk()
        ->assertSeeText('Affiliate Settings')
        ->assertSeeText('Approval Required')
        ->assertSeeText('Default Commission Type')
        ->assertSeeText('Payout Methods');

    post(route('admin.affiliates.settings.update'), [
        'approval_required' => '0',
        'default_commission_type' => 'fixed',
        'default_commission_value' => '25',
        'cookie_window_days' => '45',
        'minimum_payout_amount' => '75',
        'payout_methods_text' => "bank_transfer=Bank Transfer\npaypal=PayPal",
        'terms_text' => 'Updated affiliate terms.',
    ])
        ->assertRedirect(route('admin.affiliates.settings.index'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', 'Affiliate settings saved.');

    $settingsService = app(AffiliateSettingsService::class);

    expect($settingsService->approvalRequired())->toBeFalse()
        ->and($settingsService->defaultCommission())->toMatchArray([
            'type' => 'fixed',
            'value' => 25.0,
        ])
        ->and($settingsService->cookieWindowDays())->toBe(45)
        ->and($settingsService->minimumPayoutAmount())->toBe(75.0)
        ->and($settingsService->payoutMethods())->toMatchArray([
            'bank_transfer' => 'Bank Transfer',
            'paypal' => 'PayPal',
        ])
        ->and($settingsService->termsText())->toBe('Updated affiliate terms.');

    AffiliateSetting::query()->delete();
});

it('uses saved approval settings when customers apply as affiliates', function () {
    AffiliateSetting::query()->delete();

    app(AffiliateSettingsService::class)->update([
        'approval_required' => false,
        'default_commission_type' => 'percentage',
        'default_commission_value' => 10,
        'cookie_window_days' => 30,
        'minimum_payout_amount' => 50,
        'payout_methods' => [
            'bank_transfer' => 'Bank Transfer',
        ],
        'terms_text' => 'Current terms.',
    ]);

    $profile = app(AffiliateProfileService::class)->apply(Customer::factory()->create(), [
        'application_note' => 'Auto approve me.',
        'terms_accepted' => true,
    ]);

    expect($profile->status)->toBe(AffiliateProfile::STATUS_ACTIVE)
        ->and($profile->approved_at)->not->toBeNull();

    AffiliateSetting::query()->delete();
});

function phaseSevenActiveAffiliateProfile(): AffiliateProfile
{
    $customer = Customer::factory()->create([
        'first_name' => 'Report',
        'last_name' => 'Affiliate',
    ]);

    $profileService = app(AffiliateProfileService::class);

    return $profileService->approve($profileService->apply($customer, [
        'application_note' => 'Report test affiliate.',
        'terms_accepted' => true,
    ]));
}
