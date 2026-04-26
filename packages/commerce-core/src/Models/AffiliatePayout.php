<?php

namespace Platform\CommerceCore\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Customer\Models\CustomerProxy;
use Webkul\User\Models\AdminProxy;

class AffiliatePayout extends Model
{
    public const STATUS_REQUESTED = 'requested';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PAID = 'paid';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'affiliate_profile_id',
        'requested_by_customer_id',
        'processed_by_admin_id',
        'status',
        'amount',
        'currency',
        'payout_method',
        'payout_reference',
        'requested_at',
        'approved_at',
        'paid_at',
        'rejected_at',
        'rejection_reason',
        'notes',
        'admin_notes',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'rejected_at' => 'datetime',
        'meta' => 'array',
    ];

    public static function statusLabels(): array
    {
        return [
            self::STATUS_REQUESTED => 'Requested',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_PAID => 'Paid',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_REQUESTED,
            self::STATUS_APPROVED,
        ]);
    }

    public function affiliateProfile(): BelongsTo
    {
        return $this->belongsTo(AffiliateProfile::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(CustomerProxy::modelClass(), 'requested_by_customer_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'processed_by_admin_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(AffiliatePayoutCommissionAllocation::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return static::statusLabels()[$this->status] ?? str($this->status)->replace('_', ' ')->title()->value();
    }
}
