<?php

use Illuminate\Support\Facades\Http;
use Platform\CommerceCore\Models\PaymentAttempt;
use Platform\CommerceCore\Models\PaymentGatewayEvent;
use Webkul\Admin\Tests\AdminTestCase;
use Webkul\Core\Models\CoreConfig;
use Webkul\Payment\Tests\Concerns\ProvidePaymentHelpers;
use Webkul\Sales\Models\Invoice;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderTransaction;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(AdminTestCase::class);
uses(ProvidePaymentHelpers::class);

function configurePaymentOperationsSslCommerz(): void
{
    foreach ([
        'sales.payment_methods.mode.channel' => 'custom',
        'sales.payment_methods.cashondelivery.active' => 1,
        'sales.payment_methods.sslcommerz_gateway.sandbox' => 1,
        'sales.payment_methods.sslcommerz_gateway.store_id' => 'test_store',
        'sales.payment_methods.sslcommerz_gateway.store_password' => 'test_password',
        'sales.payment_methods.sslcommerz_gateway.request_timeout' => 30,
        'sales.payment_methods.sslcommerz_gateway.strict_amount_validation' => 1,
        'sales.payment_methods.sslcommerz_gateway.log_payloads' => 1,
        'sales.payment_methods.sslcommerz_card.active' => 1,
        'sales.payment_methods.sslcommerz_bkash.active' => 1,
        'sales.payment_methods.sslcommerz_nagad.active' => 1,
    ] as $code => $value) {
        CoreConfig::query()->updateOrCreate(
            [
                'code' => $code,
                'channel_code' => 'default',
            ],
            [
                'value' => (string) $value,
            ],
        );
    }
}

beforeEach(function () {
    configurePaymentOperationsSslCommerz();
});

it('shows the admin payments index', function () {
    $this->loginAsAdmin();

    get(route('admin.sales.payments.index'))
        ->assertOk()
        ->assertSeeText('Payments');
});

it('shows the payment attempt detail page with gateway events', function () {
    $attempt = PaymentAttempt::query()->create([
        'provider' => 'sslcommerz',
        'method_code' => 'sslcommerz_card',
        'merchant_tran_id' => 'attempt-view-001',
        'currency' => 'BDT',
        'amount' => 1500,
        'status' => 'redirected',
        'validation_status' => 'PENDING',
    ]);

    PaymentGatewayEvent::query()->create([
        'payment_attempt_id' => $attempt->id,
        'provider' => 'sslcommerz',
        'event_type' => 'manual_reconcile',
        'payload' => ['tran_id' => 'attempt-view-001'],
        'result' => 'processed',
        'notes' => 'Validation completed.',
        'received_at' => now(),
        'processed_at' => now(),
    ]);

    $this->loginAsAdmin();

    get(route('admin.sales.payments.view', $attempt))
        ->assertOk()
        ->assertSeeText('Payment Attempt #'.$attempt->id)
        ->assertSeeText('attempt-view-001')
        ->assertSeeText('manual_reconcile')
        ->assertSeeText('Validation completed.');
});

it('reconciles a payment attempt from admin and stays idempotent on repeat runs', function () {
    $cart = $this->createCartWithItems('sslcommerz_card', [
        'base_currency_code' => 'BDT',
        'channel_currency_code' => 'BDT',
        'cart_currency_code' => 'BDT',
    ]);

    $attempt = PaymentAttempt::query()->create([
        'cart_id' => $cart->id,
        'customer_id' => $cart->customer_id,
        'provider' => 'sslcommerz',
        'method_code' => 'sslcommerz_card',
        'merchant_tran_id' => "cart_{$cart->id}_ADMIN_RECONCILE",
        'currency' => 'BDT',
        'amount' => (float) $cart->base_grand_total,
        'status' => 'redirected',
    ]);

    Http::fake([
        'https://sandbox.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php*' => Http::response([
            'status' => 'VALID',
            'tran_id' => $attempt->merchant_tran_id,
            'bank_tran_id' => 'bank-admin-reconcile',
            'value_a' => (string) $cart->id,
            'value_b' => 'sslcommerz_card',
            'currency_type' => 'BDT',
            'amount' => (string) $cart->base_grand_total,
        ], 200),
    ]);

    $this->loginAsAdmin();

    post(route('admin.sales.payments.reconcile', $attempt))
        ->assertRedirect(route('admin.sales.payments.view', $attempt))
        ->assertSessionHas('success', 'Payment reconciliation completed.');

    $order = Order::query()->where('cart_id', $cart->id)->first();

    expect($order)->not->toBeNull()
        ->and(Invoice::query()->where('order_id', $order->id)->count())->toBe(1)
        ->and(OrderTransaction::query()->where('transaction_id', 'bank-admin-reconcile')->count())->toBe(1);

    $attempt->refresh();

    expect($attempt->status)->toBe('paid')
        ->and($attempt->last_reconciled_via)->toBe('manual_reconcile')
        ->and($attempt->last_reconciled_status)->toBe('VALID');

    get(route('admin.sales.orders.view', $order->id))
        ->assertOk()
        ->assertSeeText('View Payment Attempt')
        ->assertSeeText('Reconcile Payment');

    post(route('admin.sales.orders.payments.reconcile', $order->id))
        ->assertRedirect(route('admin.sales.orders.view', $order->id))
        ->assertSessionHas('success', 'Payment reconciliation completed.');

    expect(Order::query()->where('cart_id', $cart->id)->count())->toBe(1)
        ->and(Invoice::query()->where('order_id', $order->id)->count())->toBe(1)
        ->and(OrderTransaction::query()->where('transaction_id', 'bank-admin-reconcile')->count())->toBe(1)
        ->and(PaymentGatewayEvent::query()->where('payment_attempt_id', $attempt->id)->where('event_type', 'manual_reconcile')->count())->toBe(2);
});

it('reconciles pending sslcommerz attempts through the batch command', function () {
    $cart = $this->createCartWithItems('sslcommerz_bkash', [
        'base_currency_code' => 'BDT',
        'channel_currency_code' => 'BDT',
        'cart_currency_code' => 'BDT',
    ]);

    $attempt = PaymentAttempt::query()->create([
        'cart_id' => $cart->id,
        'customer_id' => $cart->customer_id,
        'provider' => 'sslcommerz',
        'method_code' => 'sslcommerz_bkash',
        'merchant_tran_id' => "cart_{$cart->id}_COMMAND_RECONCILE",
        'currency' => 'BDT',
        'amount' => (float) $cart->base_grand_total,
        'status' => 'redirected',
    ]);

    PaymentAttempt::query()
        ->whereKey($attempt->id)
        ->update([
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);

    Http::fake([
        'https://sandbox.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php*' => Http::response([
            'status' => 'VALID',
            'tran_id' => $attempt->merchant_tran_id,
            'bank_tran_id' => 'bank-command-reconcile',
            'value_a' => (string) $cart->id,
            'value_b' => 'sslcommerz_bkash',
            'currency_type' => 'BDT',
            'amount' => (string) $cart->base_grand_total,
        ], 200),
    ]);

    $this->artisan('platform:payments:reconcile-pending', [
        '--limit' => 10,
        '--older-than' => 5,
    ])
        ->assertExitCode(0);

    $attempt->refresh();

    expect($attempt->status)->toBe('paid')
        ->and($attempt->last_reconciled_via)->toBe('scheduled_reconcile')
        ->and(Order::query()->where('cart_id', $cart->id)->count())->toBe(1)
        ->and(Invoice::query()->where('order_id', $attempt->order_id)->count())->toBe(1)
        ->and(OrderTransaction::query()->where('transaction_id', 'bank-command-reconcile')->count())->toBe(1);
});
