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
use Throwable;

class SubmitLipSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;
    public int $backoff = 10;

    public function __construct(
        private readonly TalkingRender $render,
    ) {
        $this->onQueue('renders');
    }

    public function handle(LipSyncContract $lipSync): void
    {
        $this->render->refresh();

        if (! in_array($this->render->status, ['queued', 'failed'], true)) {
            return;
        }

        $this->render->update(['status' => 'processing']);

        $jobId = $lipSync->submit($this->render);

        $this->render->update(['service_job_id' => $jobId]);

        // Primer poll tras el tiempo esperado según calidad
        $delay = match ($this->render->quality) {
            'draft'      => 20,
            'production' => 45,
            'premium'    => 90,
            default      => 45,
        };

        PollLipSyncJob::dispatch($this->render)->delay(now()->addSeconds($delay));
    }

    public function failed(Throwable $e): void
    {
        Log::error('SubmitLipSyncJob falló', ['render_id' => $this->render->id, 'error' => $e->getMessage()]);
        $this->render->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
    }
}
