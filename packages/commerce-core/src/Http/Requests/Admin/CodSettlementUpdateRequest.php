<?php

namespace Platform\CommerceCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Platform\CommerceCore\Models\CodSettlement;

class CodSettlementUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(CodSettlement::statuses())],
            'collected_amount' => ['nullable', 'numeric', 'min:0'],
            'remitted_amount' => ['nullable', 'numeric', 'min:0'],
            'short_amount' => ['nullable', 'numeric', 'min:0'],
            'disputed_amount' => ['nullable', 'numeric', 'min:0'],
            'carrier_fee_amount' => ['nullable', 'numeric', 'min:0'],
            'cod_fee_amount' => ['nullable', 'numeric', 'min:0'],
            'return_fee_amount' => ['nullable', 'numeric', 'min:0'],
            'dispute_note' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
