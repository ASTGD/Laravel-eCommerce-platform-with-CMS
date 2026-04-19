<?php

namespace Platform\CommerceCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Platform\CommerceCore\Models\ShipmentRecord;

class ShipmentDeliveryFailureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'failure_reason' => ['required', 'string', Rule::in(ShipmentRecord::failureReasons())],
            'requires_reattempt' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
