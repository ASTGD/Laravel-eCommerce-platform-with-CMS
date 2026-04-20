<?php

namespace Platform\CommerceCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ShipmentCarrierRequest extends FormRequest
{
    protected const SECRET_FIELDS = [
        'api_password',
        'api_key',
        'api_secret',
        'webhook_secret',
    ];

    public const COD_FEE_TYPES = [
        'flat',
        'percentage',
    ];

    public const INTEGRATION_DRIVERS = [
        'manual',
        'steadfast',
        'pathao',
        'redx',
        'paperfly',
        'custom_api',
    ];

    public const PAYOUT_METHODS = [
        'bank_transfer',
        'bkash',
        'nagad',
        'rocket',
        'cash',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $carrierId = $this->route('carrier')?->id;

        return [
            'code' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('shipment_carriers', 'code')->ignore($carrierId)],
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'tracking_url_template' => ['nullable', 'string', 'max:500'],
            'integration_driver' => ['nullable', Rule::in(self::INTEGRATION_DRIVERS)],
            'tracking_sync_enabled' => ['nullable', 'boolean'],
            'api_base_url' => ['nullable', 'url', 'max:500'],
            'api_store_id' => ['nullable', 'integer', 'min:1'],
            'api_username' => ['nullable', 'string', 'max:255'],
            'api_password' => ['nullable', 'string', 'max:5000'],
            'api_key' => ['nullable', 'string', 'max:5000'],
            'api_secret' => ['nullable', 'string', 'max:5000'],
            'webhook_secret' => ['nullable', 'string', 'max:5000'],
            'supports_cod' => ['nullable', 'boolean'],
            'default_cod_fee_type' => ['nullable', Rule::in(self::COD_FEE_TYPES)],
            'default_cod_fee_amount' => ['nullable', 'numeric', 'min:0'],
            'default_return_fee_amount' => ['nullable', 'numeric', 'min:0'],
            'default_payout_method' => ['nullable', Rule::in(self::PAYOUT_METHODS)],
            'notes' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function payload(): array
    {
        $payload = $this->validated();
        $carrier = $this->route('carrier');

        $payload['code'] = Str::lower($payload['code']);
        $payload['integration_driver'] = $payload['integration_driver'] ?? 'manual';
        $payload['tracking_sync_enabled'] = (bool) ($payload['tracking_sync_enabled'] ?? false);
        $payload['supports_cod'] = (bool) ($payload['supports_cod'] ?? false);
        $payload['is_active'] = (bool) ($payload['is_active'] ?? false);
        $payload['sort_order'] = (int) ($payload['sort_order'] ?? 0);
        $payload['default_cod_fee_amount'] = round((float) ($payload['default_cod_fee_amount'] ?? 0), 2);
        $payload['default_return_fee_amount'] = round((float) ($payload['default_return_fee_amount'] ?? 0), 2);

        foreach (['contact_name', 'contact_phone', 'contact_email', 'tracking_url_template', 'api_base_url', 'api_username', 'api_store_id', 'notes'] as $field) {
            if (array_key_exists($field, $payload) && blank($payload[$field])) {
                $payload[$field] = null;
            }
        }

        foreach (self::SECRET_FIELDS as $field) {
            if (! array_key_exists($field, $payload) || blank($payload[$field])) {
                if ($carrier?->exists) {
                    unset($payload[$field]);
                } else {
                    $payload[$field] = null;
                }
            }
        }

        if (! $payload['supports_cod']) {
            $payload['default_cod_fee_type'] = null;
            $payload['default_cod_fee_amount'] = 0.0;
            $payload['default_payout_method'] = null;
        }

        return $payload;
    }
}
