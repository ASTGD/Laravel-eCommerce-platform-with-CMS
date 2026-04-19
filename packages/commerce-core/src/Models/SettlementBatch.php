<?php

namespace Platform\CommerceCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\User\Models\Admin;

class SettlementBatch extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_REMITTED = 'remitted';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_RECONCILED = 'reconciled';
    public const STATUS_DISPUTED = 'disputed';

    protected $fillable = [
        'shipment_carrier_id',
        'created_by_admin_id',
        'updated_by_admin_id',
        'reference',
        'payout_method',
        'status',
        'gross_expected_amount',
        'gross_remitted_amount',
        'total_adjustment_amount',
        'total_short_amount',
        'total_deductions_amount',
        'net_amount',
        'remitted_at',
        'received_at',
        'notes',
    ];

    protected $casts = [
        'gross_expected_amount' => 'decimal:2',
        'gross_remitted_amount' => 'decimal:2',
        'total_adjustment_amount' => 'decimal:2',
        'total_short_amount' => 'decimal:2',
        'total_deductions_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'remitted_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_REMITTED,
            self::STATUS_RECEIVED,
            self::STATUS_RECONCILED,
            self::STATUS_DISPUTED,
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_REMITTED => 'Remitted',
            self::STATUS_RECEIVED => 'Received',
            self::STATUS_RECONCILED => 'Reconciled',
            self::STATUS_DISPUTED => 'Disputed',
        ];
    }

    public function getReconciliationGapAmountAttribute(): float
    {
        return max(
            0,
            (float) $this->gross_expected_amount
                - (float) $this->gross_remitted_amount
                - (float) $this->total_adjustment_amount
        );
    }

    public function getStatusLabelAttribute(): string
    {
        return static::statusLabels()[$this->status] ?? str($this->status)->replace('_', ' ')->title()->value();
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(ShipmentCarrier::class, 'shipment_carrier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SettlementBatchItem::class)->orderBy('id');
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
