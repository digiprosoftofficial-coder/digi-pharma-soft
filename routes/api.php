<?php

use App\Http\Controllers\Webhooks\StripeBillingWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/v1/user', function (Request $request) {
    return $request->user();
});

Route::post('/webhooks/stripe/billing', StripeBillingWebhookController::class)
    ->name('webhooks.stripe.billing');
