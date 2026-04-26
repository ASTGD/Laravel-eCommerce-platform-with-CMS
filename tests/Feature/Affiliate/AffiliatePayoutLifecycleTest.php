<?php

use Illuminate\Validation\ValidationException;
use Platform\CommerceCore\Models\AffiliateCommission;
use Platform\CommerceCore\Models\AffiliatePayout;
use Platform\CommerceCore\Models\AffiliatePayoutCommissionAllocation;
use Platform\CommerceCore\Models\AffiliateProfile;
use Platform\CommerceCore\Services\Affiliates\AffiliatePayoutService;
use Platform\CommerceCore\Services\Affiliates\AffiliateProfileService;
use Tests\TestCase;
use Webkul\Customer\Models\Customer;
use Webkul\Sales\Models\Order;

uses(TestCase::class);

it('reserves exact approved commission amounts for requested payouts', function () {
    config()->set('commerce_affiliate.minimum_payout_amount', 10);

    $profile = activeAffiliatePayoutLifecycleProfile();
    $firstCommission = createPayoutLifecycleCommission($profile, 100);
    $secondCommission = createPayoutLifecycleCommission($profile, 50);

    $payout = app(AffiliatePayoutService::class)->requestPayout($profile, 120, [
        'payout_method' => 'bank_transfer',
    ]);

    $allocations = $payout->allocations()->oldest('id')->get();
    $balance = app(AffiliatePayoutService::class)->balanceFor($profile);

    expect($allocations)->toHaveCount(2)
        ->and($allocations[0]->affiliate_commission_id)->toBe($firstCommission->id)
        ->and((float) $allocations[0]->amount)->toBe(100.0)
        ->and($allocations[0]->status)->toBe(AffiliatePayoutCommissionAllocation::STATUS_RESERVED)
        ->and($allocations[1]->affiliate_commission_id)->toBe($secondCommission->id)
        ->and((float) $allocations[1]->amount)->toBe(20.0)
        ->and($balance['reserved_payouts'])->toBe(120.0)
        ->and($balance['available_balance'])->toBe(30.0);
});

it('marks allocated commissions paid when a payout is completed', function () {
    config()->set('commerce_affiliate.minimum_payout_amount', 10);

    $profile = activeAffiliatePayoutLifecycleProfile();
    $firstCommission = createPayoutLifecycleCommission($profile, 100);
    $secondCommission = createPayoutLifecycleCommission($profile, 50);
    $payout = app(AffiliatePayoutService::class)->requestPayout($profile, 120);

    app(AffiliatePayoutService::class)->markPaid($payout, reference: 'BANK-PAID-3001');

    $balance = app(AffiliatePayoutService::class)->balanceFor($profile);

    expect($payout->refresh()->status)->toBe(AffiliatePayout::STATUS_PAID)
        ->and($payout->payout_reference)->toBe('BANK-PAID-3001')
        ->and($payout->allocations()->where('status', AffiliatePayoutCommissionAllocation::STATUS_PAID)->sum('amount'))->toBe('120.0000')
        ->and($firstCommission->refresh()->status)->toBe(AffiliateCommission::STATUS_PAID)
        ->and($secondCommission->refresh()->status)->toBe(AffiliateCommission::STATUS_APPROVED)
        ->and($balance['paid_commissions'])->toBe(120.0)
        ->and($balance['reserved_payouts'])->toBe(0.0)
        ->and($balance['available_balance'])->toBe(30.0);
});

it('releases reserved commission allocations when a payout is rejected', function () {
    config()->set('commerce_affiliate.minimum_payout_amount', 10);

    $profile = activeAffiliatePayoutLifecycleProfile();
    createPayoutLifecycleCommission($profile, 100);
    $payout = app(AffiliatePayoutService::class)->requestPayout($profile, 80);

    app(AffiliatePayoutService::class)->reject($payout, reason: 'Incorrect payout account.');

    $balance = app(AffiliatePayoutService::class)->balanceFor($profile);

    expect($payout->refresh()->status)->toBe(AffiliatePayout::STATUS_REJECTED)
        ->and($payout->allocations()->where('status', AffiliatePayoutCommissionAllocation::STATUS_RELEASED)->sum('amount'))->toBe('80.0000')
        ->and($balance['reserved_payouts'])->toBe(0.0)
        ->and($balance['available_balance'])->toBe(100.0);
});

it('does not allow multiple payout requests to over-reserve approved commissions', function () {
    config()->set('commerce_affiliate.minimum_payout_amount', 10);

    $profile = activeAffiliatePayoutLifecycleProfile();
    createPayoutLifecycleCommission($profile, 100);

    app(AffiliatePayoutService::class)->requestPayout($profile, 60);

    expect(fn () => app(AffiliatePayoutService::class)->requestPayout($profile, 50))
        ->toThrow(ValidationException::class);

    $balance = app(AffiliatePayoutService::class)->balanceFor($profile);

    expect($balance['reserved_payouts'])->toBe(60.0)
        ->and($balance['available_balance'])->toBe(40.0);
});

function activeAffiliatePayoutLifecycleProfile(): AffiliateProfile
{
    $customer = Customer::factory()->create();
    $profileService = app(AffiliateProfileService::class);

    return $profileService->approve($profileService->apply($customer, [
        'application_note' => 'Payout lifecycle affiliate.',
        'terms_accepted' => true,
    ]));
}

function createPayoutLifecycleCommission(AffiliateProfile $profile, float $amount): AffiliateCommission
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
