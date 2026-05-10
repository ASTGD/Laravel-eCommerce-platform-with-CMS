<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Platform\CommerceCore\Http\Controllers\PathaoWebhookController;
use Platform\CommerceCore\Http\Controllers\SteadfastWebhookController;

Route::post('webhooks/shipment-carriers/{carrier}/steadfast', [SteadfastWebhookController::class, 'handle'])
    ->middleware('throttle:courier-webhook')
    ->withoutMiddleware(VerifyCsrfToken::class)
    ->name('commerce-core.webhooks.shipment-carriers.steadfast');

Route::post('webhooks/shipment-carriers/{carrier}/pathao', [PathaoWebhookController::class, 'handle'])
    ->middleware('throttle:courier-webhook')
    ->withoutMiddleware(VerifyCsrfToken::class)
    ->name('commerce-core.webhooks.shipment-carriers.pathao');
