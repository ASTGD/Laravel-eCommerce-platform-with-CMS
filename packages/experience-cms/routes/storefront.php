<?php

use Illuminate\Support\Facades\Route;
use Platform\ExperienceCms\Http\Controllers\StorefrontPageController;

Route::controller(StorefrontPageController::class)->group(function () {
    Route::get('/pages/{platformPage:slug}', 'show')->name('platform.storefront.pages.show');
    Route::middleware('signed')->group(function () {
        Route::get('/preview/pages/{platformPage:slug}', 'preview')->name('platform.storefront.pages.preview');
        Route::get('/home-preview', 'homePreview')->name('platform.storefront.home_preview');
        Route::get('/preview/category-pages/{platformPage:slug}/{categorySlug}', 'previewCategory')->name('platform.storefront.category-pages.preview');
        Route::get('/preview/product-pages/{platformPage:slug}/{productSlug}', 'previewProduct')->name('platform.storefront.product-pages.preview');
    });
});
