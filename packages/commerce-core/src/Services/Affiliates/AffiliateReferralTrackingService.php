<?php

namespace Platform\CommerceCore\Services\Affiliates;

use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cookie;
use Platform\CommerceCore\Models\AffiliateClick;
use Platform\CommerceCore\Models\AffiliateOrderAttribution;
use Webkul\Sales\Models\Order;

class AffiliateReferralTrackingService
{
    public const SESSION_KEY = 'commerce_affiliate.referral';

    public function __construct(
        protected ReferralAttributionService $referralAttributionService,
        protected AffiliateCommissionService $affiliateCommissionService,
        protected AffiliateSettingsService $affiliateSettingsService,
    ) {}

    public function captureFromRequest(Request $request): ?AffiliateClick
    {
        $referralCode = trim((string) $request->query($this->referralParameter(), ''));

        if ($referralCode === '') {
            return null;
        }

        $click = $this->referralAttributionService->recordClickFromRequest($referralCode, $request);

        if (! $click) {
            return null;
        }

        $payload = [
            'code' => $click->referral_code,
            'click_id' => $click->id,
            'captured_at' => now()->toIso8601String(),
        ];

        if ($request->hasSession()) {
            $request->session()->put(self::SESSION_KEY, $payload);
        }

        Cookie::queue(cookie(
            name: $this->cookieName(),
            value: json_encode($payload, JSON_THROW_ON_ERROR),
            minutes: $this->cookieWindowMinutes(),
            httpOnly: true,
            sameSite: 'lax',
        ));

        return $click;
    }

    public function attributeOrderFromRequest(Order $order, Request $request): ?AffiliateOrderAttribution
    {
        $payload = $this->currentReferralPayload($request);

        if (! $payload) {
            return null;
        }

        $click = $this->clickFromPayload($payload);

        $attribution = $this->referralAttributionService->attributeOrder(
            order: $order,
            affiliate: (string) Arr::get($payload, 'code'),
            click: $click,
            source: $payload['source'] ?? 'cookie',
        );

        if ($attribution) {
            $this->affiliateCommissionService->createForOrder($order, $attribution);
        }

        return $attribution;
    }

    public function reverseOrder(Order $order, ?string $reason = null): void
    {
        $this->affiliateCommissionService->reverseForOrder($order, $reason);
        $this->referralAttributionService->cancelAttributionForOrder($order, $reason);
    }

    public function currentReferralPayload(Request $request): ?array
    {
        $sessionPayload = $request->hasSession()
            ? $request->session()->get(self::SESSION_KEY)
            : null;

        if ($this->payloadIsUsable($sessionPayload)) {
            $sessionPayload['source'] = 'session';

            return $sessionPayload;
        }

        $cookiePayload = $this->decodeCookiePayload($request->cookie($this->cookieName()));

        if ($this->payloadIsUsable($cookiePayload)) {
            $cookiePayload['source'] = 'cookie';

            return $cookiePayload;
        }

        return null;
    }

    protected function clickFromPayload(array $payload): ?AffiliateClick
    {
        $clickId = Arr::get($payload, 'click_id');

        if (! $clickId) {
            return null;
        }

        return AffiliateClick::query()
            ->whereKey($clickId)
            ->where('referral_code', Arr::get($payload, 'code'))
            ->first();
    }

    protected function decodeCookiePayload(mixed $cookieValue): ?array
    {
        if (! is_string($cookieValue) || $cookieValue === '') {
            return null;
        }

        try {
            $payload = json_decode($cookieValue, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_array($payload) ? $payload : null;
    }

    protected function payloadIsUsable(mixed $payload): bool
    {
        if (! is_array($payload) || blank(Arr::get($payload, 'code'))) {
            return false;
        }

        $capturedAt = Arr::get($payload, 'captured_at');

        if (! $capturedAt) {
            return false;
        }

        try {
            $capturedAt = now()->parse($capturedAt);
        } catch (\Throwable) {
            return false;
        }

        return $capturedAt instanceof CarbonInterface
            && $capturedAt->greaterThanOrEqualTo(now()->subDays($this->cookieWindowDays()));
    }

    protected function referralParameter(): string
    {
        return $this->affiliateSettingsService->referralParameter();
    }

    protected function cookieName(): string
    {
        return $this->affiliateSettingsService->cookieName();
    }

    protected function cookieWindowDays(): int
    {
        return $this->affiliateSettingsService->cookieWindowDays();
    }

    protected function cookieWindowMinutes(): int
    {
        return $this->cookieWindowDays() * 24 * 60;
    }
}
