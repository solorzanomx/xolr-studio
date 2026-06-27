<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Render;
use App\Services\RenderFarm\RenderFarmContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SubmitRenderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries      = 3;
    public int $timeout    = 60;
    public int $backoff    = 10;

    public function __construct(
        private readonly Render $render,
    ) {
        $this->onQueue('renders');
    }

    public function handle(RenderFarmContract $farm): void
    {
        // Recargar para asegurar estado fresco
        $this->render->refresh();

        if (! in_array($this->render->status, ['queued', 'failed'], true)) {
            Log::warning('SubmitRenderJob: render ya no está en estado encolable', [
                'render_id' => $this->render->id,
                'status'    => $this->render->status,
            ]);
            return;
        }

        $this->render->update(['status' => 'queued']);

        $jobId = $farm->submit($this->render);

        $this->render->update([
            'job_id' => $jobId,
            'status' => 'processing',
        ]);

        // Encolar el primer poll después de un delay según el tier
        $delay = match ($this->render->quality_tier) {
            'draft'    => 25,   // ~20s esperado + margen
            'standard' => 70,   // ~60s esperado + margen
            'final'    => 260,  // ~4min esperado + margen
            default    => 70,
        };

        PollRenderStatusJob::dispatch($this->render)->delay(now()->addSeconds($delay));
    }

    public function failed(Throwable $e): void
    {
        Log::error('SubmitRenderJob falló definitivamente', [
            'render_id' => $this->render->id,
            'error'     => $e->getMessage(),
        ]);

        $this->render->update([
            'status'        => 'failed',
            'error_message' => $e->getMessage(),
        ]);
    }
}
