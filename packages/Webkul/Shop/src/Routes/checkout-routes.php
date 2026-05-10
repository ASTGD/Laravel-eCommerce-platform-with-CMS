<?php

use Illuminate\Support\Facades\Route;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;
use Webkul\Shop\Http\Controllers\CartController;
use Webkul\Shop\Http\Controllers\OnepageController;

/**
 * Cart routes.
 */
Route::controller(CartController::class)->prefix('checkout/cart')->group(function () {
    Route::get('', 'index')->middleware(NoCacheMiddleware::class)->name('shop.checkout.cart.index');
});

Route::controller(OnepageController::class)->prefix('checkout/onepage')->group(function () {
    Route::get('', 'index')->middleware(NoCacheMiddleware::class)->name('shop.checkout.onepage.index');

    Route::get('success', 'success')->middleware(NoCacheMiddleware::class)->name('shop.checkout.onepage.success');
});
