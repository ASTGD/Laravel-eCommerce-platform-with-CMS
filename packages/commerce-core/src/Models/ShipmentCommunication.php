<?php

namespace Platform\CommerceCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentCommunication extends Model
{
    public const AUDIENCE_CUSTOMER = 'customer';
    public const AUDIENCE_ADMIN = 'admin';

    public const CHANNEL_EMAIL = 'email';

    public const STATUS_QUEUED = 'queued';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_FAILED = 'failed';

    public const KEY_OUT_FOR_DELIVERY = 'out_for_delivery';
    public const KEY_DELIVERED = 'delivered';
    public const KEY_DELIVERY_FAILED = 'delivery_failed';
    public const KEY_RETURN_INITIATED = 'return_initiated';
    public const KEY_RETURNED = 'returned';

    protected $fillable = [
        'shipment_record_id',
        'shipment_event_id',
        'audience',
        'channel',
        'notification_key',
        'recipient_name',
        'recipient_email',
        'subject',
        'status',
        'reason',
        'queued_at',
        'failed_at',
        'meta',
    ];

    protected $casts = [
        'queued_at' => 'datetime',
        'failed_at' => 'datetime',
        'meta' => 'array',
    ];

    public static function notificationLabels(): array
    {
        return [
            self::KEY_OUT_FOR_DELIVERY => 'Out for Delivery',
            self::KEY_DELIVERED => 'Delivered',
            self::KEY_DELIVERY_FAILED => 'Delivery Failed',
            self::KEY_RETURN_INITIATED => 'Return Initiated',
            self::KEY_RETURNED => 'Returned',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_QUEUED => 'Queued',
            self::STATUS_SKIPPED => 'Skipped',
            self::STATUS_FAILED => 'Failed',
        ];
    }

    public function getNotificationLabelAttribute(): string
    {
        return static::notificationLabels()[$this->notification_key]
            ?? str($this->notification_key)->replace('_', ' ')->title()->value();
    }

    public function getStatusLabelAttribute(): string
    {
        return static::statusLabels()[$this->status]
            ?? str($this->status)->replace('_', ' ')->title()->value();
    }

    public function shipmentRecord(): BelongsTo
    {
        return $this->belongsTo(ShipmentRecord::class);
    }

    public function shipmentEvent(): BelongsTo
    {
        return $this->belongsTo(ShipmentEvent::class);
    }
}
