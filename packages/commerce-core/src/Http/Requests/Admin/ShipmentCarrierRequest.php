<?php

namespace Platform\CommerceCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Platform\CommerceCore\Models\ShipmentCarrier;

class ShipmentCarrierRequest extends FormRequest
{
    public const COURIER_SERVICE_STEADFAST = 'steadfast';

    public const COURIER_SERVICE_PATHAO = 'pathao';

    public const COURIER_SERVICE_MANUAL_OTHER = 'manual_other';

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

    public const COURIER_OPTIONS = [
        self::COURIER_SERVICE_STEADFAST   => 'Steadfast',
        self::COURIER_SERVICE_PATHAO      => 'Pathao',
        self::COURIER_SERVICE_MANUAL_OTHER => 'Manual / Other',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $carrier = $this->route('carrier');
        $courierService = $this->selectedCourierService();
        $integrationDriver = self::resolveIntegrationDriver($courierService, $carrier);

        $this->merge([
            'courier_service' => $courierService,
            'integration_driver' => $integrationDriver,
            'code' => $this->resolvedCode($courierService, $carrier),
            'tracking_sync_enabled' => $this->resolvedTrackingSyncEnabled($integrationDriver, $carrier),
            'supports_cod' => $this->resolvedBoolean('supports_cod', $carrier?->supports_cod ?? true),
            'is_active' => $this->resolvedBoolean('is_active', $carrier?->is_active ?? true),
        ]);
    }

    public function rules(): array
    {
        $carrierId = $this->route('carrier')?->id;
        $courierService = $this->selectedCourierService();

        return [
            'courier_service' => ['required', Rule::in(array_keys(self::COURIER_OPTIONS))],
            'code' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('shipment_carriers', 'code')->ignore($carrierId)],
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => $courierService === self::COURIER_SERVICE_PATHAO
                ? ['required', 'string', 'max:50']
                : ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'tracking_url_template' => ['nullable', 'string', 'max:500'],
            'integration_driver' => ['nullable', Rule::in(self::INTEGRATION_DRIVERS)],
            'tracking_sync_enabled' => ['nullable', 'boolean'],
            'api_base_url' => $this->requiresIntegratedApi($courierService)
                ? ['required', 'url', 'max:500']
                : ['nullable', 'url', 'max:500'],
            'api_store_id' => $courierService === self::COURIER_SERVICE_PATHAO
                ? ['required', 'integer', 'min:1']
                : ['nullable', 'integer', 'min:1'],
            'api_username' => $courierService === self::COURIER_SERVICE_PATHAO
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'],
            'api_password' => $this->requiresSecretField('api_password', $courierService)
                ? ['required', 'string', 'max:5000']
                : ['nullable', 'string', 'max:5000'],
            'api_key' => $this->requiresSecretField('api_key', $courierService)
                ? ['required', 'string', 'max:5000']
                : ['nullable', 'string', 'max:5000'],
            'api_secret' => $this->requiresSecretField('api_secret', $courierService)
                ? ['required', 'string', 'max:5000']
                : ['nullable', 'string', 'max:5000'],
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

    public function attributes(): array
    {
        return [
            'courier_service' => 'courier service',
            'name' => 'display name',
            'contact_name' => 'contact person',
            'contact_phone' => 'pickup phone number',
            'contact_email' => 'support email address',
            'tracking_url_template' => 'public tracking link',
            'api_base_url' => 'courier API URL',
            'api_store_id' => 'Pathao store ID',
            'api_username' => 'Pathao username',
            'api_password' => 'Pathao password',
            'api_key' => 'API key',
            'api_secret' => 'API secret',
            'webhook_secret' => 'status update secret',
            'default_cod_fee_type' => 'cash collection fee type',
            'default_cod_fee_amount' => 'default COD fee',
            'default_return_fee_amount' => 'default return fee',
            'default_payout_method' => 'default payout method',
        ];
    }

    public function messages(): array
    {
        return [
            'courier_service.required' => 'Choose the courier service you want to add.',
            'name.required' => 'Enter the display name you want to see in shipment operations.',
            'contact_phone.required' => 'Enter the pickup phone number from your Pathao merchant account.',
            'api_base_url.required' => 'Enter the courier API URL provided by '.$this->selectedCourierLabel().'.',
            'api_base_url.url' => 'Enter a valid courier API URL.',
            'api_store_id.required' => 'Enter the Pathao store ID from your merchant account.',
            'api_username.required' => 'Enter the Pathao username from your merchant account.',
            'api_password.required' => 'Enter the Pathao password from your merchant account.',
            'api_key.required' => 'Enter the API key provided by the courier.',
            'api_secret.required' => 'Enter the API secret provided by the courier.',
            'default_cod_fee_amount.min' => 'Default COD fee cannot be negative.',
            'default_return_fee_amount.min' => 'Default return fee cannot be negative.',
        ];
    }

    public function payload(): array
    {
        $payload = $this->validated();
        $carrier = $this->route('carrier');

        unset($payload['courier_service']);

        $payload['code'] = Str::lower($payload['code']);
        $payload['integration_driver'] = $payload['integration_driver'] ?? ShipmentCarrier::INTEGRATION_DRIVER_MANUAL;
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

    public static function courierOptions(): array
    {
        return self::COURIER_OPTIONS;
    }

    public static function courierServiceForDriver(?string $driver): string
    {
        return match ($driver) {
            ShipmentCarrier::INTEGRATION_DRIVER_MANUAL,
            'custom_api',
            'paperfly',
            'redx',
            null,
            '' => self::COURIER_SERVICE_MANUAL_OTHER,
            self::COURIER_SERVICE_STEADFAST => self::COURIER_SERVICE_STEADFAST,
            self::COURIER_SERVICE_PATHAO => self::COURIER_SERVICE_PATHAO,
            default => self::COURIER_SERVICE_MANUAL_OTHER,
        };
    }

    protected function selectedCourierService(): string
    {
        $service = $this->input('courier_service');

        if (is_string($service) && array_key_exists($service, self::COURIER_OPTIONS)) {
            return $service;
        }

        return self::courierServiceForDriver($this->route('carrier')?->trackingDriver());
    }

    protected function selectedCourierLabel(): string
    {
        return self::COURIER_OPTIONS[$this->selectedCourierService()] ?? 'the selected courier';
    }

    protected function requiresIntegratedApi(string $courierService): bool
    {
        return in_array($courierService, [
            self::COURIER_SERVICE_STEADFAST,
            self::COURIER_SERVICE_PATHAO,
        ], true);
    }

    protected function requiresSecretField(string $field, string $courierService): bool
    {
        if (! in_array($field, self::SECRET_FIELDS, true)) {
            return false;
        }

        $relevantServices = match ($field) {
            'api_password' => [self::COURIER_SERVICE_PATHAO],
            'api_key', 'api_secret' => [self::COURIER_SERVICE_STEADFAST, self::COURIER_SERVICE_PATHAO],
            default => [],
        };

        if (! in_array($courierService, $relevantServices, true)) {
            return false;
        }

        $carrier = $this->route('carrier');

        return ! ($carrier?->exists && filled($carrier->{$field}));
    }

    protected static function resolveIntegrationDriver(string $courierService, ?ShipmentCarrier $carrier): string
    {
        return match ($courierService) {
            self::COURIER_SERVICE_STEADFAST => self::COURIER_SERVICE_STEADFAST,
            self::COURIER_SERVICE_PATHAO => self::COURIER_SERVICE_PATHAO,
            self::COURIER_SERVICE_MANUAL_OTHER => self::preservedManualDriver($carrier),
            default => ShipmentCarrier::INTEGRATION_DRIVER_MANUAL,
        };
    }

    protected static function preservedManualDriver(?ShipmentCarrier $carrier): string
    {
        $currentDriver = $carrier?->trackingDriver();

        if ($carrier?->exists && ! in_array($currentDriver, [
            self::COURIER_SERVICE_STEADFAST,
            self::COURIER_SERVICE_PATHAO,
            null,
            '',
        ], true)) {
            return $currentDriver;
        }

        return ShipmentCarrier::INTEGRATION_DRIVER_MANUAL;
    }

    protected function resolvedCode(string $courierService, ?ShipmentCarrier $carrier): string
    {
        $providedCode = trim((string) $this->input('code'));

        if ($providedCode !== '') {
            return Str::lower($providedCode);
        }

        if ($carrier?->exists && filled($carrier->code)) {
            return Str::lower((string) $carrier->code);
        }

        $base = Str::slug(trim((string) $this->input('name')), '_');

        if ($base === '') {
            $base = $courierService === self::COURIER_SERVICE_MANUAL_OTHER
                ? 'courier_service'
                : $courierService;
        }

        $candidate = $base;
        $suffix = 2;

        while (ShipmentCarrier::query()->where('code', $candidate)->exists()) {
            $candidate = $base.'_'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    protected function resolvedTrackingSyncEnabled(string $integrationDriver, ?ShipmentCarrier $carrier): bool
    {
        if ($this->has('tracking_sync_enabled')) {
            return $this->boolean('tracking_sync_enabled');
        }

        if (in_array($integrationDriver, [
            self::COURIER_SERVICE_STEADFAST,
            self::COURIER_SERVICE_PATHAO,
        ], true)) {
            return $carrier?->exists ? (bool) $carrier->tracking_sync_enabled : true;
        }

        return $carrier?->exists && self::courierServiceForDriver($carrier->trackingDriver()) === self::COURIER_SERVICE_MANUAL_OTHER
            ? (bool) $carrier->tracking_sync_enabled
            : false;
    }

    protected function resolvedBoolean(string $field, bool $default): bool
    {
        if ($this->has($field)) {
            return $this->boolean($field);
        }

        return $default;
    }
}
