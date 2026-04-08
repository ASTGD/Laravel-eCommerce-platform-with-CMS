<?php

use Illuminate\Support\Facades\Route;
use Platform\ExperienceCms\Http\Controllers\StorefrontPageController;

Route::controller(StorefrontPageController::class)->group(function () {
    Route::get('/pages/{page:slug}', 'show')->name('platform.storefront.pages.show');
    Route::get('/preview/pages/{page:slug}', 'preview')->name('platform.storefront.pages.preview');
    Route::get('/home-preview', 'home')->name('platform.storefront.home_preview');
});
