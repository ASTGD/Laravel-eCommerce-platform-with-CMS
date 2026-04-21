<?php

namespace Platform\CommerceCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\Shipment;
use Webkul\User\Models\Admin;

class ShipmentRecord extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_READY_FOR_PICKUP = 'ready_for_pickup';
    public const STATUS_HANDED_TO_CARRIER = 'handed_to_carrier';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_DELIVERY_FAILED = 'delivery_failed';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_CANCELED = 'canceled';

    public const FAILURE_REASON_CUSTOMER_UNREACHABLE = 'customer_unreachable';
    public const FAILURE_REASON_CUSTOMER_REFUSED = 'customer_refused';
    public const FAILURE_REASON_WRONG_ADDRESS = 'wrong_address';
    public const FAILURE_REASON_AREA_UNREACHABLE = 'area_unreachable';
    public const FAILURE_REASON_RESCHEDULED_BY_CUSTOMER = 'rescheduled_by_customer';
    public const FAILURE_REASON_PARCEL_DAMAGED = 'parcel_damaged';
    public const FAILURE_REASON_OTHER = 'other';

    protected $fillable = [
        'order_id',
        'native_shipment_id',
        'shipment_carrier_id',
        'inventory_source_id',
        'created_by_admin_id',
        'updated_by_admin_id',
        'status',
        'carrier_name_snapshot',
        'tracking_number',
        'public_tracking_url',
        'carrier_booking_reference',
        'carrier_consignment_id',
        'carrier_invoice_reference',
        'carrier_booked_at',
        'inventory_source_name',
        'origin_label',
        'destination_country',
        'destination_region',
        'destination_city',
        'recipient_name',
        'recipient_phone',
        'recipient_address',
        'cod_amount_expected',
        'cod_amount_collected',
        'carrier_fee_amount',
        'cod_fee_amount',
        'return_fee_amount',
        'net_remittable_amount',
        'delivery_attempt_count',
        'delivery_failure_reason',
        'requires_reattempt',
        'last_delivery_attempt_at',
        'return_initiated_at',
        'last_tracking_synced_at',
        'last_tracking_sync_status',
        'last_tracking_sync_message',
        'handed_over_at',
        'delivered_at',
        'returned_at',
        'notes',
    ];

    protected $casts = [
        'cod_amount_expected' => 'decimal:2',
        'cod_amount_collected' => 'decimal:2',
        'carrier_fee_amount' => 'decimal:2',
        'cod_fee_amount' => 'decimal:2',
        'return_fee_amount' => 'decimal:2',
        'net_remittable_amount' => 'decimal:2',
        'delivery_attempt_count' => 'integer',
        'requires_reattempt' => 'boolean',
        'carrier_booked_at' => 'datetime',
        'last_delivery_attempt_at' => 'datetime',
        'return_initiated_at' => 'datetime',
        'last_tracking_synced_at' => 'datetime',
        'handed_over_at' => 'datetime',
        'delivered_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_READY_FOR_PICKUP,
            self::STATUS_HANDED_TO_CARRIER,
            self::STATUS_IN_TRANSIT,
            self::STATUS_OUT_FOR_DELIVERY,
            self::STATUS_DELIVERED,
            self::STATUS_DELIVERY_FAILED,
            self::STATUS_RETURNED,
            self::STATUS_CANCELED,
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_READY_FOR_PICKUP => 'Ready for Pickup',
            self::STATUS_HANDED_TO_CARRIER => 'Handed to Carrier',
            self::STATUS_IN_TRANSIT => 'In Transit',
            self::STATUS_OUT_FOR_DELIVERY => 'Out for Delivery',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_DELIVERY_FAILED => 'Delivery Failed',
            self::STATUS_RETURNED => 'Returned',
            self::STATUS_CANCELED => 'Canceled',
        ];
    }

    public static function failureReasons(): array
    {
        return [
            self::FAILURE_REASON_CUSTOMER_UNREACHABLE,
            self::FAILURE_REASON_CUSTOMER_REFUSED,
            self::FAILURE_REASON_WRONG_ADDRESS,
            self::FAILURE_REASON_AREA_UNREACHABLE,
            self::FAILURE_REASON_RESCHEDULED_BY_CUSTOMER,
            self::FAILURE_REASON_PARCEL_DAMAGED,
            self::FAILURE_REASON_OTHER,
        ];
    }

    public static function failureReasonLabels(): array
    {
        return [
            self::FAILURE_REASON_CUSTOMER_UNREACHABLE => 'Customer Unreachable',
            self::FAILURE_REASON_CUSTOMER_REFUSED => 'Customer Refused',
            self::FAILURE_REASON_WRONG_ADDRESS => 'Wrong Address',
            self::FAILURE_REASON_AREA_UNREACHABLE => 'Area Unreachable',
            self::FAILURE_REASON_RESCHEDULED_BY_CUSTOMER => 'Rescheduled by Customer',
            self::FAILURE_REASON_PARCEL_DAMAGED => 'Parcel Damaged',
            self::FAILURE_REASON_OTHER => 'Other',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return static::statusLabels()[$this->status] ?? str($this->status)->replace('_', ' ')->title()->value();
    }

    public function getDeliveryFailureReasonLabelAttribute(): ?string
    {
        if (! $this->delivery_failure_reason) {
            return null;
        }

        return static::failureReasonLabels()[$this->delivery_failure_reason]
            ?? str($this->delivery_failure_reason)->replace('_', ' ')->title()->value();
    }

    public function trackingUrl(): ?string
    {
        return $this->public_tracking_url ?: $this->carrier?->trackingUrl($this->tracking_number);
    }

    public function canBeMarkedDelivered(): bool
    {
        return ! in_array($this->status, [
            self::STATUS_DELIVERED,
            self::STATUS_RETURNED,
            self::STATUS_CANCELED,
        ], true);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function nativeShipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'native_shipment_id');
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(ShipmentCarrier::class, 'shipment_carrier_id');
    }

    public function inventorySource(): BelongsTo
    {
        return $this->belongsTo(InventorySource::class, 'inventory_source_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShipmentRecordItem::class)->orderBy('id');
    }

    public function codSettlement(): HasOne
    {
        return $this->hasOne(CodSettlement::class);
    }

    public function communications(): HasMany
    {
        return $this->hasMany(ShipmentCommunication::class)->latest('id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ShipmentEvent::class)->orderByDesc('event_at')->orderByDesc('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by_admin_id');
    }
}
