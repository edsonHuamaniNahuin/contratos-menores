<?php

use App\Http\Controllers\Api\TokenController;
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
