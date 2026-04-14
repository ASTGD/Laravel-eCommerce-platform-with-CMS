<?php

use Illuminate\Support\Facades\Route;
use Platform\CommerceCore\Http\Controllers\Admin\PaymentAttemptController;
use Platform\CommerceCore\Http\Controllers\Admin\PickupPointController;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;

Route::group([
    'middleware' => ['admin', NoCacheMiddleware::class],
    'prefix'     => config('app.admin_url'),
], function () {
    Route::prefix('sales/pickup-points')
        ->controller(PickupPointController::class)
        ->group(function () {
            Route::get('', 'index')->middleware('platform.acl:sales.pickup_points')->name('admin.sales.pickup-points.index');
            Route::get('create', 'create')->middleware('platform.acl:sales.pickup_points.create')->name('admin.sales.pickup-points.create');
            Route::post('', 'store')->middleware('platform.acl:sales.pickup_points.create')->name('admin.sales.pickup-points.store');
            Route::get('{pickupPoint}/edit', 'edit')->middleware('platform.acl:sales.pickup_points.edit')->name('admin.sales.pickup-points.edit');
            Route::put('{pickupPoint}', 'update')->middleware('platform.acl:sales.pickup_points.edit')->name('admin.sales.pickup-points.update');
            Route::delete('{pickupPoint}', 'destroy')->middleware('platform.acl:sales.pickup_points.delete')->name('admin.sales.pickup-points.destroy');
        });

    Route::prefix('sales/payments')
        ->controller(PaymentAttemptController::class)
        ->group(function () {
            Route::get('', 'index')->middleware('platform.acl:sales.payments')->name('admin.sales.payments.index');
            Route::get('{paymentAttempt}', 'show')->middleware('platform.acl:sales.payments.view')->name('admin.sales.payments.view');
            Route::post('{paymentAttempt}/reconcile', 'reconcile')->middleware('platform.acl:sales.payments.reconcile')->name('admin.sales.payments.reconcile');
        });

    Route::post('sales/orders/{order}/payments/reconcile', [PaymentAttemptController::class, 'reconcileOrder'])
        ->middleware('platform.acl:sales.orders.reconcile_payment')
        ->name('admin.sales.orders.payments.reconcile');
});
