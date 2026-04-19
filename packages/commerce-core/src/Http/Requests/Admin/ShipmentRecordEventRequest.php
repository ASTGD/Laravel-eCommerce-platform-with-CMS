<?php

namespace Platform\CommerceCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Platform\CommerceCore\Models\ShipmentEvent;
use Platform\CommerceCore\Models\ShipmentRecord;

class ShipmentRecordEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_type' => ['required', 'string', Rule::in(array_keys(ShipmentEvent::manualEventLabels()))],
            'status_after_event' => ['nullable', 'string', Rule::in(ShipmentRecord::statuses())],
            'note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
