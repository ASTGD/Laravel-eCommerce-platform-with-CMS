<?php

namespace Platform\CommerceCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\User\Models\Admin;

class CodRemittance extends Model
{
    public const STATUS_ALLOCATED = 'allocated';

    public const STATUS_PARTIALLY_ALLOCATED = 'partially_allocated';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'shipment_carrier_id',
        'created_by_admin_id',
        'reference',
        'status',
        'amount_received',
        'allocated_amount',
        'unallocated_amount',
        'received_at',
        'note',
    ];

    protected $casts = [
        'amount_received' => 'decimal:2',
        'allocated_amount' => 'decimal:2',
        'unallocated_amount' => 'decimal:2',
        'received_at' => 'datetime',
    ];

    public static function statusLabels(): array
    {
        return [
            self::STATUS_ALLOCATED => 'Allocated',
            self::STATUS_PARTIALLY_ALLOCATED => 'Partially Allocated',
            self::STATUS_COMPLETED => 'Completed',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return static::statusLabels()[$this->status] ?? str($this->status)->replace('_', ' ')->title()->value();
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(ShipmentCarrier::class, 'shipment_carrier_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(CodRemittanceAllocation::class)->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }
}
