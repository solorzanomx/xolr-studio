<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AudioAsset;
use App\Services\Audio\SubtitleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateSubtitlesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 120;

    public function __construct(
        private readonly AudioAsset $asset,
        private readonly string     $language = 'es',
    ) {
        $this->onQueue('audio');
    }

    public function handle(SubtitleService $subtitles): void
    {
        if (! $this->asset->file_path) {
            Log::warning('GenerateSubtitlesJob: asset sin file_path', ['asset_id' => $this->asset->id]);
            return;
        }

        $srt  = $subtitles->transcribe($this->asset->file_path, $this->language);
        $path = $subtitles->saveSrt($srt, (string) $this->asset->id);

        // Guardamos el SRT como metadata del asset
        $meta = $this->asset->metadata ?? [];
        $meta['srt_path']   = $path;
        $meta['transcript'] = $srt;

        $this->asset->update(['metadata' => $meta]);

        Log::info('GenerateSubtitlesJob completado', ['asset_id' => $this->asset->id, 'srt_path' => $path]);
    }

    public function failed(Throwable $e): void
    {
        Log::error('GenerateSubtitlesJob falló', ['asset_id' => $this->asset->id, 'error' => $e->getMessage()]);
    }
}
