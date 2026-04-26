<?php

namespace Platform\CommerceCore\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Sales\Models\OrderProxy;

class AffiliateCommission extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REVERSED = 'reversed';

    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'affiliate_profile_id',
        'affiliate_order_attribution_id',
        'affiliate_payout_id',
        'order_id',
        'status',
        'commission_type',
        'commission_rate',
        'order_amount',
        'commission_amount',
        'currency',
        'eligible_at',
        'approved_at',
        'reversed_at',
        'paid_at',
        'reversal_reason',
        'meta',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:4',
        'order_amount' => 'decimal:4',
        'commission_amount' => 'decimal:4',
        'eligible_at' => 'datetime',
        'approved_at' => 'datetime',
        'reversed_at' => 'datetime',
        'paid_at' => 'datetime',
        'meta' => 'array',
    ];

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REVERSED => 'Reversed',
            self::STATUS_PAID => 'Paid',
        ];
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function affiliateProfile(): BelongsTo
    {
        return $this->belongsTo(AffiliateProfile::class);
    }

    public function attribution(): BelongsTo
    {
        return $this->belongsTo(AffiliateOrderAttribution::class, 'affiliate_order_attribution_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderProxy::modelClass(), 'order_id');
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(AffiliatePayout::class, 'affiliate_payout_id');
    }

    public function payoutAllocations(): HasMany
    {
        return $this->hasMany(AffiliatePayoutCommissionAllocation::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return static::statusLabels()[$this->status] ?? str($this->status)->replace('_', ' ')->title()->value();
    }
}
