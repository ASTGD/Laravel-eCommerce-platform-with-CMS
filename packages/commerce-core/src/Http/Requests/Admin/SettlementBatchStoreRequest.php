<?php

namespace Platform\CommerceCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Platform\CommerceCore\Models\SettlementBatch;

class SettlementBatchStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'max:255', 'unique:settlement_batches,reference'],
            'shipment_carrier_id' => ['nullable', 'integer', 'exists:shipment_carriers,id'],
            'payout_method' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(SettlementBatch::statuses())],
            'notes' => ['nullable', 'string', 'max:5000'],
            'settlement_ids' => ['required', 'array', 'min:1'],
            'settlement_ids.*' => ['integer', 'exists:cod_settlements,id'],
            'remitted_amounts' => ['nullable', 'array'],
            'remitted_amounts.*' => ['nullable', 'numeric', 'min:0'],
            'adjustment_amounts' => ['nullable', 'array'],
            'adjustment_amounts.*' => ['nullable', 'numeric'],
            'item_notes' => ['nullable', 'array'],
            'item_notes.*' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
