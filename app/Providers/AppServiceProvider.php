<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Audio\AudioServiceContract;
use App\Services\LipSync\DIDAdapter;
use App\Services\LipSync\LipSyncContract;
use App\Services\Audio\ElevenLabsAdapter;
use App\Services\Audio\MusicServiceContract;
use App\Services\Audio\SubtitleService;
use App\Services\Audio\SunoAdapter;
use App\Services\RenderFarm\ComfyUIAdapter;
use App\Services\RenderFarm\RenderFarmContract;
use App\Services\RenderFarm\RunPodAdapter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LipSyncContract::class, fn(): DIDAdapter => new DIDAdapter(
            apiKey:   config('services.did.api_key', ''),
            mockMode: config('services.did.mock_mode', false),
        ));

        $this->app->singleton(AudioServiceContract::class, fn(): ElevenLabsAdapter => new ElevenLabsAdapter(
            apiKey:   config('services.elevenlabs.api_key', ''),
            mockMode: config('services.elevenlabs.mock_mode', false),
        ));

        $this->app->singleton(MusicServiceContract::class, fn(): SunoAdapter => new SunoAdapter(
            apiKey:   config('services.suno.api_key'),
            mockMode: config('services.suno.mock_mode', false),
        ));

        $this->app->singleton(SubtitleService::class, fn(): SubtitleService => new SubtitleService(
            openAiKey: config('services.openai.key'),
            mockMode:  config('services.openai.mock_mode', false),
        ));

        $this->app->singleton(RenderFarmContract::class, function (): RenderFarmContract {
            if (config('services.comfyui.base_url')) {
                return new ComfyUIAdapter(
                    baseUrl:  config('services.comfyui.base_url'),
                    models:   config('services.comfyui.models', []),
                    mockMode: config('services.comfyui.mock_mode', false),
                );
            }

            return new RunPodAdapter(
                apiKey:        config('services.runpod.api_key', ''),
                endpoints:     config('services.runpod.endpoints', []),
                webhookSecret: config('services.runpod.webhook_secret'),
                models:        config('services.runpod.models', []),
                mockMode:      config('services.runpod.mock_mode', false),
            );
        });
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
