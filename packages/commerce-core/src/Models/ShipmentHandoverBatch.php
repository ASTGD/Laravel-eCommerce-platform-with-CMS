<?php

namespace Platform\CommerceCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\User\Models\Admin;

class ShipmentHandoverBatch extends Model
{
    public const TYPE_COURIER_PICKUP = 'courier_pickup';

    public const TYPE_STAFF_DROPOFF = 'staff_dropoff';

    protected $fillable = [
        'reference',
        'shipment_carrier_id',
        'created_by_admin_id',
        'updated_by_admin_id',
        'handover_type',
        'handover_at',
        'parcel_count',
        'total_cod_amount',
        'receiver_name',
        'notes',
        'confirmed_at',
    ];

    protected $casts = [
        'handover_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'parcel_count' => 'integer',
        'total_cod_amount' => 'decimal:2',
    ];

    public static function typeLabels(): array
    {
        return [
            self::TYPE_COURIER_PICKUP => 'Courier Pickup',
            self::TYPE_STAFF_DROPOFF => 'Staff Drop-off',
        ];
    }

    public function getHandoverTypeLabelAttribute(): string
    {
        return static::typeLabels()[$this->handover_type]
            ?? str($this->handover_type)->replace('_', ' ')->title()->value();
    }

    public function getIsConfirmedAttribute(): bool
    {
        return $this->confirmed_at !== null;
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(ShipmentCarrier::class, 'shipment_carrier_id');
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(ShipmentRecord::class, 'handover_batch_id')->orderBy('id');
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
