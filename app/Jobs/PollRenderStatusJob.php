<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Render;
use App\Models\User;
use App\Notifications\RenderCompletedNotification;
use App\Services\RenderFarm\RenderFarmContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PollRenderStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;  // cada dispatch es un intento; la cadena de re-poll maneja la lógica
    public int $timeout = 30;

    private const MAX_POLLS  = 20;
    private const POLL_EVERY = 30; // segundos entre polls

    public function __construct(
        private readonly Render $render,
        private readonly int $attempt = 1,
    ) {
        $this->onQueue('renders');
    }

    public function handle(RenderFarmContract $farm): void
    {
        $this->render->refresh();

        // Si el webhook ya lo completó, no hacer nada
        if (in_array($this->render->status, ['completed', 'failed', 'cancelled'], true)) {
            return;
        }

        $result = $farm->status($this->render);

        Log::debug('PollRenderStatusJob', [
            'render_id' => $this->render->id,
            'attempt'   => $this->attempt,
            'status'    => $result->status,
        ]);

        if ($result->isCompleted()) {
            $this->handleCompleted($result->fileUrl, $result->costUsd, $result->raw);
            return;
        }

        if ($result->isFailed()) {
            $this->render->update([
                'status'        => 'failed',
                'error_message' => $result->errorMessage,
                'metadata'      => array_merge($this->render->metadata ?? [], ['last_poll' => $result->raw]),
            ]);

            $this->maybeRetry();
            return;
        }

        // Todavía en progreso — re-encolar si no superamos el límite
        if ($this->attempt < self::MAX_POLLS) {
            self::dispatch($this->render, $this->attempt + 1)
                ->delay(now()->addSeconds(self::POLL_EVERY));
        } else {
            Log::error('PollRenderStatusJob: max polls alcanzados sin completar', ['render_id' => $this->render->id]);
            $this->render->update([
                'status'        => 'failed',
                'error_message' => 'Timeout: el render no completó en el tiempo máximo de espera.',
            ]);
        }
    }

    private function handleCompleted(?string $fileUrl, ?float $costUsd, array $raw): void
    {
        $filePath = null;

        if ($fileUrl) {
            $filePath = $this->downloadAndStore($fileUrl);
        }

        $this->render->update([
            'status'      => 'completed',
            'file_path'   => $filePath ?? $fileUrl,
            'gpu_cost_usd'=> $costUsd,
            'metadata'    => array_merge($this->render->metadata ?? [], ['completed_raw' => $raw]),
        ]);

        $this->notifyUsers();
        Log::info('Render completado', ['render_id' => $this->render->id, 'cost_usd' => $costUsd]);
    }

    private function downloadAndStore(string $url): ?string
    {
        try {
            $contents = file_get_contents($url);
            if ($contents === false) {
                return null;
            }

            $ext  = str_contains($url, '.mp4') ? 'mp4' : 'webp';
            $path = "renders/{$this->render->shot_id}/{$this->render->id}.{$ext}";
            Storage::disk('public')->put($path, $contents);

            return $path;
        } catch (\Throwable $e) {
            Log::warning('No se pudo descargar el render', ['url' => $url, 'error' => $e->getMessage()]);
            return null;
        }
    }

    private function notifyUsers(): void
    {
        User::all()->each(fn($u) => $u->notify(new RenderCompletedNotification($this->render->fresh())));
    }

    // Smart retry: reintenta con seed diferente si falla
    private function maybeRetry(): void
    {
        if ($this->render->retry_count >= 2) {
            Log::info('Render agotó reintentos', ['render_id' => $this->render->id]);
            return;
        }

        $newSeed = random_int(1, 2_147_483_647);

        $this->render->increment('retry_count');
        $this->render->update([
            'status' => 'queued',
            'seed'   => $newSeed,
            'job_id' => null,
        ]);

        SubmitRenderJob::dispatch($this->render->fresh())->delay(now()->addSeconds(5));

        Log::info('Render reintentando con nuevo seed', [
            'render_id'   => $this->render->id,
            'retry_count' => $this->render->retry_count,
            'new_seed'    => $newSeed,
        ]);
    }

    public function failed(Throwable $e): void
    {
        Log::error('PollRenderStatusJob excepción', [
            'render_id' => $this->render->id,
            'error'     => $e->getMessage(),
        ]);
    }
}
