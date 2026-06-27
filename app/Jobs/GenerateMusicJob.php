<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AudioAsset;
use App\Services\Audio\MusicServiceContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateMusicJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 180;

    public function __construct(
        private readonly AudioAsset $asset,
        private readonly string     $mood = 'neutral',
    ) {
        $this->onQueue('audio');
    }

    public function handle(MusicServiceContract $music): void
    {
        $this->asset->update(['status' => 'generating']);

        $result = $music->generate(
            prompt:          $this->asset->prompt_used ?? $this->asset->name,
            durationSeconds: (float) ($this->asset->duration_seconds ?? 30.0),
            mood:            $this->mood,
        );

        $this->asset->update([
            'status'              => 'completed',
            'file_path'           => $result->filePath,
            'duration_seconds'    => $result->durationSeconds,
            'generation_cost_usd' => $result->costUsd,
            'service_job_id'      => $result->serviceJobId,
            'file_format'         => $result->format,
        ]);

        Log::info('GenerateMusicJob completado', ['asset_id' => $this->asset->id]);
    }

    public function failed(Throwable $e): void
    {
        Log::error('GenerateMusicJob falló', ['asset_id' => $this->asset->id, 'error' => $e->getMessage()]);
        $this->asset->update(['status' => 'failed']);
    }
}
