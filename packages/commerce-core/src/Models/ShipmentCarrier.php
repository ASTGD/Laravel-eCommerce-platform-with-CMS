<?php

namespace Platform\CommerceCore\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ShipmentCarrier extends Model
{
    public const INTEGRATION_DRIVER_MANUAL = 'manual';

    protected $table = 'shipment_carriers';

    protected $fillable = [
        'code',
        'name',
        'contact_name',
        'contact_phone',
        'contact_email',
        'tracking_url_template',
        'integration_driver',
        'tracking_sync_enabled',
        'api_base_url',
        'api_username',
        'api_password',
        'api_key',
        'api_secret',
        'webhook_secret',
        'supports_cod',
        'default_cod_fee_type',
        'default_cod_fee_amount',
        'default_return_fee_amount',
        'default_payout_method',
        'notes',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'tracking_sync_enabled' => 'boolean',
        'api_password' => 'encrypted',
        'api_key' => 'encrypted',
        'api_secret' => 'encrypted',
        'webhook_secret' => 'encrypted',
        'supports_cod' => 'boolean',
        'default_cod_fee_amount' => 'decimal:2',
        'default_return_fee_amount' => 'decimal:2',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function trackingDriver(): string
    {
        return $this->integration_driver ?: self::INTEGRATION_DRIVER_MANUAL;
    }

    public function trackingSyncConfigured(): bool
    {
        return (bool) $this->tracking_sync_enabled;
    }

    public function trackingUrl(?string $trackingNumber): ?string
    {
        $template = trim((string) $this->tracking_url_template);
        $trackingNumber = trim((string) $trackingNumber);

        if ($template === '' || $trackingNumber === '') {
            return null;
        }

        return str_replace(
            ['{tracking_number}', '{tracking}'],
            [rawurlencode($trackingNumber), rawurlencode($trackingNumber)],
            $template,
        );
    }
}
