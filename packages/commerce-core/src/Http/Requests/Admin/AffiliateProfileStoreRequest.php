<?php

namespace Platform\CommerceCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Platform\CommerceCore\Models\AffiliateProfile;
use Platform\CommerceCore\Services\Affiliates\AffiliateProfileService;
use Platform\CommerceCore\Services\Affiliates\AffiliateSettingsService;

class AffiliateProfileStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'referral_code' => app(AffiliateProfileService::class)->normalizeReferralCode($this->input('referral_code')),
        ]);
    }

    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'integer',
                'exists:customers,id',
                Rule::unique('affiliate_profiles', 'customer_id'),
            ],
            'status' => ['required', Rule::in([AffiliateProfile::STATUS_PENDING, AffiliateProfile::STATUS_ACTIVE])],
            'referral_code' => ['nullable', 'string', 'max:64', Rule::unique('affiliate_profiles', 'referral_code')],
            'application_note' => ['nullable', 'string', 'max:1000'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'social_profiles_text' => ['nullable', 'string', 'max:1000'],
            'payout_method' => ['nullable', Rule::in(array_keys(app(AffiliateSettingsService::class)->payoutMethods()))],
            'payout_reference' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Choose the customer account that will own this affiliate profile.',
            'customer_id.unique' => 'This customer already has an affiliate profile.',
            'status.in' => 'Choose Pending or Active for the new affiliate.',
            'referral_code.unique' => 'This referral code is already used by another affiliate.',
            'website_url.url' => 'Enter a valid website URL or leave it blank.',
            'payout_method.in' => 'Choose a valid payout method.',
        ];
    }

    public function payload(): array
    {
        $payload = $this->validated();
        $socialProfiles = trim((string) ($payload['social_profiles_text'] ?? ''));

        unset($payload['social_profiles_text']);

        $payload['social_profiles'] = $socialProfiles !== ''
            ? ['text' => $socialProfiles]
            : null;

        return $payload;
    }
}
