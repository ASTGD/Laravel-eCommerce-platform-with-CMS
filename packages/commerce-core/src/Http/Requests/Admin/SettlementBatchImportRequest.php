<?php

namespace Platform\CommerceCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Platform\CommerceCore\Models\SettlementBatch;

class SettlementBatchImportRequest extends FormRequest
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
            'import_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ];
    }
}
