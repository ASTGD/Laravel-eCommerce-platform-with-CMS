<?php

declare(strict_types=1);

use ExperienceCms\Http\Controllers\Storefront\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'home'])->name('storefront.home');
Route::get('/pages/{slug}', [StorefrontController::class, 'show'])->name('storefront.pages.show');
