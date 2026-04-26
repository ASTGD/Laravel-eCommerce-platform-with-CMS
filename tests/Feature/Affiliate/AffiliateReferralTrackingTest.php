<?php

use Platform\CommerceCore\Listeners\AttributeAffiliateOrder;
use Platform\CommerceCore\Listeners\ReverseAffiliateCommissionForCanceledOrder;
use Platform\CommerceCore\Models\AffiliateClick;
use Platform\CommerceCore\Models\AffiliateCommission;
use Platform\CommerceCore\Models\AffiliateOrderAttribution;
use Platform\CommerceCore\Models\AffiliateProfile;
use Platform\CommerceCore\Services\Affiliates\AffiliateProfileService;
use Platform\CommerceCore\Services\Affiliates\AffiliateReferralTrackingService;
use Tests\TestCase;
use Webkul\Customer\Models\Customer;
use Webkul\Sales\Models\Order;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\withCookie;

uses(TestCase::class);

it('captures an active affiliate referral click into session and cookie attribution', function () {
    $affiliate = Customer::factory()->create();
    $profile = activeAffiliateProfileFor($affiliate);

    $response = get(route('shop.home.index', [
        'ref' => $profile->referral_code,
    ]));

    $response->assertOk()
        ->assertSessionHas(AffiliateReferralTrackingService::SESSION_KEY.'.code', $profile->referral_code)
        ->assertCookie(config('commerce_affiliate.cookie_name'));

    $click = AffiliateClick::query()
        ->where('affiliate_profile_id', $profile->id)
        ->where('referral_code', $profile->referral_code)
        ->first();

    expect($click)->not->toBeNull()
        ->and($click->affiliate_profile_id)->toBe($profile->id)
        ->and($click->referral_code)->toBe($profile->referral_code)
        ->and($click->landing_url)->toContain('ref='.$profile->referral_code);
});

it('attributes checkout orders from the captured referral and creates a pending commission', function () {
    config()->set('commerce_affiliate.default_commission', [
        'type' => 'percentage',
        'value' => 10,
    ]);

    $affiliate = Customer::factory()->create();
    $buyer = Customer::factory()->create();
    $profile = activeAffiliateProfileFor($affiliate);

    actingAs($buyer, 'customer');

    get(route('shop.home.index', [
        'ref' => $profile->referral_code,
    ]))->assertOk();

    $order = Order::factory()->create([
        'customer_id' => $buyer->id,
        'base_sub_total' => 500,
        'base_grand_total' => 550,
        'grand_total' => 550,
        'base_currency_code' => 'USD',
        'order_currency_code' => 'USD',
    ]);

    app(AttributeAffiliateOrder::class)->handle($order);

    $attribution = AffiliateOrderAttribution::query()->where('order_id', $order->id)->first();
    $commission = AffiliateCommission::query()->where('order_id', $order->id)->first();
    $click = AffiliateClick::query()
        ->where('affiliate_profile_id', $profile->id)
        ->where('referral_code', $profile->referral_code)
        ->latest('id')
        ->first();

    expect($attribution)->not->toBeNull()
        ->and($attribution->affiliate_profile_id)->toBe($profile->id)
        ->and($attribution->affiliate_click_id)->toBe($click?->id)
        ->and($attribution->attribution_source)->toBe('session')
        ->and($commission)->not->toBeNull()
        ->and($commission->status)->toBe(AffiliateCommission::STATUS_PENDING)
        ->and((float) $commission->commission_amount)->toBe(50.0);
});

it('attributes checkout orders from the referral cookie when no session referral exists', function () {
    $affiliate = Customer::factory()->create();
    $buyer = Customer::factory()->create();
    $profile = activeAffiliateProfileFor($affiliate);
    $click = AffiliateClick::query()->create([
        'affiliate_profile_id' => $profile->id,
        'referral_code' => $profile->referral_code,
        'clicked_at' => now(),
    ]);

    actingAs($buyer, 'customer');

    withCookie(config('commerce_affiliate.cookie_name'), json_encode([
        'code' => $profile->referral_code,
        'click_id' => $click->id,
        'captured_at' => now()->toIso8601String(),
    ], JSON_THROW_ON_ERROR));

    get(route('shop.home.index'))->assertOk()
        ->assertSessionMissing(AffiliateReferralTrackingService::SESSION_KEY);

    $order = Order::factory()->create([
        'customer_id' => $buyer->id,
        'base_sub_total' => 500,
        'base_grand_total' => 500,
    ]);

    app(AttributeAffiliateOrder::class)->handle($order);

    $attribution = AffiliateOrderAttribution::query()->where('order_id', $order->id)->first();

    expect($attribution)->not->toBeNull()
        ->and($attribution->affiliate_profile_id)->toBe($profile->id)
        ->and($attribution->affiliate_click_id)->toBe($click->id)
        ->and($attribution->attribution_source)->toBe('cookie')
        ->and(AffiliateCommission::query()->where('order_id', $order->id)->exists())->toBeTrue();
});

it('does not capture or attribute self referrals', function () {
    $affiliate = Customer::factory()->create();
    $profile = activeAffiliateProfileFor($affiliate);

    actingAs($affiliate, 'customer');

    get(route('shop.home.index', [
        'ref' => $profile->referral_code,
    ]))->assertOk()
        ->assertSessionMissing(AffiliateReferralTrackingService::SESSION_KEY);

    $order = Order::factory()->create([
        'customer_id' => $affiliate->id,
    ]);

    app(AttributeAffiliateOrder::class)->handle($order);

    expect(AffiliateClick::query()->where('affiliate_profile_id', $profile->id)->exists())->toBeFalse()
        ->and(AffiliateOrderAttribution::query()->where('order_id', $order->id)->exists())->toBeFalse()
        ->and(AffiliateCommission::query()->where('order_id', $order->id)->exists())->toBeFalse();
});

it('reverses affiliate commission and cancels attribution when an attributed order is canceled', function () {
    $affiliate = Customer::factory()->create();
    $buyer = Customer::factory()->create();
    $profile = activeAffiliateProfileFor($affiliate);

    actingAs($buyer, 'customer');

    get(route('shop.home.index', [
        'ref' => $profile->referral_code,
    ]))->assertOk();

    $order = Order::factory()->create([
        'customer_id' => $buyer->id,
        'base_sub_total' => 1000,
        'base_grand_total' => 1000,
    ]);

    app(AttributeAffiliateOrder::class)->handle($order);
    app(ReverseAffiliateCommissionForCanceledOrder::class)->handle($order);

    $attribution = AffiliateOrderAttribution::query()->where('order_id', $order->id)->first();
    $commission = AffiliateCommission::query()->where('order_id', $order->id)->first();

    expect($attribution->status)->toBe(AffiliateOrderAttribution::STATUS_CANCELED)
        ->and($commission->status)->toBe(AffiliateCommission::STATUS_REVERSED)
        ->and($commission->reversal_reason)->toBe('Order canceled.');
});

function activeAffiliateProfileFor(Customer $customer): AffiliateProfile
{
    $service = app(AffiliateProfileService::class);

    return $service->approve($service->apply($customer, [
        'application_note' => 'Approved affiliate.',
        'terms_accepted' => true,
    ]));
}
