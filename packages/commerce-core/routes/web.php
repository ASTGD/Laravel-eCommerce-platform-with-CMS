<?php

use Illuminate\Support\Facades\Route;
use Platform\CommerceCore\Http\Controllers\API\OnepageController as CustomOnepageApiController;
use Platform\CommerceCore\Http\Controllers\BkashController;
use Platform\CommerceCore\Http\Controllers\CheckoutController;
use Platform\CommerceCore\Http\Controllers\OnepageController as CustomOnepageController;
use Platform\CommerceCore\Http\Controllers\PublicShipmentTrackingController;
use Platform\CommerceCore\Http\Controllers\Shop\AffiliateController;
use Platform\CommerceCore\Http\Controllers\SslCommerzController;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;

Route::controller(CheckoutController::class)
    ->prefix('checkout')
    ->middleware(NoCacheMiddleware::class)
    ->group(function () {
        Route::get('', 'index')->name('shop.checkout.index');
        Route::get('success', 'success')->name('shop.checkout.success');
    });

Route::controller(CustomOnepageController::class)
    ->prefix('checkout/custom')
    ->middleware(NoCacheMiddleware::class)
    ->group(function () {
        Route::get('', 'index')->name('shop.checkout.custom.index');
        Route::get('success', 'success')->name('shop.checkout.custom.success');
    });

Route::controller(CustomOnepageApiController::class)
    ->prefix('api/checkout/custom')
    ->middleware(NoCacheMiddleware::class)
    ->group(function () {
        Route::get('state', 'state')->name('shop.checkout.custom.state');
        Route::get('summary', 'summary')->name('shop.checkout.custom.summary');
        Route::post('addresses', 'storeAddress')->middleware('throttle:cart-mutation')->name('shop.checkout.custom.addresses.store');
        Route::post('shipping-methods', 'storeShippingMethod')->middleware('throttle:cart-mutation')->name('shop.checkout.custom.shipping_methods.store');
        Route::post('payment-methods', 'storePaymentMethod')->middleware('throttle:cart-mutation')->name('shop.checkout.custom.payment_methods.store');
        Route::post('orders', 'storeOrder')->middleware('throttle:checkout-order')->name('shop.checkout.custom.orders.store');
    });

Route::controller(SslCommerzController::class)
    ->prefix('payment/sslcommerz/{code}')
    ->middleware(['throttle:payment-callback', NoCacheMiddleware::class])
    ->group(function () {
        Route::get('redirect', 'redirect')->name('commerce-core.sslcommerz.redirect');
        Route::match(['get', 'post'], 'success', 'success')->name('commerce-core.sslcommerz.success');
        Route::match(['get', 'post'], 'fail', 'fail')->name('commerce-core.sslcommerz.fail');
        Route::match(['get', 'post'], 'cancel', 'cancel')->name('commerce-core.sslcommerz.cancel');
        Route::post('ipn', 'ipn')->name('commerce-core.sslcommerz.ipn');
    });

Route::controller(BkashController::class)
    ->prefix('payment/bkash/{code}')
    ->middleware(['throttle:payment-callback', NoCacheMiddleware::class])
    ->group(function () {
        Route::get('redirect', 'redirect')->name('commerce-core.bkash.redirect');
        Route::match(['get', 'post'], 'callback', 'callback')->name('commerce-core.bkash.callback');
    });

Route::controller(PublicShipmentTrackingController::class)
    ->prefix('shipment-tracking')
    ->group(function () {
        Route::get('', 'index')->name('shop.shipment-tracking.index');
        Route::post('', 'lookup')->middleware('throttle:shipment-tracking')->name('shop.shipment-tracking.lookup');
    });

Route::get('affiliate-program', [AffiliateController::class, 'program'])->name('shop.affiliate-program.index');
Route::redirect('become-an-affiliate', 'affiliate-program')->name('shop.affiliate-program.redirect');

Route::controller(AffiliateController::class)
    ->prefix('customer/account/affiliate')
    ->middleware([NoCacheMiddleware::class, 'customer'])
    ->group(function () {
        Route::get('', 'index')->name('shop.customers.account.affiliate.index');
        Route::post('apply', 'apply')->middleware('throttle:affiliate-action')->name('shop.customers.account.affiliate.apply');
        Route::post('withdrawals', 'requestWithdrawal')->middleware('throttle:affiliate-action')->name('shop.customers.account.affiliate.withdrawals.store');
    });
