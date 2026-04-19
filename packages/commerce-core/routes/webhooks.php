<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Platform\CommerceCore\Http\Controllers\SteadfastWebhookController;

Route::post('webhooks/shipment-carriers/{carrier}/steadfast', [SteadfastWebhookController::class, 'handle'])
    ->withoutMiddleware(VerifyCsrfToken::class)
    ->name('commerce-core.webhooks.shipment-carriers.steadfast');
