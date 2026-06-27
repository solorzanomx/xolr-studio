<?php

declare(strict_types=1);

use App\Http\Controllers\Api\RunPodWebhookController;
use Illuminate\Support\Facades\Route;

// Webhook de RunPod — sin auth middleware, verificación por HMAC
Route::post('/webhooks/runpod', RunPodWebhookController::class)
    ->name('webhooks.runpod')
    ->withoutMiddleware(['web']);
