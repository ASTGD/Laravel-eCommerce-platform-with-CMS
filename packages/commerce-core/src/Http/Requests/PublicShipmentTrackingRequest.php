<?php

namespace Platform\CommerceCore\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicShipmentTrackingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
        ];
    }
}
