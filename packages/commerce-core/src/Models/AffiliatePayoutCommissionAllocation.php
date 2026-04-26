<?php

namespace Platform\CommerceCore\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliatePayoutCommissionAllocation extends Model
{
    public const STATUS_RESERVED = 'reserved';

    public const STATUS_PAID = 'paid';

    public const STATUS_RELEASED = 'released';

    protected $fillable = [
        'affiliate_payout_id',
        'affiliate_commission_id',
        'affiliate_profile_id',
        'status',
        'amount',
        'paid_at',
        'released_at',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'paid_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_RESERVED,
            self::STATUS_PAID,
        ]);
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(AffiliatePayout::class, 'affiliate_payout_id');
    }

    public function commission(): BelongsTo
    {
        return $this->belongsTo(AffiliateCommission::class, 'affiliate_commission_id');
    }

    public function affiliateProfile(): BelongsTo
    {
        return $this->belongsTo(AffiliateProfile::class);
    }
}
