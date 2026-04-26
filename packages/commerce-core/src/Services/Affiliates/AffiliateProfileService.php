<?php

namespace Platform\CommerceCore\Services\Affiliates;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Platform\CommerceCore\Models\AffiliateProfile;
use Webkul\Customer\Models\Customer;

class AffiliateProfileService
{
    public const PORTAL_STATE_NO_PROFILE = 'no_profile';

    public const PORTAL_STATE_PENDING = 'pending';

    public const PORTAL_STATE_ACTIVE = 'active';

    public const PORTAL_STATE_SUSPENDED = 'suspended';

    public const PORTAL_STATE_REJECTED = 'rejected';

    public function __construct(protected AffiliateSettingsService $affiliateSettingsService) {}

    public function apply(Customer $customer, array $data = []): AffiliateProfile
    {
        $profile = AffiliateProfile::query()->firstOrNew([
            'customer_id' => $customer->id,
        ]);

        if (! $profile->exists || blank($profile->referral_code)) {
            $profile->referral_code = $this->generateReferralCode($customer);
        }

        $approvalRequired = $this->affiliateSettingsService->approvalRequired();

        if (! $profile->exists || $profile->status === AffiliateProfile::STATUS_REJECTED) {
            $profile->status = $approvalRequired
                ? AffiliateProfile::STATUS_PENDING
                : AffiliateProfile::STATUS_ACTIVE;

            $profile->last_status_changed_at = now();
        }

        $profile->fill($this->applicationPayload($data));

        if (Arr::get($data, 'terms_accepted') && ! $profile->terms_accepted_at) {
            $profile->terms_accepted_at = now();
        }

        if ($profile->status === AffiliateProfile::STATUS_ACTIVE && ! $profile->approved_at) {
            $profile->approved_at = now();
        }

        $profile->save();

        return $profile->refresh();
    }

    public function approve(AffiliateProfile $profile, ?int $adminId = null): AffiliateProfile
    {
        $profile->fill([
            'status' => AffiliateProfile::STATUS_ACTIVE,
            'approved_at' => now(),
            'approved_by_admin_id' => $adminId,
            'rejected_at' => null,
            'rejected_by_admin_id' => null,
            'rejection_reason' => null,
            'suspended_at' => null,
            'suspended_by_admin_id' => null,
            'suspension_reason' => null,
            'reactivated_at' => $profile->status === AffiliateProfile::STATUS_SUSPENDED ? now() : $profile->reactivated_at,
            'last_status_changed_at' => now(),
        ]);

        if (blank($profile->referral_code)) {
            $profile->referral_code = $this->generateReferralCode($profile->customer);
        }

        $profile->save();

        return $profile->refresh();
    }

    public function createFromAdmin(Customer $customer, array $data = [], ?int $adminId = null): AffiliateProfile
    {
        if (AffiliateProfile::query()->where('customer_id', $customer->id)->exists()) {
            throw ValidationException::withMessages([
                'customer_id' => 'This customer already has an affiliate profile.',
            ]);
        }

        $status = in_array(Arr::get($data, 'status'), [AffiliateProfile::STATUS_PENDING, AffiliateProfile::STATUS_ACTIVE], true)
            ? Arr::get($data, 'status')
            : AffiliateProfile::STATUS_PENDING;

        $referralCode = $this->normalizeReferralCode(Arr::get($data, 'referral_code'));

        $profile = new AffiliateProfile([
            ...$this->applicationPayload([
                ...$data,
                'application_source' => 'admin_created',
                'meta' => [
                    ...((array) Arr::get($data, 'meta', [])),
                    'created_by_admin_id' => $adminId,
                    'admin_created_at' => now()->toIso8601String(),
                ],
            ]),
            'customer_id' => $customer->id,
            'status' => $status,
            'referral_code' => $referralCode ?: $this->generateReferralCode($customer),
            'last_status_changed_at' => now(),
        ]);

        if ($status === AffiliateProfile::STATUS_ACTIVE) {
            $profile->approved_at = now();
            $profile->approved_by_admin_id = $adminId;
        }

        $profile->save();

        return $profile->refresh();
    }

    public function reject(AffiliateProfile $profile, ?int $adminId = null, ?string $reason = null): AffiliateProfile
    {
        $profile->fill([
            'status' => AffiliateProfile::STATUS_REJECTED,
            'rejected_at' => now(),
            'rejected_by_admin_id' => $adminId,
            'rejection_reason' => $reason,
            'last_status_changed_at' => now(),
        ])->save();

        return $profile->refresh();
    }

    public function suspend(AffiliateProfile $profile, ?int $adminId = null, ?string $reason = null): AffiliateProfile
    {
        $profile->fill([
            'status' => AffiliateProfile::STATUS_SUSPENDED,
            'suspended_at' => now(),
            'suspended_by_admin_id' => $adminId,
            'suspension_reason' => $reason,
            'last_status_changed_at' => now(),
        ])->save();

        return $profile->refresh();
    }

    public function reactivate(AffiliateProfile $profile, ?int $adminId = null): AffiliateProfile
    {
        return $this->approve($profile, $adminId);
    }

    public function regenerateReferralCode(AffiliateProfile $profile, ?int $adminId = null): AffiliateProfile
    {
        $oldCode = $profile->referral_code;
        $meta = $profile->meta ?: [];
        $previousCodes = collect(Arr::get($meta, 'previous_referral_codes', []))
            ->push($oldCode)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $profile->fill([
            'referral_code' => $this->generateReferralCode($profile->customer),
            'meta' => [
                ...$meta,
                'previous_referral_codes' => $previousCodes,
                'last_referral_code_regenerated_at' => now()->toIso8601String(),
                'last_referral_code_regenerated_by_admin_id' => $adminId,
            ],
        ])->save();

        return $profile->refresh();
    }

    public function profileForCustomer(?Customer $customer): ?AffiliateProfile
    {
        if (! $customer) {
            return null;
        }

        return AffiliateProfile::query()->where('customer_id', $customer->id)->first();
    }

    public function canAccessPortal(?Customer $customer): bool
    {
        return $this->profileForCustomer($customer)?->isActive() ?? false;
    }

    public function portalState(?Customer $customer): string
    {
        $profile = $this->profileForCustomer($customer);

        if (! $profile) {
            return self::PORTAL_STATE_NO_PROFILE;
        }

        return match ($profile->status) {
            AffiliateProfile::STATUS_ACTIVE => self::PORTAL_STATE_ACTIVE,
            AffiliateProfile::STATUS_PENDING => self::PORTAL_STATE_PENDING,
            AffiliateProfile::STATUS_REJECTED => self::PORTAL_STATE_REJECTED,
            AffiliateProfile::STATUS_SUSPENDED => self::PORTAL_STATE_SUSPENDED,
            default => self::PORTAL_STATE_NO_PROFILE,
        };
    }

    public function generateReferralCode(Customer $customer): string
    {
        $base = 'AFF'.str_pad((string) $customer->id, 6, '0', STR_PAD_LEFT);
        $candidate = $base;

        while (AffiliateProfile::query()->where('referral_code', $candidate)->exists()) {
            $candidate = $base.Str::upper(Str::random(4));
        }

        return $candidate;
    }

    public function normalizeReferralCode(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        if (blank($value)) {
            return null;
        }

        $value = Str::upper(preg_replace('/[^A-Za-z0-9_-]/', '', (string) $value) ?? '');

        return blank($value) ? null : $value;
    }

    protected function applicationPayload(array $data): array
    {
        return [
            'application_source' => Arr::get($data, 'application_source', 'customer_portal'),
            'application_note' => $this->nullableString(Arr::get($data, 'application_note')),
            'website_url' => $this->nullableString(Arr::get($data, 'website_url')),
            'social_profiles' => Arr::get($data, 'social_profiles'),
            'payout_method' => $this->nullableString(Arr::get($data, 'payout_method')),
            'payout_reference' => $this->nullableString(Arr::get($data, 'payout_reference')),
            'meta' => Arr::get($data, 'meta'),
        ];
    }

    protected function nullableString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return blank($value) ? null : (string) $value;
    }
}
