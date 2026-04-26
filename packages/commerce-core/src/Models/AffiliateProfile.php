<?php

namespace Platform\CommerceCore\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Customer\Models\CustomerProxy;
use Webkul\User\Models\AdminProxy;

class AffiliateProfile extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'customer_id',
        'status',
        'referral_code',
        'application_source',
        'application_note',
        'website_url',
        'social_profiles',
        'payout_method',
        'payout_reference',
        'terms_accepted_at',
        'approved_at',
        'approved_by_admin_id',
        'rejected_at',
        'rejected_by_admin_id',
        'rejection_reason',
        'suspended_at',
        'suspended_by_admin_id',
        'suspension_reason',
        'reactivated_at',
        'last_status_changed_at',
        'meta',
    ];

    protected $casts = [
        'social_profiles' => 'array',
        'meta' => 'array',
        'terms_accepted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'suspended_at' => 'datetime',
        'reactivated_at' => 'datetime',
        'last_status_changed_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_ACTIVE,
            self::STATUS_SUSPENDED,
            self::STATUS_REJECTED,
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_SUSPENDED => 'Suspended',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerProxy::modelClass(), 'customer_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'approved_by_admin_id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'rejected_by_admin_id');
    }

    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'suspended_by_admin_id');
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(AffiliateClick::class);
    }

    public function attributions(): HasMany
    {
        return $this->hasMany(AffiliateOrderAttribution::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(AffiliatePayout::class);
    }

    public function payoutAllocations(): HasMany
    {
        return $this->hasMany(AffiliatePayoutCommissionAllocation::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getStatusLabelAttribute(): string
    {
        return static::statusLabels()[$this->status] ?? str($this->status)->replace('_', ' ')->title()->value();
    }

    public function referralUrl(?string $path = null): string
    {
        $target = $path ?: '/';

        return url($target).(str_contains($target, '?') ? '&' : '?').'ref='.$this->referral_code;
    }

    public function getReferralUrlAttribute(): string
    {
        return $this->referralUrl();
    }
}
