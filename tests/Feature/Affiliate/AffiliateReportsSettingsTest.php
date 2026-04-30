<?php

use Illuminate\Support\Str;
use Platform\CommerceCore\Models\AffiliateClick;
use Platform\CommerceCore\Models\AffiliateCommission;
use Platform\CommerceCore\Models\AffiliateOrderAttribution;
use Platform\CommerceCore\Models\AffiliatePayout;
use Platform\CommerceCore\Models\AffiliateProfile;
use Platform\CommerceCore\Models\AffiliateSetting;
use Platform\CommerceCore\Services\Affiliates\AffiliateProfileService;
use Platform\CommerceCore\Services\Affiliates\AffiliateReportService;
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
        ->assertSeeText('Unique Visitors')
        ->assertSeeText('Referred Orders')
        ->assertSeeText('Traffic, Orders and Commission Trend')
        ->assertSeeText('Payout and Reversal Trend')
        ->assertSeeText('Affiliate Registrations')
        ->assertSeeText('New affiliate applications created during the selected range.')
        ->assertSee('data-affiliate-report-chart="traffic"', false)
        ->assertSee('data-affiliate-report-chart="payout-reversal"', false)
        ->assertSee('data-affiliate-report-chart="registrations"', false)
        ->assertSeeText('Top Affiliates')
        ->assertSeeText('Recent Payouts')
        ->assertSeeText($profile->referral_code)
        ->assertDontSeeText('Email Affiliate')
        ->assertDontSeeText('Banner')
        ->assertDontSeeText('Text Ad');
});

it('builds report KPIs and chart series from real affiliate records only', function () {
    $service = app(AffiliateReportService::class);
    $before = $service->dashboard(30);
    $today = now()->format('Y-m-d');
    $beforeToday = collect($before['series']['rows'])->firstWhere('date', $today) ?? [
        'clicks' => 0,
        'orders' => 0,
        'commissions' => 0,
        'payouts' => 0,
        'reversed' => 0,
        'registrations' => 0,
    ];

    $profile = phaseSevenActiveAffiliateProfile();
    $sessionId = 'reports-real-session-'.Str::random(8);
    $firstClick = AffiliateClick::query()->create([
        'affiliate_profile_id' => $profile->id,
        'referral_code' => $profile->referral_code,
        'session_id' => $sessionId,
        'ip_address' => '192.0.2.10',
        'landing_url' => url('/products?ref='.$profile->referral_code),
        'clicked_at' => now(),
    ]);
    AffiliateClick::query()->create([
        'affiliate_profile_id' => $profile->id,
        'referral_code' => $profile->referral_code,
        'session_id' => $sessionId,
        'ip_address' => '192.0.2.10',
        'landing_url' => url('/category?ref='.$profile->referral_code),
        'clicked_at' => now(),
    ]);

    $order = Order::factory()->create([
        'customer_id' => Customer::factory()->create()->id,
        'base_sub_total' => 500,
        'base_grand_total' => 500,
        'base_currency_code' => 'USD',
        'order_currency_code' => 'USD',
    ]);
    $reversedOrder = Order::factory()->create([
        'customer_id' => Customer::factory()->create()->id,
        'base_sub_total' => 100,
        'base_grand_total' => 100,
        'base_currency_code' => 'USD',
        'order_currency_code' => 'USD',
    ]);

    $attribution = AffiliateOrderAttribution::query()->create([
        'affiliate_profile_id' => $profile->id,
        'affiliate_click_id' => $firstClick->id,
        'order_id' => $order->id,
        'referral_code' => $profile->referral_code,
        'attribution_source' => 'cookie',
        'status' => AffiliateOrderAttribution::STATUS_ATTRIBUTED,
        'attributed_at' => now(),
        'expires_at' => now()->addDays(30),
    ]);
    $reversedAttribution = AffiliateOrderAttribution::query()->create([
        'affiliate_profile_id' => $profile->id,
        'affiliate_click_id' => $firstClick->id,
        'order_id' => $reversedOrder->id,
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
    AffiliateCommission::query()->create([
        'affiliate_profile_id' => $profile->id,
        'affiliate_order_attribution_id' => $reversedAttribution->id,
        'order_id' => $reversedOrder->id,
        'status' => AffiliateCommission::STATUS_REVERSED,
        'commission_type' => 'percentage',
        'commission_rate' => 10,
        'order_amount' => 100,
        'commission_amount' => 10,
        'currency' => 'USD',
        'eligible_at' => now(),
        'approved_at' => now(),
        'reversed_at' => now(),
    ]);

    AffiliatePayout::query()->create([
        'affiliate_profile_id' => $profile->id,
        'status' => AffiliatePayout::STATUS_PAID,
        'amount' => 15,
        'currency' => 'USD',
        'payout_method' => 'bank_transfer',
        'payout_reference' => 'REPORT-PAID-'.Str::random(8),
        'requested_at' => now(),
        'paid_at' => now(),
    ]);
    AffiliatePayout::query()->create([
        'affiliate_profile_id' => $profile->id,
        'status' => AffiliatePayout::STATUS_REQUESTED,
        'amount' => 5,
        'currency' => 'USD',
        'payout_method' => 'bank_transfer',
        'payout_reference' => 'REPORT-REQ-'.Str::random(8),
        'requested_at' => now(),
    ]);

    $after = $service->dashboard(30);
    $weekly = $service->dashboard(7);
    $payoutAndReversalSeries = $service->dailyPayoutAndReversalSeries(30);
    $registrationSeries = $service->dailyRegistrationSeries(30);
    $afterToday = collect($after['series']['rows'])->firstWhere('date', $today);
    $payoutAndReversalTodayIndex = array_search($today, $payoutAndReversalSeries['labels'], true);
    $registrationTodayIndex = array_search($today, $registrationSeries['labels'], true);

    expect($after['kpis']['total_clicks'] - $before['kpis']['total_clicks'])->toBe(2)
        ->and($after['kpis']['unique_visitors'] - $before['kpis']['unique_visitors'])->toBe(1)
        ->and($after['kpis']['referred_orders'] - $before['kpis']['referred_orders'])->toBe(2)
        ->and(round($after['kpis']['total_commission_earned'] - $before['kpis']['total_commission_earned'], 4))->toBe(50.0)
        ->and(round($after['kpis']['available_balance'] - $before['kpis']['available_balance'], 4))->toBe(35.0)
        ->and(round($after['kpis']['paid_out'] - $before['kpis']['paid_out'], 4))->toBe(15.0)
        ->and($after['kpis']['pending_payout_requests'] - $before['kpis']['pending_payout_requests'])->toBe(1)
        ->and(round($after['kpis']['reversed_commissions'] - $before['kpis']['reversed_commissions'], 4))->toBe(10.0)
        ->and($afterToday['clicks'] - $beforeToday['clicks'])->toBe(2)
        ->and($afterToday['orders'] - $beforeToday['orders'])->toBe(2)
        ->and(round($afterToday['commissions'] - $beforeToday['commissions'], 4))->toBe(50.0)
        ->and(round($afterToday['payouts'] - $beforeToday['payouts'], 4))->toBe(15.0)
        ->and(round($afterToday['reversed'] - $beforeToday['reversed'], 4))->toBe(10.0)
        ->and($afterToday['registrations'] - $beforeToday['registrations'])->toBe(1)
        ->and(round($payoutAndReversalSeries['paid_payouts'][$payoutAndReversalTodayIndex], 4))->toBeGreaterThanOrEqual(15.0)
        ->and(round($payoutAndReversalSeries['reversed_commissions'][$payoutAndReversalTodayIndex], 4))->toBeGreaterThanOrEqual(10.0)
        ->and($registrationSeries['registrations'][$registrationTodayIndex])->toBeGreaterThanOrEqual(1)
        ->and($weekly['series']['rows'])->toHaveCount(7);
});

it('scopes report chart series to the selected date range', function () {
    $service = app(AffiliateReportService::class);
    $oldDate = now()->subDays(10)->startOfDay()->addHours(10);
    $oldDateKey = $oldDate->format('Y-m-d');
    $beforeWeekly = $service->dailySeries(7);
    $beforeMonthly = $service->dailySeries(30);
    $beforeMonthlyRow = collect($beforeMonthly['rows'])->firstWhere('date', $oldDateKey) ?? [
        'clicks' => 0,
        'orders' => 0,
        'commissions' => 0,
        'payouts' => 0,
        'reversed' => 0,
        'registrations' => 0,
    ];

    $profile = phaseSevenActiveAffiliateProfile();
    $profile->forceFill([
        'created_at' => $oldDate,
        'updated_at' => $oldDate,
    ])->save();

    $click = AffiliateClick::query()->create([
        'affiliate_profile_id' => $profile->id,
        'referral_code' => $profile->referral_code,
        'session_id' => 'reports-old-session-'.Str::random(8),
        'ip_address' => '192.0.2.20',
        'landing_url' => url('/old-range?ref='.$profile->referral_code),
        'clicked_at' => $oldDate,
    ]);

    $order = Order::factory()->create([
        'customer_id' => Customer::factory()->create()->id,
        'base_sub_total' => 200,
        'base_grand_total' => 200,
        'base_currency_code' => 'USD',
        'order_currency_code' => 'USD',
        'created_at' => $oldDate,
        'updated_at' => $oldDate,
    ]);
    $reversedOrder = Order::factory()->create([
        'customer_id' => Customer::factory()->create()->id,
        'base_sub_total' => 40,
        'base_grand_total' => 40,
        'base_currency_code' => 'USD',
        'order_currency_code' => 'USD',
        'created_at' => $oldDate,
        'updated_at' => $oldDate,
    ]);

    $attribution = AffiliateOrderAttribution::query()->create([
        'affiliate_profile_id' => $profile->id,
        'affiliate_click_id' => $click->id,
        'order_id' => $order->id,
        'referral_code' => $profile->referral_code,
        'attribution_source' => 'cookie',
        'status' => AffiliateOrderAttribution::STATUS_ATTRIBUTED,
        'attributed_at' => $oldDate,
        'expires_at' => $oldDate->copy()->addDays(30),
    ]);
    $reversedAttribution = AffiliateOrderAttribution::query()->create([
        'affiliate_profile_id' => $profile->id,
        'affiliate_click_id' => $click->id,
        'order_id' => $reversedOrder->id,
        'referral_code' => $profile->referral_code,
        'attribution_source' => 'cookie',
        'status' => AffiliateOrderAttribution::STATUS_ATTRIBUTED,
        'attributed_at' => $oldDate,
        'expires_at' => $oldDate->copy()->addDays(30),
    ]);

    $approvedCommission = AffiliateCommission::query()->create([
        'affiliate_profile_id' => $profile->id,
        'affiliate_order_attribution_id' => $attribution->id,
        'order_id' => $order->id,
        'status' => AffiliateCommission::STATUS_APPROVED,
        'commission_type' => 'percentage',
        'commission_rate' => 10,
        'order_amount' => 200,
        'commission_amount' => 20,
        'currency' => 'USD',
        'eligible_at' => $oldDate,
        'approved_at' => $oldDate,
    ]);
    $approvedCommission->forceFill([
        'created_at' => $oldDate,
        'updated_at' => $oldDate,
    ])->save();

    $reversedCommission = AffiliateCommission::query()->create([
        'affiliate_profile_id' => $profile->id,
        'affiliate_order_attribution_id' => $reversedAttribution->id,
        'order_id' => $reversedOrder->id,
        'status' => AffiliateCommission::STATUS_REVERSED,
        'commission_type' => 'percentage',
        'commission_rate' => 10,
        'order_amount' => 40,
        'commission_amount' => 4,
        'currency' => 'USD',
        'eligible_at' => $oldDate,
        'approved_at' => $oldDate,
    ]);
    $reversedCommission->forceFill([
        'created_at' => $oldDate,
        'updated_at' => $oldDate,
    ])->save();

    $payout = AffiliatePayout::query()->create([
        'affiliate_profile_id' => $profile->id,
        'status' => AffiliatePayout::STATUS_PAID,
        'amount' => 7,
        'currency' => 'USD',
        'payout_method' => 'bank_transfer',
        'payout_reference' => 'REPORT-OLD-'.Str::random(8),
        'requested_at' => $oldDate,
    ]);
    $payout->forceFill([
        'created_at' => $oldDate,
        'updated_at' => $oldDate,
    ])->save();

    $afterWeekly = $service->dailySeries(7);
    $afterMonthly = $service->dailySeries(30);
    $afterMonthlyRow = collect($afterMonthly['rows'])->firstWhere('date', $oldDateKey);

    expect(collect($beforeWeekly['rows'])->firstWhere('date', $oldDateKey))->toBeNull()
        ->and(collect($afterWeekly['rows'])->firstWhere('date', $oldDateKey))->toBeNull()
        ->and($afterMonthlyRow)->not->toBeNull()
        ->and($afterMonthlyRow['clicks'] - $beforeMonthlyRow['clicks'])->toBe(1)
        ->and($afterMonthlyRow['orders'] - $beforeMonthlyRow['orders'])->toBe(2)
        ->and(round($afterMonthlyRow['commissions'] - $beforeMonthlyRow['commissions'], 4))->toBe(20.0)
        ->and(round($afterMonthlyRow['payouts'] - $beforeMonthlyRow['payouts'], 4))->toBe(7.0)
        ->and(round($afterMonthlyRow['reversed'] - $beforeMonthlyRow['reversed'], 4))->toBe(4.0)
        ->and($afterMonthlyRow['registrations'] - $beforeMonthlyRow['registrations'])->toBe(1);
});

it('lets admin save affiliate settings used by the shared affiliate domain', function () {
    $this->loginAsAdmin();

    AffiliateSetting::query()->delete();

    get(route('admin.affiliates.settings.index'))
        ->assertOk()
        ->assertSeeText('Affiliate Settings')
        ->assertSeeText('Approval Required')
        ->assertSeeText('Affiliate Commission Approval')
        ->assertSeeText('Default Commission Type')
        ->assertSeeText('Payout Methods');

    post(route('admin.affiliates.settings.update'), [
        'approval_required' => '0',
        'default_commission_type' => 'fixed',
        'default_commission_value' => '25',
        'commission_approval_mode' => 'automatic',
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
        ->and($settingsService->commissionApprovalMode())->toBe('automatic')
        ->and($settingsService->usesAutomaticCommissionApproval())->toBeTrue()
        ->and($settingsService->usesManualCommissionApproval())->toBeFalse()
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
        'commission_approval_mode' => 'manual',
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
