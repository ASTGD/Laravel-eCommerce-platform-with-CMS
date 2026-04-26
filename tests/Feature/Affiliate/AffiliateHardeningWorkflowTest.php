<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Platform\CommerceCore\Listeners\AttributeAffiliateOrder;
use Platform\CommerceCore\Models\AffiliateClick;
use Platform\CommerceCore\Models\AffiliateCommission;
use Platform\CommerceCore\Models\AffiliateOrderAttribution;
use Platform\CommerceCore\Models\AffiliatePayout;
use Platform\CommerceCore\Models\AffiliateProfile;
use Platform\CommerceCore\Models\AffiliateSetting;
use Platform\CommerceCore\Services\Affiliates\AffiliateCommissionService;
use Platform\CommerceCore\Services\Affiliates\AffiliatePayoutService;
use Platform\CommerceCore\Services\Affiliates\AffiliateReportService;
use Platform\CommerceCore\Services\Affiliates\AffiliateSettingsService;
use Webkul\Admin\Tests\AdminTestCase;
use Webkul\Customer\Models\Customer;
use Webkul\Sales\Models\Order;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(AdminTestCase::class);

beforeEach(function () {
    AffiliateSetting::query()->delete();
});

it('keeps the full affiliate workflow synchronized between customer portal admin and shared records', function () {
    app(AffiliateSettingsService::class)->update([
        'approval_required' => true,
        'default_commission_type' => 'percentage',
        'default_commission_value' => 10,
        'cookie_window_days' => 30,
        'minimum_payout_amount' => 10,
        'payout_methods' => [
            'bank_transfer' => 'Bank Transfer',
        ],
        'terms_text' => 'Phase 8 affiliate terms.',
    ]);

    $affiliateCustomer = Customer::factory()->create([
        'first_name' => 'Unified',
        'last_name' => 'Affiliate',
    ]);

    actingAs($affiliateCustomer, 'customer');

    post(route('shop.customers.account.affiliate.apply'), [
        'application_note' => 'I will promote this store through my audience.',
        'website_url' => 'https://affiliate.example.test',
        'payout_method' => 'bank_transfer',
        'payout_reference' => 'Bank account 123',
        'terms_accepted' => '1',
    ])->assertRedirectToRoute('shop.customers.account.affiliate.index')
        ->assertSessionHasNoErrors();

    $profile = AffiliateProfile::query()->where('customer_id', $affiliateCustomer->id)->firstOrFail();

    expect($profile->status)->toBe(AffiliateProfile::STATUS_PENDING);

    get(route('shop.customers.account.affiliate.index'))
        ->assertOk()
        ->assertSeeText('Application under review')
        ->assertDontSeeText('Affiliate Dashboard');

    $this->loginAsAdmin();

    post(route('admin.affiliates.profiles.approve', $profile))
        ->assertRedirect(route('admin.affiliates.profiles.show', $profile));

    expect($profile->refresh()->status)->toBe(AffiliateProfile::STATUS_ACTIVE);

    actingAs($affiliateCustomer, 'customer');

    get(route('shop.customers.account.affiliate.index'))
        ->assertOk()
        ->assertSeeText('Affiliate Dashboard')
        ->assertSeeText($profile->referral_code)
        ->assertSee($profile->referral_url);

    $buyer = Customer::factory()->create();

    actingAs($buyer, 'customer');

    get(route('shop.home.index', [
        'ref' => $profile->referral_code,
    ]))->assertOk()
        ->assertSessionHas('commerce_affiliate.referral.code', $profile->referral_code);

    $order = Order::factory()->create([
        'customer_id' => $buyer->id,
        'base_sub_total' => 800,
        'base_grand_total' => 850,
        'grand_total' => 850,
        'base_currency_code' => 'USD',
        'order_currency_code' => 'USD',
    ]);

    app(AttributeAffiliateOrder::class)->handle($order);

    $attribution = AffiliateOrderAttribution::query()->where('order_id', $order->id)->firstOrFail();
    $commission = AffiliateCommission::query()->where('order_id', $order->id)->firstOrFail();

    expect($attribution->affiliate_profile_id)->toBe($profile->id)
        ->and($commission->affiliate_profile_id)->toBe($profile->id)
        ->and($commission->status)->toBe(AffiliateCommission::STATUS_PENDING)
        ->and((float) $commission->commission_amount)->toBe(80.0);

    app(AffiliateCommissionService::class)->approve($commission);

    $balance = app(AffiliatePayoutService::class)->balanceFor($profile);

    expect($balance['available_balance'])->toBe(80.0);

    actingAs($affiliateCustomer, 'customer');

    post(route('shop.customers.account.affiliate.withdrawals.store'), [
        'amount' => 40,
        'payout_method' => 'bank_transfer',
        'payout_reference' => 'Bank account 123',
        'notes' => 'Please process this payout.',
    ])->assertRedirectToRoute('shop.customers.account.affiliate.index')
        ->assertSessionHasNoErrors();

    $payout = AffiliatePayout::query()->where('affiliate_profile_id', $profile->id)->firstOrFail();

    expect($payout->status)->toBe(AffiliatePayout::STATUS_REQUESTED)
        ->and((float) $payout->amount)->toBe(40.0)
        ->and(app(AffiliatePayoutService::class)->balanceFor($profile)['reserved_payouts'])->toBe(40.0)
        ->and(app(AffiliatePayoutService::class)->balanceFor($profile)['available_balance'])->toBe(40.0);

    $this->loginAsAdmin();

    post(route('admin.affiliates.payouts.mark-paid', $payout), [
        'payout_reference' => 'BANK-PAID-PHASE8',
    ])->assertRedirect(route('admin.affiliates.payouts.index', ['status' => AffiliatePayout::STATUS_PAID]));

    expect($payout->refresh()->status)->toBe(AffiliatePayout::STATUS_PAID)
        ->and($payout->payout_reference)->toBe('BANK-PAID-PHASE8')
        ->and(app(AffiliatePayoutService::class)->balanceFor($profile)['paid_payouts'])->toBe(40.0)
        ->and(app(AffiliatePayoutService::class)->balanceFor($profile)['available_balance'])->toBe(40.0);

    actingAs($affiliateCustomer, 'customer');

    get(route('shop.customers.account.affiliate.index'))
        ->assertOk()
        ->assertSeeText('Paid')
        ->assertSeeText('BANK-PAID-PHASE8');
});

it('calculates report totals directly from the shared affiliate records', function () {
    $before = app(AffiliateReportService::class)->summary();

    $firstProfile = phaseEightAffiliateProfile('Report', 'One');
    $secondProfile = phaseEightAffiliateProfile('Report', 'Two');
    $firstClick = phaseEightAffiliateClick($firstProfile);

    $firstOrder = Order::factory()->create([
        'customer_id' => Customer::factory()->create()->id,
        'base_grand_total' => 300,
        'base_currency_code' => 'USD',
        'order_currency_code' => 'USD',
    ]);
    $secondOrder = Order::factory()->create([
        'customer_id' => Customer::factory()->create()->id,
        'base_grand_total' => 700,
        'base_currency_code' => 'USD',
        'order_currency_code' => 'USD',
    ]);
    $canceledOrder = Order::factory()->create([
        'customer_id' => Customer::factory()->create()->id,
        'base_grand_total' => 900,
    ]);

    $firstAttribution = phaseEightAffiliateAttribution($firstProfile, $firstOrder, $firstClick);
    $secondAttribution = phaseEightAffiliateAttribution($secondProfile, $secondOrder);
    phaseEightAffiliateAttribution($firstProfile, $canceledOrder, null, AffiliateOrderAttribution::STATUS_CANCELED);

    phaseEightAffiliateCommission($firstProfile, $firstOrder, $firstAttribution, AffiliateCommission::STATUS_PENDING, 30);
    phaseEightAffiliateCommission($secondProfile, $secondOrder, $secondAttribution, AffiliateCommission::STATUS_APPROVED, 70);
    phaseEightAffiliatePayout($secondProfile, AffiliatePayout::STATUS_PAID, 50);
    phaseEightAffiliatePayout($firstProfile, AffiliatePayout::STATUS_REJECTED, 20);

    $summary = app(AffiliateReportService::class)->summary();

    expect($summary['total_affiliates'] - $before['total_affiliates'])->toBe(2)
        ->and($summary['active_affiliates'] - $before['active_affiliates'])->toBe(2)
        ->and($summary['total_clicks'] - $before['total_clicks'])->toBe(1)
        ->and($summary['attributed_orders'] - $before['attributed_orders'])->toBe(2)
        ->and(round((float) $summary['attributed_sales_total'] - (float) $before['attributed_sales_total'], 4))->toBe(1000.0)
        ->and(round((float) $summary['commissions'][AffiliateCommission::STATUS_PENDING] - (float) $before['commissions'][AffiliateCommission::STATUS_PENDING], 4))->toBe(30.0)
        ->and(round((float) $summary['commissions'][AffiliateCommission::STATUS_APPROVED] - (float) $before['commissions'][AffiliateCommission::STATUS_APPROVED], 4))->toBe(70.0)
        ->and(round((float) $summary['payouts'][AffiliatePayout::STATUS_PAID] - (float) $before['payouts'][AffiliatePayout::STATUS_PAID], 4))->toBe(50.0)
        ->and(round((float) $summary['payouts'][AffiliatePayout::STATUS_REJECTED] - (float) $before['payouts'][AffiliatePayout::STATUS_REJECTED], 4))->toBe(20.0);
});

it('does not register excluded affiliate MVP routes menu items or ACL entries', function () {
    $routeNames = collect(Route::getRoutes()->getRoutesByName())
        ->keys()
        ->filter(fn (string $name): bool => str_contains($name, 'affiliate'));
    $routeUris = collect(Route::getRoutes()->getRoutes())
        ->map(fn ($route): string => $route->uri())
        ->filter(fn (string $uri): bool => str_contains($uri, 'affiliate'));
    $menuItems = collect(config('menu.admin'))->where(fn (array $item): bool => str_starts_with((string) ($item['key'] ?? ''), 'affiliates'));
    $aclItems = collect(config('acl'))->where(fn (array $item): bool => str_starts_with((string) ($item['key'] ?? ''), 'affiliates'));

    foreach (['email', 'banner', 'text-ad', 'text_ad'] as $excludedToken) {
        expect($routeNames->filter(fn (string $name): bool => str_contains($name, $excludedToken))->all())->toBe([])
            ->and($routeUris->filter(fn (string $uri): bool => str_contains($uri, $excludedToken))->all())->toBe([])
            ->and($menuItems->filter(fn (array $item): bool => str_contains(strtolower((string) ($item['name'] ?? '')), str_replace(['-', '_'], ' ', $excludedToken)))->all())->toBe([])
            ->and($aclItems->filter(fn (array $item): bool => str_contains(strtolower((string) ($item['name'] ?? '')), str_replace(['-', '_'], ' ', $excludedToken)))->all())->toBe([]);
    }
});

function phaseEightAffiliateProfile(string $firstName = 'Phase', string $lastName = 'Eight'): AffiliateProfile
{
    return AffiliateProfile::query()->create([
        'customer_id' => Customer::factory()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
        ])->id,
        'status' => AffiliateProfile::STATUS_ACTIVE,
        'referral_code' => 'P8'.Str::upper(Str::random(8)),
        'application_source' => 'test',
        'terms_accepted_at' => now(),
        'approved_at' => now(),
        'last_status_changed_at' => now(),
    ]);
}

function phaseEightAffiliateClick(AffiliateProfile $profile): AffiliateClick
{
    return AffiliateClick::query()->create([
        'affiliate_profile_id' => $profile->id,
        'referral_code' => $profile->referral_code,
        'clicked_at' => now(),
    ]);
}

function phaseEightAffiliateAttribution(
    AffiliateProfile $profile,
    Order $order,
    ?AffiliateClick $click = null,
    string $status = AffiliateOrderAttribution::STATUS_ATTRIBUTED,
): AffiliateOrderAttribution {
    return AffiliateOrderAttribution::query()->create([
        'affiliate_profile_id' => $profile->id,
        'affiliate_click_id' => $click?->id,
        'order_id' => $order->id,
        'referral_code' => $profile->referral_code,
        'attribution_source' => 'session',
        'status' => $status,
        'attributed_at' => now(),
        'expires_at' => now()->addDays(30),
    ]);
}

function phaseEightAffiliateCommission(
    AffiliateProfile $profile,
    Order $order,
    AffiliateOrderAttribution $attribution,
    string $status,
    float $amount,
): AffiliateCommission {
    return AffiliateCommission::query()->create([
        'affiliate_profile_id' => $profile->id,
        'affiliate_order_attribution_id' => $attribution->id,
        'order_id' => $order->id,
        'status' => $status,
        'commission_type' => 'fixed',
        'commission_rate' => $amount,
        'order_amount' => $order->base_grand_total,
        'commission_amount' => $amount,
        'currency' => 'USD',
        'eligible_at' => now(),
        'approved_at' => $status === AffiliateCommission::STATUS_APPROVED ? now() : null,
        'paid_at' => $status === AffiliateCommission::STATUS_PAID ? now() : null,
        'reversed_at' => $status === AffiliateCommission::STATUS_REVERSED ? now() : null,
    ]);
}

function phaseEightAffiliatePayout(AffiliateProfile $profile, string $status, float $amount): AffiliatePayout
{
    return AffiliatePayout::query()->create([
        'affiliate_profile_id' => $profile->id,
        'status' => $status,
        'amount' => $amount,
        'currency' => 'USD',
        'payout_method' => 'bank_transfer',
        'payout_reference' => 'P8-'.Str::upper(Str::random(8)),
        'requested_at' => now(),
        'approved_at' => in_array($status, [AffiliatePayout::STATUS_APPROVED, AffiliatePayout::STATUS_PAID], true) ? now() : null,
        'paid_at' => $status === AffiliatePayout::STATUS_PAID ? now() : null,
        'rejected_at' => $status === AffiliatePayout::STATUS_REJECTED ? now() : null,
    ]);
}
