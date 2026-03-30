<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ===== PAYMENT WEBHOOKS =====
// Không cần auth — provider gọi trực tiếp, verify bằng signature
Route::post('/webhook/payment/{provider}', [\App\Http\Controllers\Webhook\PaymentWebhookController::class, 'handle'])
    ->name('webhook.payment')
    ->where('provider', 'vnpay|momo|zalopay|manual');
