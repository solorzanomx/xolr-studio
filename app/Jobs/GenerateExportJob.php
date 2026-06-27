<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Export;
use App\Services\ExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GenerateExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 300;

    public function __construct(
        private readonly int $exportId,
    ) {}

    public function handle(ExportService $service): void
    {
        $export = Export::findOrFail($this->exportId);

        match ($export->type) {
            'zip_campaign' => $this->handleZip($export, $service),
            default        => $export->update(['status' => 'completed']),
        };
    }

    private function handleZip(Export $export, ExportService $service): void
    {
        $campaignId = $export->metadata['campaign_id'] ?? null;
        if (! $campaignId) {
            $export->update(['status' => 'failed', 'metadata' => ['error' => 'campaign_id requerido en metadata.']]);
            return;
        }

        $campaign = Campaign::findOrFail($campaignId);
        $service->generateZipCampaign($export, $campaign);
    }

    public function failed(Throwable $e): void
    {
        Export::where('id', $this->exportId)->update([
            'status'   => 'failed',
            'metadata' => ['error' => $e->getMessage()],
        ]);
    }
}
