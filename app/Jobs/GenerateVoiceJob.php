<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AudioAsset;
use App\Services\Audio\AudioServiceContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateVoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 90;

    public function __construct(
        private readonly AudioAsset $asset,
    ) {
        $this->onQueue('audio');
    }

    public function handle(AudioServiceContract $audio): void
    {
        $this->asset->update(['status' => 'generating']);

        $profile  = $this->asset->voiceProfile;
        $voiceId  = $profile?->elevenlabs_voice_id ?? config('services.elevenlabs.default_voice_id', '');

        $result = $audio->generateSpeech(
            text:     $this->asset->transcript ?? '',
            voiceId:  $voiceId,
            settings: [
                'stability'        => (float) ($profile?->default_stability ?? 0.5),
                'similarity_boost' => (float) ($profile?->default_similarity_boost ?? 0.75),
                'style'            => (float) ($profile?->default_style ?? 0.0),
            ],
        );

        $this->asset->update([
            'status'              => 'completed',
            'file_path'           => $result->filePath,
            'duration_seconds'    => $result->durationSeconds,
            'generation_cost_usd' => $result->costUsd,
            'service_job_id'      => $result->serviceJobId,
            'file_format'         => $result->format,
        ]);

        Log::info('GenerateVoiceJob completado', ['asset_id' => $this->asset->id]);
    }

    public function failed(Throwable $e): void
    {
        Log::error('GenerateVoiceJob falló', ['asset_id' => $this->asset->id, 'error' => $e->getMessage()]);
        $this->asset->update(['status' => 'failed']);
    }
}
