<?php

use Illuminate\Support\Facades\Route;
use Platform\CommerceCore\Http\Controllers\SslCommerzController;

Route::controller(SslCommerzController::class)
    ->prefix('payment/sslcommerz/{code}')
    ->group(function () {
        Route::get('redirect', 'redirect')->name('commerce-core.sslcommerz.redirect');
        Route::match(['get', 'post'], 'success', 'success')->name('commerce-core.sslcommerz.success');
        Route::match(['get', 'post'], 'fail', 'fail')->name('commerce-core.sslcommerz.fail');
        Route::match(['get', 'post'], 'cancel', 'cancel')->name('commerce-core.sslcommerz.cancel');
        Route::post('ipn', 'ipn')->name('commerce-core.sslcommerz.ipn');
    });
