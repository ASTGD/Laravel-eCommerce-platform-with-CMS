<?php

namespace Platform\CommerceCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\ShipmentItem;

class ShipmentRecordItem extends Model
{
    protected $fillable = [
        'shipment_record_id',
        'order_item_id',
        'native_shipment_item_id',
        'name',
        'sku',
        'qty',
        'weight',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'weight' => 'decimal:4',
    ];

    public function shipmentRecord(): BelongsTo
    {
        return $this->belongsTo(ShipmentRecord::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function nativeShipmentItem(): BelongsTo
    {
        return $this->belongsTo(ShipmentItem::class, 'native_shipment_item_id');
    }
}
