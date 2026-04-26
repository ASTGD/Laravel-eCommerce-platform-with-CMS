<?php

namespace Platform\CommerceCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Customer\Models\CustomerProxy;

class AffiliateClick extends Model
{
    protected $fillable = [
        'affiliate_profile_id',
        'customer_id',
        'referral_code',
        'session_id',
        'ip_address',
        'user_agent',
        'landing_url',
        'referrer_url',
        'clicked_at',
        'meta',
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
        'meta' => 'array',
    ];

    public function affiliateProfile(): BelongsTo
    {
        return $this->belongsTo(AffiliateProfile::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerProxy::modelClass(), 'customer_id');
    }

    public function attributions(): HasMany
    {
        return $this->hasMany(AffiliateOrderAttribution::class);
    }
}
