<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Episode;
use App\Services\ScriptGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Throwable;

class GenerateScriptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 300;

    public function __construct(
        private readonly int $episodeId,
        private readonly string $type = 'script', // script | chapter
    ) {}

    public static function cacheKey(int $episodeId, string $type): string
    {
        return "episode_script_gen_{$episodeId}_{$type}";
    }

    public function handle(ScriptGeneratorService $service): void
    {
        $episode = Episode::findOrFail($this->episodeId);
        $key     = self::cacheKey($this->episodeId, $this->type);

        Cache::put($key, ['status' => 'generating'], now()->addMinutes(10));

        $result = match ($this->type) {
            'chapter' => $service->generateBookChapter($episode),
            default   => $service->generateScript($episode),
        };

        if ($this->type === 'script') {
            $episode->update(['script' => $result]);
        }

        Cache::put($key, ['status' => 'completed', 'result' => $result], now()->addMinutes(5));
    }

    public function failed(Throwable $e): void
    {
        $key = self::cacheKey($this->episodeId, $this->type);
        Cache::put($key, ['status' => 'failed', 'error' => $e->getMessage()], now()->addMinutes(5));
    }
}
