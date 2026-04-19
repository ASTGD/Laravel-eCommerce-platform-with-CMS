<?php

namespace Platform\CommerceCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ShipmentBookingReferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'carrier_booking_reference' => ['nullable', 'string', 'max:255'],
            'carrier_consignment_id' => ['nullable', 'string', 'max:255'],
            'carrier_invoice_reference' => ['nullable', 'string', 'max:255'],
            'carrier_booked_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
        ];
    }

    public function payload(): array
    {
        $payload = $this->validated();

        foreach ([
            'carrier_booking_reference',
            'carrier_consignment_id',
            'carrier_invoice_reference',
            'carrier_booked_at',
        ] as $field) {
            if (array_key_exists($field, $payload) && blank($payload[$field])) {
                $payload[$field] = null;
            }
        }

        return $payload;
    }
}
