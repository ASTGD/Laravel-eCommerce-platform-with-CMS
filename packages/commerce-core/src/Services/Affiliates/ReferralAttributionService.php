<?php

namespace Platform\CommerceCore\Services\Affiliates;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Platform\CommerceCore\Models\AffiliateClick;
use Platform\CommerceCore\Models\AffiliateOrderAttribution;
use Platform\CommerceCore\Models\AffiliateProfile;
use Webkul\Sales\Models\Order;

class ReferralAttributionService
{
    public function __construct(protected AffiliateSettingsService $affiliateSettingsService) {}

    public function findActiveProfileByCode(?string $referralCode): ?AffiliateProfile
    {
        if (blank($referralCode)) {
            return null;
        }

        return AffiliateProfile::query()
            ->active()
            ->where('referral_code', trim((string) $referralCode))
            ->first();
    }

    public function recordClick(string $referralCode, array $data = []): ?AffiliateClick
    {
        $profile = $this->findActiveProfileByCode($referralCode);

        if (! $profile) {
            return null;
        }

        $customerId = Arr::get($data, 'customer_id');

        if ($this->isSelfReferral($profile, $customerId)) {
            return null;
        }

        return AffiliateClick::query()->create([
            'affiliate_profile_id' => $profile->id,
            'customer_id' => $customerId,
            'referral_code' => $profile->referral_code,
            'session_id' => Arr::get($data, 'session_id'),
            'ip_address' => Arr::get($data, 'ip_address'),
            'user_agent' => Arr::get($data, 'user_agent'),
            'landing_url' => Arr::get($data, 'landing_url'),
            'referrer_url' => Arr::get($data, 'referrer_url'),
            'clicked_at' => Arr::get($data, 'clicked_at', now()),
            'meta' => Arr::get($data, 'meta'),
        ]);
    }

    public function recordClickFromRequest(string $referralCode, Request $request): ?AffiliateClick
    {
        return $this->recordClick($referralCode, [
            'customer_id' => $request->user('customer')?->id,
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'landing_url' => $request->fullUrl(),
            'referrer_url' => $request->headers->get('referer'),
        ]);
    }

    public function attributeOrder(
        Order $order,
        AffiliateProfile|string $affiliate,
        ?AffiliateClick $click = null,
        string $source = 'cookie',
        array $meta = [],
    ): ?AffiliateOrderAttribution {
        $existing = AffiliateOrderAttribution::query()->where('order_id', $order->id)->first();

        if ($existing) {
            return $existing;
        }

        $profile = $affiliate instanceof AffiliateProfile
            ? $affiliate
            : $this->findActiveProfileByCode($affiliate);

        if (! $profile || ! $profile->isActive()) {
            return null;
        }

        if ($this->isSelfReferral($profile, $order->customer_id)) {
            return null;
        }

        return AffiliateOrderAttribution::query()->create([
            'order_id' => $order->id,
            'affiliate_profile_id' => $profile->id,
            'affiliate_click_id' => $click?->id,
            'referral_code' => $profile->referral_code,
            'attribution_source' => $source,
            'status' => AffiliateOrderAttribution::STATUS_ATTRIBUTED,
            'attributed_at' => now(),
            'expires_at' => now()->addDays($this->affiliateSettingsService->cookieWindowDays()),
            'meta' => $meta ?: null,
        ]);
    }

    public function cancelAttributionForOrder(Order $order, ?string $reason = null): ?AffiliateOrderAttribution
    {
        $attribution = AffiliateOrderAttribution::query()->where('order_id', $order->id)->first();

        if (! $attribution) {
            return null;
        }

        $meta = $attribution->meta ?: [];
        $meta['canceled_reason'] = $reason;

        $attribution->fill([
            'status' => AffiliateOrderAttribution::STATUS_CANCELED,
            'meta' => $meta,
        ])->save();

        return $attribution->refresh();
    }

    protected function isSelfReferral(AffiliateProfile $profile, mixed $customerId): bool
    {
        return $this->affiliateSettingsService->selfReferralPreventionEnabled()
            && filled($customerId)
            && (int) $customerId === (int) $profile->customer_id;
    }
}
