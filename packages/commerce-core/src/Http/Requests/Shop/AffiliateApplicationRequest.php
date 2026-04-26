<?php

namespace Platform\CommerceCore\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Platform\CommerceCore\Services\Affiliates\AffiliateSettingsService;

class AffiliateApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->guard('customer')->check();
    }

    public function rules(): array
    {
        return [
            'application_note' => ['required', 'string', 'max:1000'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'social_profiles_text' => ['nullable', 'string', 'max:1000'],
            'payout_method' => ['nullable', Rule::in(array_keys(app(AffiliateSettingsService::class)->payoutMethods()))],
            'payout_reference' => ['nullable', 'string', 'max:500'],
            'terms_accepted' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'application_note.required' => 'Tell us briefly how you plan to promote this store.',
            'website_url.url' => 'Enter a valid website URL or leave it blank.',
            'payout_method.in' => 'Choose a valid payout method.',
            'terms_accepted.accepted' => 'Please accept the affiliate terms before applying.',
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

        $payload['application_source'] = 'customer_portal';
        $payload['terms_accepted'] = true;

        return $payload;
    }
}
