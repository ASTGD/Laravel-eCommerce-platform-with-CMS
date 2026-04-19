<?php

namespace Platform\CommerceCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ShipmentReattemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
