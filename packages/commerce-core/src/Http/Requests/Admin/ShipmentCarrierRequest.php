<?php

namespace Platform\CommerceCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Support\ShippingMode;

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
        self::COURIER_SERVICE_MANUAL_OTHER => 'Manual',
        self::COURIER_SERVICE_STEADFAST    => 'Steadfast',
        self::COURIER_SERVICE_PATHAO       => 'Pathao',
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
            'code' => $this->resolvedCode($carrier),
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
            'code' => ['nullable', 'string', 'max:100', 'alpha_dash', Rule::unique('shipment_carriers', 'code')->ignore($carrierId)],
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => $courierService === self::COURIER_SERVICE_PATHAO && $this->usesAdvancedCarrierConfiguration()
                ? ['required', 'string', 'max:50']
                : ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'tracking_url_template' => ['nullable', 'string', 'max:500'],
            'integration_driver' => ['nullable', Rule::in(self::INTEGRATION_DRIVERS)],
            'tracking_sync_enabled' => ['nullable', 'boolean'],
            'api_base_url' => $this->requiresIntegratedApi($courierService)
                ? ['required', 'url', 'max:500']
                : ['nullable', 'url', 'max:500'],
            'api_store_id' => $courierService === self::COURIER_SERVICE_PATHAO && $this->usesAdvancedCarrierConfiguration()
                ? ['required', 'integer', 'min:1']
                : ['nullable', 'integer', 'min:1'],
            'api_username' => $courierService === self::COURIER_SERVICE_PATHAO && $this->usesAdvancedCarrierConfiguration()
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
            'courier_service' => 'automation type',
            'code' => 'courier code',
            'name' => 'courier name',
            'contact_name' => 'contact person',
            'contact_phone' => 'phone',
            'address' => 'address',
            'contact_email' => 'support email',
            'tracking_url_template' => 'tracking URL template',
            'api_base_url' => 'API URL',
            'api_store_id' => 'store ID',
            'api_username' => 'username',
            'api_password' => 'password',
            'api_key' => 'API key',
            'api_secret' => 'API secret',
            'webhook_secret' => 'status update secret',
            'default_cod_fee_type' => 'COD fee type',
            'default_cod_fee_amount' => 'default COD fee',
            'default_return_fee_amount' => 'default return fee',
            'default_payout_method' => 'default payout method',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Enter the courier name your team uses.',
            'code.alpha_dash' => 'Courier code can only use letters, numbers, dashes, and underscores.',
            'contact_phone.required' => 'Enter the phone number linked to your Pathao merchant account.',
            'api_base_url.required' => 'Enter the API URL provided for '.$this->selectedCourierLabel().'.',
            'api_base_url.url' => 'Enter a valid API URL.',
            'api_store_id.required' => 'Enter the Pathao store ID from your merchant account.',
            'api_username.required' => 'Enter the Pathao username from your merchant account.',
            'api_password.required' => 'Enter the Pathao password from your merchant account.',
            'api_key.required' => 'Enter the API key provided for this courier connection.',
            'api_secret.required' => 'Enter the API secret provided for this courier connection.',
            'default_cod_fee_amount.min' => 'Default COD fee cannot be negative.',
            'default_return_fee_amount.min' => 'Default return fee cannot be negative.',
        ];
    }

    public function payload(): array
    {
        $payload = $this->validated();
        $carrier = $this->route('carrier');

        unset($payload['courier_service']);

        $payload['code'] = Str::lower($this->resolvedCode($carrier));
        $payload['integration_driver'] = $payload['integration_driver'] ?? ($carrier?->trackingDriver() ?? ShipmentCarrier::INTEGRATION_DRIVER_MANUAL);
        $payload['tracking_sync_enabled'] = array_key_exists('tracking_sync_enabled', $payload)
            ? (bool) $payload['tracking_sync_enabled']
            : ($carrier?->exists ? (bool) $carrier->tracking_sync_enabled : false);
        $payload['supports_cod'] = (bool) ($payload['supports_cod'] ?? false);
        $payload['is_active'] = (bool) ($payload['is_active'] ?? false);

        $this->normalizeNullableField($payload, 'contact_name', $carrier);
        $this->normalizeNullableField($payload, 'contact_phone', $carrier);
        $this->normalizeNullableField($payload, 'address', $carrier);
        $this->normalizeNullableField($payload, 'contact_email', $carrier);
        $this->normalizeNullableField($payload, 'tracking_url_template', $carrier);
        $this->normalizeNullableField($payload, 'api_base_url', $carrier);
        $this->normalizeNullableField($payload, 'api_username', $carrier);
        $this->normalizeNullableField($payload, 'notes', $carrier);

        $this->normalizeNullableIntegerField($payload, 'api_store_id', $carrier);
        $this->normalizeIntegerField($payload, 'sort_order', $carrier, 0);
        $this->normalizeDecimalField($payload, 'default_cod_fee_amount', $carrier, 0.0);
        $this->normalizeDecimalField($payload, 'default_return_fee_amount', $carrier, 0.0);

        if (array_key_exists('default_cod_fee_type', $payload)) {
            $payload['default_cod_fee_type'] = blank($payload['default_cod_fee_type']) ? null : $payload['default_cod_fee_type'];
        } elseif ($carrier?->exists) {
            unset($payload['default_cod_fee_type']);
        } else {
            $payload['default_cod_fee_type'] = null;
        }

        if (array_key_exists('default_payout_method', $payload)) {
            $payload['default_payout_method'] = blank($payload['default_payout_method']) ? null : $payload['default_payout_method'];
        } elseif ($carrier?->exists) {
            unset($payload['default_payout_method']);
        } else {
            $payload['default_payout_method'] = null;
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
            $payload['default_return_fee_amount'] = 0.0;
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
            self::COURIER_SERVICE_STEADFAST => self::COURIER_SERVICE_STEADFAST,
            self::COURIER_SERVICE_PATHAO => self::COURIER_SERVICE_PATHAO,
            ShipmentCarrier::INTEGRATION_DRIVER_MANUAL,
            'custom_api',
            'paperfly',
            'redx',
            null,
            '' => self::COURIER_SERVICE_MANUAL_OTHER,
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

    protected function usesAdvancedCarrierConfiguration(): bool
    {
        return app(ShippingMode::class)->showsAdvancedCarrierConfiguration();
    }

    protected function requiresIntegratedApi(string $courierService): bool
    {
        if (! $this->usesAdvancedCarrierConfiguration()) {
            return false;
        }

        return in_array($courierService, [
            self::COURIER_SERVICE_STEADFAST,
            self::COURIER_SERVICE_PATHAO,
        ], true);
    }

    protected function requiresSecretField(string $field, string $courierService): bool
    {
        if (! $this->usesAdvancedCarrierConfiguration()) {
            return false;
        }

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
        if (! app(ShippingMode::class)->showsAdvancedCarrierConfiguration()) {
            return $carrier?->exists
                ? $carrier->trackingDriver()
                : ShipmentCarrier::INTEGRATION_DRIVER_MANUAL;
        }

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

    protected function resolvedCode(?ShipmentCarrier $carrier): string
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
            $base = 'courier';
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
        if (! $this->usesAdvancedCarrierConfiguration()) {
            return $carrier?->exists
                ? (bool) $carrier->tracking_sync_enabled
                : false;
        }

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

    protected function normalizeNullableField(array &$payload, string $field, ?ShipmentCarrier $carrier): void
    {
        if (! array_key_exists($field, $payload)) {
            if (! $carrier?->exists) {
                $payload[$field] = null;
            }

            return;
        }

        $payload[$field] = blank($payload[$field]) ? null : $payload[$field];
    }

    protected function normalizeNullableIntegerField(array &$payload, string $field, ?ShipmentCarrier $carrier): void
    {
        if (! array_key_exists($field, $payload)) {
            if (! $carrier?->exists) {
                $payload[$field] = null;
            }

            return;
        }

        $payload[$field] = blank($payload[$field]) ? null : (int) $payload[$field];
    }

    protected function normalizeIntegerField(array &$payload, string $field, ?ShipmentCarrier $carrier, int $default): void
    {
        if (! array_key_exists($field, $payload)) {
            if ($carrier?->exists) {
                unset($payload[$field]);
            } else {
                $payload[$field] = $default;
            }

            return;
        }

        $payload[$field] = blank($payload[$field]) ? $default : (int) $payload[$field];
    }

    protected function normalizeDecimalField(array &$payload, string $field, ?ShipmentCarrier $carrier, float $default): void
    {
        if (! array_key_exists($field, $payload)) {
            if ($carrier?->exists) {
                unset($payload[$field]);
            } else {
                $payload[$field] = $default;
            }

            return;
        }

        $payload[$field] = round(blank($payload[$field]) ? $default : (float) $payload[$field], 2);
    }
}
