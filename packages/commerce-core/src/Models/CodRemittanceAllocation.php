<?php

namespace Platform\CommerceCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Sales\Models\Order;

class CodRemittanceAllocation extends Model
{
    public const STATUS_ALLOCATED = 'allocated';

    public const STATUS_SETTLED = 'settled';

    protected $fillable = [
        'cod_remittance_id',
        'cod_settlement_id',
        'order_id',
        'shipment_record_id',
        'allocated_amount',
        'status',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
    ];

    public static function statusLabels(): array
    {
        return [
            self::STATUS_ALLOCATED => 'Allocated',
            self::STATUS_SETTLED => 'Settled',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return static::statusLabels()[$this->status] ?? str($this->status)->replace('_', ' ')->title()->value();
    }

    public function remittance(): BelongsTo
    {
        return $this->belongsTo(CodRemittance::class, 'cod_remittance_id');
    }

    public function codSettlement(): BelongsTo
    {
        return $this->belongsTo(CodSettlement::class, 'cod_settlement_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function shipmentRecord(): BelongsTo
    {
        return $this->belongsTo(ShipmentRecord::class);
    }
}
