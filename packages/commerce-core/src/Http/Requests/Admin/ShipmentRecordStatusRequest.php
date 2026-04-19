<?php

namespace Platform\CommerceCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Platform\CommerceCore\Models\ShipmentRecord;

class ShipmentRecordStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(ShipmentRecord::statuses())],
            'note' => ['nullable', 'string'],
        ];
    }
}
