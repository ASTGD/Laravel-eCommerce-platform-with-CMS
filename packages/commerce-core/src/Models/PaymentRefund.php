<?php

namespace Platform\CommerceCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Sales\Models\OrderProxy;
use Webkul\Sales\Models\RefundProxy;

class PaymentRefund extends Model
{
    protected $fillable = [
        'payment_attempt_id',
        'order_id',
        'refund_id',
        'provider',
        'method_code',
        'merchant_tran_id',
        'gateway_tran_id',
        'gateway_refund_ref',
        'gateway_bank_tran_id',
        'requested_amount',
        'currency',
        'reason',
        'status',
        'gateway_status',
        'requested_by_admin_id',
        'requested_at',
        'last_checked_at',
        'processed_at',
        'failed_at',
        'last_error',
        'meta',
        'last_payload',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:4',
        'requested_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
        'meta' => 'array',
        'last_payload' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderProxy::modelClass(), 'order_id');
    }

    public function paymentAttempt(): BelongsTo
    {
        return $this->belongsTo(PaymentAttempt::class, 'payment_attempt_id');
    }

    public function refund(): BelongsTo
    {
        return $this->belongsTo(RefundProxy::modelClass(), 'refund_id');
    }
}
