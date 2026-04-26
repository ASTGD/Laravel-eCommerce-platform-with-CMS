<?php

namespace Platform\CommerceCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AffiliateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function reason(): ?string
    {
        $reason = trim((string) $this->input('reason', ''));

        return $reason === '' ? null : $reason;
    }
}
