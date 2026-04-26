<?php

namespace Platform\CommerceCore\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Platform\CommerceCore\Services\Affiliates\AffiliateSettingsService;

class AffiliateWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->guard('customer')->check();
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'payout_method' => ['nullable', Rule::in(array_keys(app(AffiliateSettingsService::class)->payoutMethods()))],
            'payout_reference' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Enter the payout amount you want to withdraw.',
            'amount.numeric' => 'Enter a valid payout amount.',
            'amount.gt' => 'The payout amount must be greater than zero.',
            'payout_method.in' => 'Choose a valid payout method.',
        ];
    }

    public function payload(): array
    {
        $payload = $this->validated();
        $payoutAccountDetails = trim((string) ($payload['payout_reference'] ?? ''));

        return [
            'requested_by_customer_id' => auth()->guard('customer')->id(),
            'payout_method' => $payload['payout_method'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'meta' => $payoutAccountDetails !== ''
                ? ['payout_account_details' => $payoutAccountDetails]
                : null,
        ];
    }
}
