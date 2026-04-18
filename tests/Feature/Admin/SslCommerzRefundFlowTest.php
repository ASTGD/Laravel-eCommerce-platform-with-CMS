<?php

use Illuminate\Support\Facades\Http;
use Platform\CommerceCore\Models\PaymentAttempt;
use Platform\CommerceCore\Models\PaymentRefund;
use Webkul\Admin\Tests\AdminTestCase;
use Webkul\Core\Models\CoreConfig;
use Webkul\Payment\Tests\Concerns\ProvidePaymentHelpers;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\Refund;

use function Pest\Laravel\from;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(AdminTestCase::class);
uses(ProvidePaymentHelpers::class);

function configureSslRefundGateway(): void
{
    foreach ([
        'sales.payment_methods.cashondelivery.active' => 1,
        'sales.payment_methods.sslcommerz_gateway.sandbox' => 1,
        'sales.payment_methods.sslcommerz_gateway.store_id' => 'test_store',
        'sales.payment_methods.sslcommerz_gateway.store_password' => 'test_password',
        'sales.payment_methods.sslcommerz_gateway.request_timeout' => 30,
        'sales.payment_methods.sslcommerz_gateway.strict_amount_validation' => 1,
        'sales.payment_methods.sslcommerz_gateway.log_payloads' => 1,
        'sales.payment_methods.sslcommerz.active' => 1,
    ] as $code => $value) {
        CoreConfig::query()->updateOrCreate(
            ['code' => $code, 'channel_code' => 'default'],
            ['value' => (string) $value],
        );
    }
}

function sslSoapJsonResponse(array $payload, string $operation): string
{
    return sprintf(
        '<?xml version="1.0" encoding="UTF-8"?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"><soapenv:Body><ns1:%1$sResponse xmlns:ns1="urn:validationquote"><return>%2$s</return></ns1:%1$sResponse></soapenv:Body></soapenv:Envelope>',
        $operation,
        htmlspecialchars(json_encode($payload, JSON_THROW_ON_ERROR), ENT_XML1)
    );
}

function createPaidSslOrderForRefundTest($test, string $method = 'sslcommerz'): Order
{
    $cart = $test->createCartWithItems($method, [
        'base_currency_code' => 'BDT',
        'channel_currency_code' => 'BDT',
        'cart_currency_code' => 'BDT',
    ]);

    Http::fake([
        'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php*' => Http::response([
            'status' => 'VALID',
            'tran_id' => "cart_{$cart->id}_REFUND",
            'bank_tran_id' => "bank-tran-{$cart->id}",
            'val_id' => "val-{$cart->id}",
            'value_a' => (string) $cart->id,
            'value_b' => $method,
            'currency_type' => 'BDT',
            'amount' => (string) $cart->base_grand_total,
        ], 200),
    ]);

    get(route('commerce-core.sslcommerz.success', [
        'code' => $method,
        'val_id' => "val-{$cart->id}",
        'tran_id' => "cart_{$cart->id}_REFUND",
        'value_a' => $cart->id,
        'value_b' => $method,
    ]))->assertRedirect(route('shop.checkout.success', ['order' => Order::query()->where('cart_id', $cart->id)->firstOrFail()->id]));

    return Order::query()->where('cart_id', $cart->id)->firstOrFail();
}

beforeEach(function () {
    configureSslRefundGateway();
});

it('creates an sslcommerz payment refund when the admin refund succeeds', function () {
    $order = createPaidSslOrderForRefundTest($this);
    $item = $order->items()->firstOrFail();

    Http::fake(function ($request) {
        if (($request->header('SOAPAction')[0] ?? null) === 'urn:validationquote#initiateRefund') {
            return Http::response(sslSoapJsonResponse([
                'APIConnect' => 'DONE',
                'status' => 'SUCCESS',
                'refund_ref_id' => 'refund-ref-001',
                'bank_tran_id' => 'refund-bank-001',
            ], 'initiateRefund'), 200, ['Content-Type' => 'text/xml']);
        }

        throw new RuntimeException('Unexpected HTTP request: '.$request->url());
    });

    $this->loginAsAdmin();

    post(route('admin.sales.refunds.store', $order->id), [
        'refund' => [
            'items' => [
                $item->id => 1,
            ],
            'shipping' => '0',
            'adjustment_refund' => '0',
            'adjustment_fee' => '0',
            'reason' => 'Customer requested a refund',
        ],
    ])
        ->assertRedirect(route('admin.sales.orders.view', $order->id))
        ->assertSessionHas('success');

    $refund = Refund::query()->where('order_id', $order->id)->firstOrFail();
    $paymentRefund = PaymentRefund::query()->where('refund_id', $refund->id)->firstOrFail();
    $attempt = PaymentAttempt::query()->where('order_id', $order->id)->latest('id')->firstOrFail();

    expect($paymentRefund->payment_attempt_id)->toBe($attempt->id)
        ->and($paymentRefund->status)->toBe('refunded')
        ->and($paymentRefund->gateway_refund_ref)->toBe('refund-ref-001')
        ->and($paymentRefund->reason)->toBe('Customer requested a refund');

    $html = view('commerce-core::admin.orders.payment-details', [
        'order' => $order->fresh(),
    ])->render();

    expect($html)->toContain('Refund History')
        ->toContain('refund-ref-001');
});

it('rolls back the local refund when the sslcommerz gateway rejects it', function () {
    $order = createPaidSslOrderForRefundTest($this, 'sslcommerz_bkash');
    $item = $order->items()->firstOrFail();

    Http::fake(function ($request) {
        if (($request->header('SOAPAction')[0] ?? null) === 'urn:validationquote#initiateRefund') {
            return Http::response(sslSoapJsonResponse([
                'APIConnect' => 'FAILED',
                'status' => 'FAILED',
                'errorReason' => 'Refund window expired',
            ], 'initiateRefund'), 200, ['Content-Type' => 'text/xml']);
        }

        throw new RuntimeException('Unexpected HTTP request: '.$request->url());
    });

    $this->loginAsAdmin();

    from(route('admin.sales.orders.view', $order->id))
        ->post(route('admin.sales.refunds.store', $order->id), [
            'refund' => [
                'items' => [
                    $item->id => 1,
                ],
                'shipping' => '0',
                'adjustment_refund' => '0',
                'adjustment_fee' => '0',
            ],
        ])
        ->assertRedirect(route('admin.sales.orders.view', $order->id))
        ->assertSessionHas('error', 'Refund window expired');

    expect(Refund::query()->where('order_id', $order->id)->count())->toBe(0)
        ->and(PaymentRefund::query()->where('order_id', $order->id)->count())->toBe(0);
});

it('refreshes a pending sslcommerz refund status from the admin order view', function () {
    $order = createPaidSslOrderForRefundTest($this);
    $item = $order->items()->firstOrFail();

    Http::fake(function ($request) {
        $soapAction = $request->header('SOAPAction')[0] ?? null;

        if ($soapAction === 'urn:validationquote#initiateRefund') {
            return Http::response(sslSoapJsonResponse([
                'APIConnect' => 'DONE',
                'refund_ref_id' => 'refund-ref-pending',
            ], 'initiateRefund'), 200, ['Content-Type' => 'text/xml']);
        }

        if ($soapAction === 'urn:validationquote#inquiryRefund') {
            return Http::response(sslSoapJsonResponse([
                'APIConnect' => 'DONE',
                'status' => 'SUCCESS',
                'refund_ref_id' => 'refund-ref-pending',
                'bank_tran_id' => 'refund-bank-002',
            ], 'inquiryRefund'), 200, ['Content-Type' => 'text/xml']);
        }

        throw new RuntimeException('Unexpected HTTP request: '.$request->url());
    });

    $this->loginAsAdmin();

    post(route('admin.sales.refunds.store', $order->id), [
        'refund' => [
            'items' => [
                $item->id => 1,
            ],
            'shipping' => '0',
            'adjustment_refund' => '0',
            'adjustment_fee' => '0',
        ],
    ])->assertRedirect(route('admin.sales.orders.view', $order->id));

    $paymentRefund = PaymentRefund::query()->where('order_id', $order->id)->firstOrFail();

    expect($paymentRefund->status)->toBe('pending');

    post(route('admin.sales.orders.payment_refunds.refresh', $paymentRefund))
        ->assertRedirect(route('admin.sales.orders.view', $order->id))
        ->assertSessionHas('success', 'Refund status refreshed successfully.');

    $paymentRefund->refresh();

    expect($paymentRefund->status)->toBe('refunded')
        ->and($paymentRefund->gateway_bank_tran_id)->toBe('refund-bank-002')
        ->and($paymentRefund->processed_at)->not->toBeNull();
});
