<?php

namespace Platform\CommerceCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettlementBatchItem extends Model
{
    protected $fillable = [
        'settlement_batch_id',
        'cod_settlement_id',
        'expected_amount',
        'remitted_amount',
        'adjustment_amount',
        'short_amount',
        'note',
    ];

    protected $casts = [
        'expected_amount' => 'decimal:2',
        'remitted_amount' => 'decimal:2',
        'adjustment_amount' => 'decimal:2',
        'short_amount' => 'decimal:2',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(SettlementBatch::class, 'settlement_batch_id');
    }

    public function codSettlement(): BelongsTo
    {
        return $this->belongsTo(CodSettlement::class);
    }
}
