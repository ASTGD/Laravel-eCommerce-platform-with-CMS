<?php

use Illuminate\Validation\ValidationException;
use Platform\CommerceCore\Models\AffiliateCommission;
use Platform\CommerceCore\Models\AffiliatePayout;
use Platform\CommerceCore\Models\AffiliatePayoutCommissionAllocation;
use Platform\CommerceCore\Models\AffiliateProfile;
use Platform\CommerceCore\Services\Affiliates\AffiliateCommissionService;
use Platform\CommerceCore\Services\Affiliates\AffiliatePayoutService;
use Platform\CommerceCore\Services\Affiliates\AffiliatePortalService;
use Platform\CommerceCore\Services\Affiliates\AffiliateProfileDashboardService;
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

it('reduces available balance and net earned when an unpaid commission is reversed', function () {
    $profile = activeAffiliatePayoutLifecycleProfile();
    createPayoutLifecycleCommission($profile, 80);
    $reversedCommission = createPayoutLifecycleCommission($profile, 30);

    app(AffiliateCommissionService::class)->reverseForOrder($reversedCommission->order, 'Order refunded.');

    $balance = app(AffiliatePayoutService::class)->balanceFor($profile);

    expect($balance['gross_earned'])->toBe(110.0)
        ->and($balance['reversed_commissions'])->toBe(30.0)
        ->and($balance['net_earned'])->toBe(80.0)
        ->and($balance['available_balance'])->toBe(80.0)
        ->and($reversedCommission->refresh()->status)->toBe(AffiliateCommission::STATUS_REVERSED);
});

it('adjusts open reserved payouts when a reserved commission is reversed', function () {
    config()->set('commerce_affiliate.minimum_payout_amount', 1);

    $profile = activeAffiliatePayoutLifecycleProfile();
    $reversedCommission = createPayoutLifecycleCommission($profile, 30);
    createPayoutLifecycleCommission($profile, 80);
    $payout = app(AffiliatePayoutService::class)->requestPayout($profile, 40);

    app(AffiliateCommissionService::class)->reverseForOrder($reversedCommission->order, 'Order refunded.');

    $payout = $payout->refresh();
    $balance = app(AffiliatePayoutService::class)->balanceFor($profile);

    expect($payout->status)->toBe(AffiliatePayout::STATUS_REQUESTED)
        ->and((float) $payout->amount)->toBe(10.0)
        ->and($payout->meta['adjustment_required'])->toBeTrue()
        ->and($payout->allocations()->where('affiliate_commission_id', $reversedCommission->id)->where('status', AffiliatePayoutCommissionAllocation::STATUS_RELEASED)->sum('amount'))->toBe('30.0000')
        ->and($balance['reserved_payouts'])->toBe(10.0)
        ->and($balance['available_balance'])->toBe(70.0);

    app(AffiliatePayoutService::class)->markPaid($payout);

    expect($payout->refresh()->amount)->toBe('10.0000')
        ->and($payout->status)->toBe(AffiliatePayout::STATUS_PAID)
        ->and(app(AffiliatePayoutService::class)->balanceFor($profile)['paid_payouts'])->toBe(10.0);
});

it('rejects an open payout when all reserved commissions are reversed', function () {
    config()->set('commerce_affiliate.minimum_payout_amount', 1);

    $profile = activeAffiliatePayoutLifecycleProfile();
    $commission = createPayoutLifecycleCommission($profile, 30);
    $payout = app(AffiliatePayoutService::class)->requestPayout($profile, 30);

    app(AffiliateCommissionService::class)->reverseForOrder($commission->order, 'Order refunded.');

    $balance = app(AffiliatePayoutService::class)->balanceFor($profile);

    expect($payout->refresh()->status)->toBe(AffiliatePayout::STATUS_REJECTED)
        ->and((float) $payout->amount)->toBe(0.0)
        ->and($balance['reserved_payouts'])->toBe(0.0)
        ->and($balance['available_balance'])->toBe(0.0);
});

it('allows already paid reversals to create a negative carry-forward balance', function () {
    config()->set('commerce_affiliate.minimum_payout_amount', 1);

    $profile = activeAffiliatePayoutLifecycleProfile();
    $paidThenReversedCommission = createPayoutLifecycleCommission($profile, 70);
    createPayoutLifecycleCommission($profile, 40);
    $payout = app(AffiliatePayoutService::class)->requestPayout($profile, 60);

    app(AffiliatePayoutService::class)->markPaid($payout);

    $beforeReversal = app(AffiliatePayoutService::class)->balanceFor($profile);

    app(AffiliateCommissionService::class)->reverseForOrder($paidThenReversedCommission->order, 'Order refunded.');

    $afterReversal = app(AffiliatePayoutService::class)->balanceFor($profile);

    expect($beforeReversal['gross_earned'])->toBe(110.0)
        ->and($beforeReversal['net_earned'])->toBe(110.0)
        ->and($beforeReversal['paid_payouts'])->toBe(60.0)
        ->and($beforeReversal['available_balance'])->toBe(50.0)
        ->and($afterReversal['gross_earned'])->toBe(110.0)
        ->and($afterReversal['reversed_commissions'])->toBe(70.0)
        ->and($afterReversal['net_earned'])->toBe(40.0)
        ->and($afterReversal['paid_payouts'])->toBe(60.0)
        ->and($afterReversal['available_balance'])->toBe(-20.0);
});

it('uses future earned commissions to naturally offset negative carry-forward balance', function () {
    config()->set('commerce_affiliate.minimum_payout_amount', 1);

    $profile = activeAffiliatePayoutLifecycleProfile();
    $paidThenReversedCommission = createPayoutLifecycleCommission($profile, 70);
    createPayoutLifecycleCommission($profile, 40);
    $payout = app(AffiliatePayoutService::class)->requestPayout($profile, 60);

    app(AffiliatePayoutService::class)->markPaid($payout);
    app(AffiliateCommissionService::class)->reverseForOrder($paidThenReversedCommission->order, 'Order refunded.');

    expect(app(AffiliatePayoutService::class)->balanceFor($profile)['available_balance'])->toBe(-20.0);

    createPayoutLifecycleCommission($profile, 50);

    $balance = app(AffiliatePayoutService::class)->balanceFor($profile);

    expect($balance['net_earned'])->toBe(90.0)
        ->and($balance['available_balance'])->toBe(30.0);
});

it('keeps admin and customer portal balances consistent after commission reversal', function () {
    config()->set('commerce_affiliate.minimum_payout_amount', 1);

    $profile = activeAffiliatePayoutLifecycleProfile();
    $commission = createPayoutLifecycleCommission($profile, 70);
    createPayoutLifecycleCommission($profile, 40);
    $payout = app(AffiliatePayoutService::class)->requestPayout($profile, 60);

    app(AffiliatePayoutService::class)->markPaid($payout);
    app(AffiliateCommissionService::class)->reverseForOrder($commission->order, 'Order refunded.');

    $adminBalance = app(AffiliateProfileDashboardService::class)->build($profile)['balance'];
    $customerBalance = app(AffiliatePortalService::class)->dashboardFor($profile)['balance'];

    expect($adminBalance['available_balance'])->toBe(-20.0)
        ->and($customerBalance['available_balance'])->toBe(-20.0)
        ->and($adminBalance['net_earned'])->toBe($customerBalance['net_earned']);
});

it('blocks payout requests while reversal carry-forward balance is negative', function () {
    config()->set('commerce_affiliate.minimum_payout_amount', 1);

    $profile = activeAffiliatePayoutLifecycleProfile();
    $commission = createPayoutLifecycleCommission($profile, 70);
    createPayoutLifecycleCommission($profile, 40);
    $payout = app(AffiliatePayoutService::class)->requestPayout($profile, 60);

    app(AffiliatePayoutService::class)->markPaid($payout);
    app(AffiliateCommissionService::class)->reverseForOrder($commission->order, 'Order refunded.');

    expect(app(AffiliatePayoutService::class)->balanceFor($profile)['available_balance'])->toBe(-20.0);

    expect(fn () => app(AffiliatePayoutService::class)->requestPayout($profile, 1))
        ->toThrow(ValidationException::class);
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
