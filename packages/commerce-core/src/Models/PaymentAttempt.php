<?php

namespace Platform\CommerceCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Checkout\Models\CartProxy;
use Webkul\Customer\Models\CustomerProxy;
use Webkul\Sales\Models\OrderProxy;

class PaymentAttempt extends Model
{
    protected $fillable = [
        'cart_id',
        'order_id',
        'customer_id',
        'provider',
        'method_code',
        'merchant_tran_id',
        'session_key',
        'gateway_tran_id',
        'currency',
        'amount',
        'status',
        'validation_status',
        'finalized_via',
        'finalized_at',
        'last_callback_at',
        'last_ipn_at',
        'callback_count',
        'ipn_count',
        'last_reconciled_at',
        'last_reconciled_status',
        'last_reconciled_via',
        'last_reconcile_error',
        'meta',
        'last_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'callback_count' => 'integer',
        'ipn_count' => 'integer',
        'meta' => 'array',
        'last_payload' => 'array',
        'finalized_at' => 'datetime',
        'last_callback_at' => 'datetime',
        'last_ipn_at' => 'datetime',
        'last_reconciled_at' => 'datetime',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(CartProxy::modelClass(), 'cart_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerProxy::modelClass(), 'customer_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(PaymentGatewayEvent::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(PaymentRefund::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderProxy::modelClass(), 'order_id');
    }
}
