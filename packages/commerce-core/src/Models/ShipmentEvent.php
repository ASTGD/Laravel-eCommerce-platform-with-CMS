<?php

namespace Platform\CommerceCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\User\Models\Admin;

class ShipmentEvent extends Model
{
    public const EVENT_SHIPMENT_CREATED = 'shipment_created';
    public const EVENT_STATUS_UPDATED = 'status_updated';
    public const EVENT_ARRIVED_DESTINATION_HUB = 'arrived_destination_hub';
    public const EVENT_DELIVERY_ATTEMPTED = 'delivery_attempted';
    public const EVENT_CUSTOMER_UNREACHABLE = 'customer_unreachable';
    public const EVENT_CUSTOMER_REFUSED = 'customer_refused';
    public const EVENT_REATTEMPT_APPROVED = 'reattempt_approved';
    public const EVENT_RETURN_INITIATED = 'return_initiated';
    public const EVENT_RETURN_COMPLETED = 'return_completed';

    protected $fillable = [
        'shipment_record_id',
        'actor_admin_id',
        'event_type',
        'status_after_event',
        'event_at',
        'note',
        'meta',
    ];

    protected $casts = [
        'event_at' => 'datetime',
        'meta' => 'array',
    ];

    public static function labels(): array
    {
        return [
            self::EVENT_SHIPMENT_CREATED => 'Shipment Created',
            self::EVENT_STATUS_UPDATED => 'Status Updated',
            self::EVENT_ARRIVED_DESTINATION_HUB => 'Arrived at Destination Hub',
            self::EVENT_DELIVERY_ATTEMPTED => 'Delivery Attempted',
            self::EVENT_CUSTOMER_UNREACHABLE => 'Customer Unreachable',
            self::EVENT_CUSTOMER_REFUSED => 'Customer Refused',
            self::EVENT_REATTEMPT_APPROVED => 'Reattempt Approved',
            self::EVENT_RETURN_INITIATED => 'Return Initiated',
            self::EVENT_RETURN_COMPLETED => 'Return Completed',
        ];
    }

    public static function manualEventLabels(): array
    {
        return [
            self::EVENT_ARRIVED_DESTINATION_HUB => static::labels()[self::EVENT_ARRIVED_DESTINATION_HUB],
            self::EVENT_DELIVERY_ATTEMPTED => static::labels()[self::EVENT_DELIVERY_ATTEMPTED],
        ];
    }

    public function getEventTypeLabelAttribute(): string
    {
        return static::labels()[$this->event_type] ?? str($this->event_type)->replace('_', ' ')->title()->value();
    }

    public function shipmentRecord(): BelongsTo
    {
        return $this->belongsTo(ShipmentRecord::class);
    }

    public function communications(): HasMany
    {
        return $this->hasMany(ShipmentCommunication::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'actor_admin_id');
    }
}
