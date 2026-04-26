<?php

namespace Platform\CommerceCore\Services\Affiliates;

use Illuminate\Support\Arr;
use Platform\CommerceCore\Models\AffiliateSetting;

class AffiliateSettingsService
{
    public function all(): array
    {
        $settings = $this->defaults();

        AffiliateSetting::query()
            ->whereIn('key', array_keys($settings))
            ->get()
            ->each(function (AffiliateSetting $setting) use (&$settings): void {
                $settings[$setting->key] = $setting->value;
            });

        $settings['approval_required'] = (bool) $settings['approval_required'];
        $settings['cookie_window_days'] = max((int) $settings['cookie_window_days'], 1);
        $settings['minimum_payout_amount'] = max((float) $settings['minimum_payout_amount'], 0);
        $settings['payout_methods'] = $this->normalizedPayoutMethods($settings['payout_methods'] ?? []);
        $settings['default_commission'] = [
            'type' => in_array(Arr::get($settings, 'default_commission.type'), ['percentage', 'fixed'], true)
                ? Arr::get($settings, 'default_commission.type')
                : 'percentage',
            'value' => max((float) Arr::get($settings, 'default_commission.value', 10), 0),
        ];

        return $settings;
    }

    public function update(array $payload): array
    {
        $settings = [
            'approval_required' => (bool) ($payload['approval_required'] ?? true),
            'default_commission' => [
                'type' => $payload['default_commission_type'] ?? 'percentage',
                'value' => (float) ($payload['default_commission_value'] ?? 10),
            ],
            'cookie_window_days' => (int) ($payload['cookie_window_days'] ?? 30),
            'minimum_payout_amount' => (float) ($payload['minimum_payout_amount'] ?? 50),
            'payout_methods' => $this->normalizedPayoutMethods($payload['payout_methods'] ?? []),
            'terms_text' => $payload['terms_text'] ?? '',
        ];

        foreach ($settings as $key => $value) {
            AffiliateSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }

        return $this->all();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->all(), $key, $default);
    }

    public function approvalRequired(): bool
    {
        return (bool) $this->get('approval_required', true);
    }

    public function defaultCommission(): array
    {
        return (array) $this->get('default_commission', [
            'type' => 'percentage',
            'value' => 10.0,
        ]);
    }

    public function cookieWindowDays(): int
    {
        return max((int) $this->get('cookie_window_days', 30), 1);
    }

    public function minimumPayoutAmount(): float
    {
        return max((float) $this->get('minimum_payout_amount', 50), 0);
    }

    public function payoutMethods(): array
    {
        return $this->normalizedPayoutMethods($this->get('payout_methods', []));
    }

    public function termsText(): string
    {
        return (string) $this->get('terms_text', '');
    }

    public function referralParameter(): string
    {
        return (string) config('commerce_affiliate.referral_parameter', 'ref');
    }

    public function cookieName(): string
    {
        return (string) config('commerce_affiliate.cookie_name', 'platform_affiliate_referral');
    }

    public function selfReferralPreventionEnabled(): bool
    {
        return (bool) config('commerce_affiliate.self_referral_prevention', true);
    }

    public function payoutMethodsText(?array $methods = null): string
    {
        $methods ??= $this->payoutMethods();

        return collect($methods)
            ->map(fn (string $label, string $code): string => "{$code}={$label}")
            ->implode("\n");
    }

    protected function defaults(): array
    {
        return [
            'approval_required' => (bool) config('commerce_affiliate.approval_required', true),
            'default_commission' => (array) config('commerce_affiliate.default_commission', [
                'type' => 'percentage',
                'value' => 10.0,
            ]),
            'cookie_window_days' => (int) config('commerce_affiliate.cookie_window_days', 30),
            'minimum_payout_amount' => (float) config('commerce_affiliate.minimum_payout_amount', 50.0),
            'payout_methods' => (array) config('commerce_affiliate.payout_methods', []),
            'terms_text' => (string) config('commerce_affiliate.terms_text', ''),
        ];
    }

    protected function normalizedPayoutMethods(mixed $methods): array
    {
        if (! is_array($methods)) {
            return [];
        }

        return collect($methods)
            ->mapWithKeys(function (mixed $label, mixed $code): array {
                $code = str($code)->trim()->snake()->value();
                $label = trim((string) $label);

                return $code !== '' && $label !== ''
                    ? [$code => $label]
                    : [];
            })
            ->all();
    }
}
