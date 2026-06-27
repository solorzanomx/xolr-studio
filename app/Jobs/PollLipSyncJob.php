<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\TalkingRender;
use App\Services\LipSync\LipSyncContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PollLipSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 30;

    private const MAX_POLLS  = 20;
    private const POLL_EVERY = 15;

    public function __construct(
        private readonly TalkingRender $render,
        private readonly int           $attempt = 1,
    ) {
        $this->onQueue('renders');
    }

    public function handle(LipSyncContract $lipSync): void
    {
        $this->render->refresh();

        if (in_array($this->render->status, ['completed', 'failed'], true)) {
            return;
        }

        $result = $lipSync->status($this->render);

        if ($result->isCompleted()) {
            $filePath = $result->fileUrl
                ? $this->downloadAndStore($result->fileUrl)
                : null;

            $this->render->update([
                'status'           => 'completed',
                'file_path'        => $filePath ?? $result->fileUrl,
                'service_cost_usd' => $result->costUsd,
                'duration_seconds' => $result->durationSeconds,
            ]);

            Log::info('LipSync completado', ['render_id' => $this->render->id]);
            return;
        }

        if ($result->isFailed()) {
            $this->render->update([
                'status'        => 'failed',
                'error_message' => $result->errorMessage,
            ]);
            return;
        }

        if ($this->attempt < self::MAX_POLLS) {
            self::dispatch($this->render, $this->attempt + 1)
                ->delay(now()->addSeconds(self::POLL_EVERY));
        } else {
            $this->render->update([
                'status'        => 'failed',
                'error_message' => 'Timeout: lip sync no completó en tiempo máximo.',
            ]);
        }
    }

    private function downloadAndStore(string $url): ?string
    {
        try {
            $contents = file_get_contents($url);
            if ($contents === false) return null;
            $path = "lipsync/{$this->render->shot_id}/{$this->render->id}.mp4";
            Storage::disk('public')->put($path, $contents);
            return $path;
        } catch (\Throwable $e) {
            Log::warning('PollLipSyncJob: no se pudo descargar el video', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error('PollLipSyncJob excepción', ['render_id' => $this->render->id, 'error' => $e->getMessage()]);
    }
}
