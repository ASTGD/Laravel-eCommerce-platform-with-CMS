<?php

namespace Platform\CommerceCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Platform\CommerceCore\Models\SettlementBatch;

class SettlementBatchUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(SettlementBatch::statuses())],
            'reference' => ['required', 'string', 'max:255'],
            'payout_method' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
