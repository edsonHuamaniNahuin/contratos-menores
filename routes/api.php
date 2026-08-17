<?php

use App\Http\Controllers\Api\ContratosMayoresController;
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

    // API de Contratos Mayores (SEACE 3.0) para sistemas del cliente
    Route::get('/contratos-mayores', [ContratosMayoresController::class, 'index']);
    Route::get('/contratos-mayores/geografia', [ContratosMayoresController::class, 'geografia']);
    Route::get('/contratos-mayores/{ocid}', [ContratosMayoresController::class, 'show']);
});

// Webhook de Openpay (sin autenticación, firma verificada en controller)
Route::post('/webhooks/openpay', [SubscriptionController::class, 'webhookOpenpay'])
    ->name('webhooks.openpay');

// Webhook de MercadoPago (sin autenticación, firma verificada en controller)
Route::post('/webhooks/mercadopago', [SubscriptionController::class, 'webhookMercadoPago'])
    ->name('webhooks.mercadopago');

// Webhooks de WhatsApp Business Cloud API (sin autenticación, token verificado en controller)
Route::get('/webhooks/whatsapp', [WebhookWhatsAppController::class, 'verify'])
    ->name('webhooks.whatsapp.verify');
Route::post('/webhooks/whatsapp', [WebhookWhatsAppController::class, 'handle'])
    ->name('webhooks.whatsapp.handle');
