<?php

declare(strict_types=1);

use App\Http\Controllers\Api\DIDWebhookController;
use App\Http\Controllers\Api\RunPodWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/runpod', RunPodWebhookController::class)
    ->name('webhooks.runpod')
    ->withoutMiddleware(['web']);

Route::post('/webhooks/did', DIDWebhookController::class)
    ->name('webhooks.did')
    ->withoutMiddleware(['web']);
