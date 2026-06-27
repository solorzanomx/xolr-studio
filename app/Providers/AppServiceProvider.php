<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Audio\AudioServiceContract;
use App\Services\Audio\ElevenLabsAdapter;
use App\Services\Audio\MusicServiceContract;
use App\Services\Audio\SubtitleService;
use App\Services\Audio\SunoAdapter;
use App\Services\RenderFarm\RenderFarmContract;
use App\Services\RenderFarm\RunPodAdapter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
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
