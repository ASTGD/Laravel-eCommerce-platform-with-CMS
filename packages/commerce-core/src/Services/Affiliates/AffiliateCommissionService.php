<?php

namespace Platform\CommerceCore\Services\Affiliates;

use Platform\CommerceCore\Models\AffiliateCommission;
use Platform\CommerceCore\Models\AffiliateOrderAttribution;
use Platform\CommerceCore\Models\ShipmentRecord;
use Webkul\Sales\Models\Order;

class AffiliateCommissionService
{
    public function __construct(
        protected AffiliateSettingsService $affiliateSettingsService,
        protected AffiliatePayoutService $affiliatePayoutService,
    ) {}

    public function createForOrder(Order $order, ?AffiliateOrderAttribution $attribution = null): ?AffiliateCommission
    {
        $attribution ??= AffiliateOrderAttribution::query()
            ->where('order_id', $order->id)
            ->where('status', AffiliateOrderAttribution::STATUS_ATTRIBUTED)
            ->first();

        if (! $attribution || $attribution->status !== AffiliateOrderAttribution::STATUS_ATTRIBUTED) {
            return null;
        }

        $existing = AffiliateCommission::query()->where('order_id', $order->id)->first();

        if ($existing) {
            return $existing;
        }

        $orderAmount = $this->commissionableOrderAmount($order);
        $commissionRule = $this->affiliateSettingsService->defaultCommission();
        $type = (string) ($commissionRule['type'] ?? 'percentage');
        $rate = (float) ($commissionRule['value'] ?? 10);

        $commission = AffiliateCommission::query()->create([
            'affiliate_profile_id' => $attribution->affiliate_profile_id,
            'affiliate_order_attribution_id' => $attribution->id,
            'order_id' => $order->id,
            'status' => AffiliateCommission::STATUS_PENDING,
            'commission_type' => $type,
            'commission_rate' => $rate,
            'order_amount' => $orderAmount,
            'commission_amount' => $this->calculateCommissionAmount($orderAmount, $type, $rate),
            'currency' => $order->base_currency_code ?: $order->order_currency_code,
        ]);

        $this->handleOrderEligibilityForCommission($order);

        return $commission->refresh();
    }

    public function approve(AffiliateCommission $commission): AffiliateCommission
    {
        if ($commission->status !== AffiliateCommission::STATUS_PENDING) {
            return $commission->refresh();
        }

        $commission->fill([
            'status' => AffiliateCommission::STATUS_APPROVED,
            'eligible_at' => now(),
            'approved_at' => now(),
            'reversed_at' => null,
            'reversal_reason' => null,
        ])->save();

        return $commission->refresh();
    }

    public function approveForOrder(Order $order): ?AffiliateCommission
    {
        $commission = AffiliateCommission::query()->where('order_id', $order->id)->first();

        return $commission ? $this->approve($commission) : null;
    }

    public function reverseForOrder(Order $order, ?string $reason = null): ?AffiliateCommission
    {
        $commission = AffiliateCommission::query()->where('order_id', $order->id)->first();

        if (! $commission) {
            return null;
        }

        return $this->reverse($commission, $reason);
    }

    public function reverse(AffiliateCommission $commission, ?string $reason = null): AffiliateCommission
    {
        if ($commission->status === AffiliateCommission::STATUS_REVERSED) {
            return $commission->refresh();
        }

        $commission->fill([
            'status' => AffiliateCommission::STATUS_REVERSED,
            'reversed_at' => now(),
            'reversal_reason' => $reason,
        ])->save();

        $this->affiliatePayoutService->releaseReservedAllocationsForCommission($commission, $reason);

        return $commission->refresh();
    }

    public function handleOrderEligibilityForCommission(Order $order): ?AffiliateCommission
    {
        if (! $this->affiliateSettingsService->usesAutomaticCommissionApproval()) {
            return null;
        }

        if (! $this->orderIsEligibleForAutomaticApproval($order)) {
            return null;
        }

        $commission = AffiliateCommission::query()
            ->where('order_id', $order->id)
            ->where('status', AffiliateCommission::STATUS_PENDING)
            ->first();

        return $commission ? $this->approve($commission) : null;
    }

    public function calculateCommissionAmount(float $orderAmount, string $type, float $rate): float
    {
        $amount = match ($type) {
            'fixed' => $rate,
            default => $orderAmount * ($rate / 100),
        };

        return round(max($amount, 0), 4);
    }

    protected function commissionableOrderAmount(Order $order): float
    {
        return (float) ($order->base_sub_total ?: $order->base_grand_total ?: $order->grand_total ?: 0);
    }

    protected function orderIsEligibleForAutomaticApproval(Order $order): bool
    {
        if ($order->status === Order::STATUS_COMPLETED) {
            return true;
        }

        return ShipmentRecord::query()
            ->where('order_id', $order->id)
            ->where('status', ShipmentRecord::STATUS_DELIVERED)
            ->exists();
    }
}
