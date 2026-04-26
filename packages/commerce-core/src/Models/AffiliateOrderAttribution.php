<?php

namespace Platform\CommerceCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Sales\Models\OrderProxy;

class AffiliateOrderAttribution extends Model
{
    public const STATUS_ATTRIBUTED = 'attributed';

    public const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'affiliate_profile_id',
        'affiliate_click_id',
        'order_id',
        'referral_code',
        'attribution_source',
        'status',
        'attributed_at',
        'expires_at',
        'meta',
    ];

    protected $casts = [
        'attributed_at' => 'datetime',
        'expires_at' => 'datetime',
        'meta' => 'array',
    ];

    public function affiliateProfile(): BelongsTo
    {
        return $this->belongsTo(AffiliateProfile::class);
    }

    public function click(): BelongsTo
    {
        return $this->belongsTo(AffiliateClick::class, 'affiliate_click_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderProxy::modelClass(), 'order_id');
    }
}
