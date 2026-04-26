<?php

namespace Platform\CommerceCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Platform\CommerceCore\Services\Affiliates\AffiliateSettingsService;

class AffiliatePayoutRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'size:3'],
            'payout_method' => ['required', Rule::in(array_keys(app(AffiliateSettingsService::class)->payoutMethods()))],
            'payout_reference' => ['nullable', 'string', 'max:255'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function payload(): array
    {
        return [
            'currency' => $this->string('currency')->trim()->upper()->value() ?: null,
            'payout_method' => $this->string('payout_method')->value(),
            'payout_reference' => $this->nullableString('payout_reference'),
            'admin_notes' => $this->nullableString('admin_notes'),
        ];
    }

    public function amount(): float
    {
        return round((float) $this->input('amount'), 4);
    }

    protected function nullableString(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value === '' ? null : $value;
    }
}
