<?php

namespace Platform\CommerceCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Webkul\Sales\Models\Order;
use Webkul\User\Models\Admin;

class CodSettlement extends Model
{
    public const STATUS_EXPECTED = 'expected';
    public const STATUS_COLLECTED_BY_CARRIER = 'collected_by_carrier';
    public const STATUS_REMITTED = 'remitted';
    public const STATUS_SETTLED = 'settled';
    public const STATUS_SHORT_SETTLED = 'short_settled';
    public const STATUS_DISPUTED = 'disputed';
    public const STATUS_WRITTEN_OFF = 'written_off';

    protected $fillable = [
        'shipment_record_id',
        'order_id',
        'shipment_carrier_id',
        'created_by_admin_id',
        'updated_by_admin_id',
        'status',
        'expected_amount',
        'collected_amount',
        'remitted_amount',
        'short_amount',
        'disputed_amount',
        'carrier_fee_amount',
        'cod_fee_amount',
        'return_fee_amount',
        'net_amount',
        'collected_at',
        'remitted_at',
        'settled_at',
        'dispute_note',
        'notes',
    ];

    protected $casts = [
        'expected_amount' => 'decimal:2',
        'collected_amount' => 'decimal:2',
        'remitted_amount' => 'decimal:2',
        'short_amount' => 'decimal:2',
        'disputed_amount' => 'decimal:2',
        'carrier_fee_amount' => 'decimal:2',
        'cod_fee_amount' => 'decimal:2',
        'return_fee_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'collected_at' => 'datetime',
        'remitted_at' => 'datetime',
        'settled_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_EXPECTED,
            self::STATUS_COLLECTED_BY_CARRIER,
            self::STATUS_REMITTED,
            self::STATUS_SETTLED,
            self::STATUS_SHORT_SETTLED,
            self::STATUS_DISPUTED,
            self::STATUS_WRITTEN_OFF,
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_EXPECTED => 'Expected',
            self::STATUS_COLLECTED_BY_CARRIER => 'Collected by Carrier',
            self::STATUS_REMITTED => 'Remitted',
            self::STATUS_SETTLED => 'Settled',
            self::STATUS_SHORT_SETTLED => 'Short Settled',
            self::STATUS_DISPUTED => 'Disputed',
            self::STATUS_WRITTEN_OFF => 'Written Off',
        ];
    }

    public static function exceptionStatuses(): array
    {
        return [
            self::STATUS_SHORT_SETTLED,
            self::STATUS_DISPUTED,
            self::STATUS_WRITTEN_OFF,
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return static::statusLabels()[$this->status] ?? str($this->status)->replace('_', ' ')->title()->value();
    }

    public function getOutstandingAmountAttribute(): float
    {
        return max(
            0,
            (float) $this->net_amount
                - (float) $this->remitted_amount
                - (float) $this->disputed_amount
        );
    }

    public function getRequiresAttentionAttribute(): bool
    {
        return in_array($this->status, static::exceptionStatuses(), true)
            || (float) $this->short_amount > 0
            || (float) $this->disputed_amount > 0
            || $this->outstanding_amount > 0;
    }

    public function getHealthLabelAttribute(): string
    {
        if ($this->status === self::STATUS_SETTLED) {
            return 'Settled';
        }

        if ($this->status === self::STATUS_SHORT_SETTLED) {
            return 'Short Settlement';
        }

        if ($this->status === self::STATUS_DISPUTED) {
            return 'Disputed';
        }

        if ($this->status === self::STATUS_WRITTEN_OFF) {
            return 'Written Off';
        }

        if ($this->outstanding_amount > 0) {
            return 'Outstanding Remittance';
        }

        return 'In Progress';
    }

    public function shipmentRecord(): BelongsTo
    {
        return $this->belongsTo(ShipmentRecord::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(ShipmentCarrier::class, 'shipment_carrier_id');
    }

    public function batchItem(): HasOne
    {
        return $this->hasOne(SettlementBatchItem::class);
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
