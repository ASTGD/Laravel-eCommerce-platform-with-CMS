<?php

use Mockery\MockInterface;
use Platform\CommerceCore\Models\PaymentAttempt;
use Platform\CommerceCore\Services\PaymentReconciliationService;
use Platform\CommerceCore\Services\PendingPaymentExpiryService;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\OrderPayment;

use function Pest\Laravel\mock;

function createPendingPaymentOrderForExpiry(string $method = 'sslcommerz'): Order
{
    $order = Order::factory()->create([
        'status' => Order::STATUS_PENDING_PAYMENT,
        'grand_total_invoiced' => 0,
        'base_grand_total_invoiced' => 0,
        'sub_total_invoiced' => 0,
        'base_sub_total_invoiced' => 0,
        'grand_total_refunded' => 0,
        'base_grand_total_refunded' => 0,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'qty_ordered' => 1,
        'qty_invoiced' => 0,
        'qty_canceled' => 0,
        'qty_refunded' => 0,
    ]);

    OrderPayment::factory()->create([
        'order_id' => $order->id,
        'method' => $method,
        'method_title' => $method,
    ]);

    return $order->fresh(['items', 'payment']);
}

function createPaymentAttemptForExpiry(Order $order, string $status = 'redirected'): PaymentAttempt
{
    $attempt = PaymentAttempt::query()->create([
        'order_id' => $order->id,
        'cart_id' => $order->cart_id ?: null,
        'customer_id' => $order->customer_id,
        'provider' => 'sslcommerz',
        'method_code' => 'sslcommerz',
        'merchant_tran_id' => 'expiry-test-'.$order->id.'-'.$status,
        'currency' => 'BDT',
        'amount' => (float) $order->base_grand_total,
        'status' => $status,
    ]);

    $attempt->forceFill([
        'created_at' => now()->subMinutes(90),
        'updated_at' => now()->subMinutes(90),
    ])->save();

    return $attempt;
}

it('expires stale online payment attempts and cancels linked pending payment orders', function () {
    $order = createPendingPaymentOrderForExpiry();
    $attempt = createPaymentAttemptForExpiry($order);

    mock(PaymentReconciliationService::class, function (MockInterface $mock): void {
        $mock
            ->shouldReceive('reconcile')
            ->once()
            ->andReturnUsing(fn (PaymentAttempt $attempt): PaymentAttempt => $attempt);
    });

    $result = app(PendingPaymentExpiryService::class)->expirePending(
        limit: 10,
        expireAfterMinutes: 60,
        reconcileOlderThanMinutes: 0,
    );

    expect($result['processed'])->toBe(1)
        ->and($result['reconciled'])->toBe(1)
        ->and($result['expired_attempts'])->toBe(1)
        ->and($result['cancelled_orders'])->toBe(1)
        ->and($result['errors'])->toBe(0);

    $attempt->refresh();
    $order->refresh();

    expect($attempt->status)->toBe('expired')
        ->and($attempt->validation_status)->toBe('EXPIRED')
        ->and($attempt->last_reconciled_via)->toBe('scheduled_expiry')
        ->and($order->status)->toBe(Order::STATUS_CANCELED)
        ->and((float) $order->items()->first()->qty_canceled)->toBe(1.0);
});

it('cancels terminal failed pending payment orders without rewriting the gateway failure status', function () {
    $order = createPendingPaymentOrderForExpiry();
    $attempt = createPaymentAttemptForExpiry($order, 'failed');

    $result = app(PendingPaymentExpiryService::class)->expirePending(
        limit: 10,
        expireAfterMinutes: 60,
    );

    expect($result['processed'])->toBe(1)
        ->and($result['reconciled'])->toBe(0)
        ->and($result['expired_attempts'])->toBe(1)
        ->and($result['cancelled_orders'])->toBe(1)
        ->and($result['errors'])->toBe(0);

    $attempt->refresh();
    $order->refresh();

    expect($attempt->status)->toBe('failed')
        ->and($attempt->last_reconciled_via)->toBe('scheduled_expiry')
        ->and($attempt->meta['pending_payment_expiry']['reason'])->toBe('terminal_non_paid')
        ->and($order->status)->toBe(Order::STATUS_CANCELED);
});
