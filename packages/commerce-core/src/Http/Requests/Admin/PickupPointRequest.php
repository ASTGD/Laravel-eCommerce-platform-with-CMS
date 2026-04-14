<?php

namespace Platform\CommerceCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PickupPointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $pickupPointId = $this->route('pickupPoint')?->id;

        return [
            'code'           => ['required', 'string', 'max:255', Rule::unique('pickup_points', 'code')->ignore($pickupPointId)],
            'name'           => ['required', 'string', 'max:255'],
            'slug'           => ['nullable', 'string', 'max:255', Rule::unique('pickup_points', 'slug')->ignore($pickupPointId)],
            'courier_name'   => ['nullable', 'string', 'max:255'],
            'phone'          => ['nullable', 'string', 'max:255'],
            'email'          => ['nullable', 'email', 'max:255'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city'           => ['required', 'string', 'max:255'],
            'state'          => ['nullable', 'string', 'max:255'],
            'postcode'       => ['nullable', 'string', 'max:255'],
            'country'        => ['required', 'string', 'size:2'],
            'landmark'       => ['nullable', 'string', 'max:255'],
            'opening_hours'  => ['nullable', 'string'],
            'notes'          => ['nullable', 'string'],
            'sort_order'     => ['nullable', 'integer', 'min:0'],
            'is_active'      => ['nullable', 'boolean'],
        ];
    }

    public function payload(): array
    {
        $payload = $this->validated();

        $payload['slug'] = ($payload['slug'] ?? null) ?: Str::slug($payload['name']);
        $payload['is_active'] = (bool) ($payload['is_active'] ?? false);
        $payload['sort_order'] = (int) ($payload['sort_order'] ?? 0);

        return $payload;
    }
}
