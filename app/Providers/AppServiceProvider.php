<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\RenderFarm\RenderFarmContract;
use App\Services\RenderFarm\RunPodAdapter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RenderFarmContract::class, function (): RunPodAdapter {
            return new RunPodAdapter(
                apiKey: config('services.runpod.api_key', ''),
                endpoints: config('services.runpod.endpoints', []),
                webhookSecret: config('services.runpod.webhook_secret'),
                mockMode: config('services.runpod.mock_mode', false),
            );
        });
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
