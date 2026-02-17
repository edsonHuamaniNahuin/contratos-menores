<?php

use App\Http\Controllers\Api\TokenController;
use App\Http\Controllers\Api\WebhookWhatsAppController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/token', [TokenController::class, 'store']);
Route::delete('/auth/token', [TokenController::class, 'destroy'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

// Webhook de Openpay (sin autenticación, firma verificada en controller)
Route::post('/webhooks/openpay', [SubscriptionController::class, 'webhook'])
    ->name('webhooks.openpay');

// Webhooks de WhatsApp Business Cloud API (sin autenticación, token verificado en controller)
Route::get('/webhooks/whatsapp', [WebhookWhatsAppController::class, 'verify'])
    ->name('webhooks.whatsapp.verify');
Route::post('/webhooks/whatsapp', [WebhookWhatsAppController::class, 'handle'])
    ->name('webhooks.whatsapp.handle');
