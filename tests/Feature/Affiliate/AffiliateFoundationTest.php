<?php

use Platform\CommerceCore\Models\AffiliateCommission;
use Platform\CommerceCore\Models\AffiliatePayout;
use Platform\CommerceCore\Models\AffiliateProfile;
use Platform\CommerceCore\Models\AffiliateSetting;
use Platform\CommerceCore\Models\ShipmentRecord;
use Platform\CommerceCore\Services\Affiliates\AffiliateCommissionService;
use Platform\CommerceCore\Services\Affiliates\AffiliatePayoutService;
use Platform\CommerceCore\Services\Affiliates\AffiliateProfileService;
use Platform\CommerceCore\Services\Affiliates\ReferralAttributionService;
use Platform\CommerceCore\Services\ShipmentRecordService;
use Tests\TestCase;
use Webkul\Customer\Models\Customer;
use Webkul\Sales\Models\Order;

uses(TestCase::class);

it('creates one pending affiliate profile for a customer application', function () {
    config()->set('commerce_affiliate.approval_required', true);

    $customer = Customer::factory()->create();
    $service = app(AffiliateProfileService::class);

    $profile = $service->apply($customer, [
        'application_note' => 'I will promote the store on my site.',
        'website_url' => 'https://example.test',
        'terms_accepted' => true,
    ]);

    expect($profile->status)->toBe(AffiliateProfile::STATUS_PENDING)
        ->and($profile->referral_code)->toStartWith('AFF')
        ->and($profile->terms_accepted_at)->not->toBeNull()
        ->and($service->portalState($customer))->toBe(AffiliateProfileService::PORTAL_STATE_PENDING)
        ->and($service->canAccessPortal($customer))->toBeFalse();

    $approved = $service->approve($profile);

    expect($approved->status)->toBe(AffiliateProfile::STATUS_ACTIVE)
        ->and($approved->approved_at)->not->toBeNull()
        ->and($service->portalState($customer))->toBe(AffiliateProfileService::PORTAL_STATE_ACTIVE)
        ->and($service->canAccessPortal($customer))->toBeTrue()
        ->and(AffiliateProfile::query()->where('customer_id', $customer->id)->count())->toBe(1);
});

it('tracks a referral click, attributes an order, creates commission, and derives payout balance', function () {
    config()->set('commerce_affiliate.default_commission', [
        'type' => 'percentage',
        'value' => 10,
    ]);
    config()->set('commerce_affiliate.minimum_payout_amount', 1);

    $affiliateCustomer = Customer::factory()->create();
    $buyer = Customer::factory()->create();

    $profileService = app(AffiliateProfileService::class);
    $profile = $profileService->approve($profileService->apply($affiliateCustomer, [
        'terms_accepted' => true,
    ]));

    $attributionService = app(ReferralAttributionService::class);
    $click = $attributionService->recordClick($profile->referral_code, [
        'customer_id' => $buyer->id,
        'session_id' => 'test-session',
        'landing_url' => 'https://store.test/?ref='.$profile->referral_code,
    ]);

    $order = Order::factory()->create([
        'customer_id' => $buyer->id,
        'base_sub_total' => 1000,
        'base_grand_total' => 1100,
        'grand_total' => 1100,
        'base_currency_code' => 'USD',
        'order_currency_code' => 'USD',
    ]);

    $attribution = $attributionService->attributeOrder($order, $profile, $click);
    $commission = app(AffiliateCommissionService::class)->createForOrder($order, $attribution);
    $commission = app(AffiliateCommissionService::class)->approve($commission);

    $payoutService = app(AffiliatePayoutService::class);

    expect($click)->not->toBeNull()
        ->and($attribution->order_id)->toBe($order->id)
        ->and($commission->status)->toBe(AffiliateCommission::STATUS_APPROVED)
        ->and((float) $commission->commission_amount)->toBe(100.0)
        ->and($payoutService->balanceFor($profile)['available_balance'])->toBe(100.0);

    $payout = $payoutService->requestPayout($profile, 40, [
        'currency' => 'USD',
        'payout_method' => 'bank_transfer',
    ]);

    expect($payout->status)->toBe(AffiliatePayout::STATUS_REQUESTED)
        ->and($payoutService->balanceFor($profile)['reserved_payouts'])->toBe(40.0)
        ->and($payoutService->balanceFor($profile)['available_balance'])->toBe(60.0);

    $payoutService->markPaid($payout);

    expect($payout->refresh()->status)->toBe(AffiliatePayout::STATUS_PAID)
        ->and($payoutService->balanceFor($profile)['paid_payouts'])->toBe(40.0)
        ->and($payoutService->balanceFor($profile)['available_balance'])->toBe(60.0);
});

it('prevents self referrals from creating clicks or order attribution', function () {
    $customer = Customer::factory()->create();
    $profileService = app(AffiliateProfileService::class);
    $profile = $profileService->approve($profileService->apply($customer, [
        'terms_accepted' => true,
    ]));

    $attributionService = app(ReferralAttributionService::class);
    $click = $attributionService->recordClick($profile->referral_code, [
        'customer_id' => $customer->id,
    ]);

    $order = Order::factory()->create([
        'customer_id' => $customer->id,
    ]);

    expect($click)->toBeNull()
        ->and($attributionService->attributeOrder($order, $profile))->toBeNull();
});

it('keeps commissions pending in manual approval mode until admin approval', function () {
    config()->set('commerce_affiliate.commission_approval_mode', 'manual');

    $profile = activeAffiliateFoundationProfile();
    $order = Order::factory()->create([
        'customer_id' => Customer::factory()->create()->id,
        'status' => Order::STATUS_COMPLETED,
        'base_sub_total' => 1000,
        'base_grand_total' => 1000,
        'base_currency_code' => 'USD',
        'order_currency_code' => 'USD',
    ]);
    $attribution = app(ReferralAttributionService::class)->attributeOrder($order, $profile);
    $commission = app(AffiliateCommissionService::class)->createForOrder($order, $attribution);

    expect($commission->status)->toBe(AffiliateCommission::STATUS_PENDING)
        ->and(app(AffiliatePayoutService::class)->balanceFor($profile)['available_balance'])->toBe(0.0);
});

it('automatically approves commissions when an order reaches completed status in automatic mode', function () {
    config()->set('commerce_affiliate.commission_approval_mode', 'automatic');

    $profile = activeAffiliateFoundationProfile();
    $order = Order::factory()->create([
        'customer_id' => Customer::factory()->create()->id,
        'status' => Order::STATUS_PROCESSING,
        'base_sub_total' => 1000,
        'base_grand_total' => 1000,
        'base_currency_code' => 'USD',
        'order_currency_code' => 'USD',
    ]);
    $attribution = app(ReferralAttributionService::class)->attributeOrder($order, $profile);
    $commission = app(AffiliateCommissionService::class)->createForOrder($order, $attribution);

    expect($commission->status)->toBe(AffiliateCommission::STATUS_PENDING)
        ->and(app(AffiliatePayoutService::class)->balanceFor($profile)['available_balance'])->toBe(0.0);

    $order->forceFill(['status' => Order::STATUS_COMPLETED])->save();
    app(AffiliateCommissionService::class)->handleOrderEligibilityForCommission($order);

    expect($commission->refresh()->status)->toBe(AffiliateCommission::STATUS_APPROVED)
        ->and(app(AffiliatePayoutService::class)->balanceFor($profile)['available_balance'])->toBe(100.0);

    app(AffiliateCommissionService::class)->reverseForOrder($order, 'Order refunded.');

    expect($commission->refresh()->status)->toBe(AffiliateCommission::STATUS_REVERSED)
        ->and(app(AffiliatePayoutService::class)->balanceFor($profile)['available_balance'])->toBe(0.0);
});

it('automatically approves commissions when a shipment is marked delivered in automatic mode', function () {
    config()->set('commerce_affiliate.commission_approval_mode', 'automatic');

    $profile = activeAffiliateFoundationProfile();
    $order = Order::factory()->create([
        'customer_id' => Customer::factory()->create()->id,
        'status' => Order::STATUS_PROCESSING,
        'base_sub_total' => 1000,
        'base_grand_total' => 1000,
        'base_currency_code' => 'USD',
        'order_currency_code' => 'USD',
    ]);
    $attribution = app(ReferralAttributionService::class)->attributeOrder($order, $profile);
    $commission = app(AffiliateCommissionService::class)->createForOrder($order, $attribution);
    $shipmentRecord = ShipmentRecord::query()->create([
        'order_id' => $order->id,
        'status' => ShipmentRecord::STATUS_IN_TRANSIT,
        'carrier_name_snapshot' => 'Manual Courier',
        'tracking_number' => 'AFF-AUTO-DELIVERED',
        'cod_amount_expected' => 0,
        'carrier_fee_amount' => 0,
        'cod_fee_amount' => 0,
        'return_fee_amount' => 0,
        'net_remittable_amount' => 0,
        'handed_over_at' => now()->subDay(),
    ]);

    app(ShipmentRecordService::class)->updateStatus($shipmentRecord, ShipmentRecord::STATUS_DELIVERED);

    expect($commission->refresh()->status)->toBe(AffiliateCommission::STATUS_APPROVED)
        ->and(app(AffiliatePayoutService::class)->balanceFor($profile)['available_balance'])->toBe(100.0);
});

function activeAffiliateFoundationProfile(): AffiliateProfile
{
    AffiliateSetting::query()->delete();

    return app(AffiliateProfileService::class)->approve(
        app(AffiliateProfileService::class)->apply(Customer::factory()->create(), [
            'application_note' => 'Commission approval workflow affiliate.',
            'terms_accepted' => true,
        ]),
    );
}
