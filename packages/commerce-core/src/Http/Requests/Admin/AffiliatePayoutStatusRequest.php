<?php

namespace Platform\CommerceCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AffiliatePayoutStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payout_reference' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function nullableString(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value === '' ? null : $value;
    }
}
