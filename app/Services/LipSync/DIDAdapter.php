<?php

declare(strict_types=1);

namespace App\Services\LipSync;

use App\Models\TalkingRender;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DIDAdapter implements LipSyncContract
{
    private const BASE_URL = 'https://api.d-id.com';

    // D-ID cobra por segundo de video generado
    private const COST_PER_SECOND = [
        'draft'      => 0.004,
        'production' => 0.010,
        'premium'    => 0.025,
    ];

    public function __construct(
        private readonly string $apiKey,
        private readonly bool   $mockMode = false,
    ) {}

    public function submit(TalkingRender $render): string
    {
        if ($this->mockMode) {
            return $this->mockSubmit($render);
        }

        $render->loadMissing(['sourceRender', 'audioAsset']);

        $imageUrl = $this->resolveImageUrl($render->sourceRender->file_path);
        $audioUrl = $this->resolveAudioUrl($render->audioAsset->file_path);

        $webhookUrl = config('app.url') . '/api/webhooks/did';

        $response = Http::withBasicAuth($this->apiKey, '')
            ->timeout(30)
            ->post(self::BASE_URL . '/talks', [
                'source_url' => $imageUrl,
                'script'     => [
                    'type'      => 'audio',
                    'audio_url' => $audioUrl,
                ],
                'config' => [
                    'stitch'   => true,
                    'fluent'   => $render->quality !== 'draft',
                    'pad_audio'=> 0.0,
                ],
                'webhook' => $webhookUrl,
            ]);

        if ($response->failed()) {
            throw new RuntimeException("D-ID submit falló [{$response->status()}]: " . $response->body());
        }

        $jobId = $response->json('id');

        if (! $jobId) {
            throw new RuntimeException('D-ID no devolvió job id: ' . $response->body());
        }

        Log::info('D-ID job submitted', ['job_id' => $jobId, 'render_id' => $render->id]);

        return $jobId;
    }

    public function status(TalkingRender $render): LipSyncStatusResult
    {
        if ($this->mockMode) {
            return $this->mockStatus($render);
        }

        $response = Http::withBasicAuth($this->apiKey, '')
            ->timeout(15)
            ->get(self::BASE_URL . "/talks/{$render->service_job_id}");

        if ($response->failed()) {
            return new LipSyncStatusResult(
                status: 'failed',
                fileUrl: null,
                costUsd: null,
                durationSeconds: null,
                errorMessage: "D-ID status check falló [{$response->status()}]",
            );
        }

        $data      = $response->json();
        $rawStatus = $data['status'] ?? 'error';

        $normalized = match ($rawStatus) {
            'created', 'started' => 'processing',
            'done'               => 'completed',
            'error', 'rejected'  => 'failed',
            default              => 'processing',
        };

        $fileUrl  = $data['result_url'] ?? null;
        $duration = isset($data['duration']) ? (float) $data['duration'] : null;
        $costUsd  = $duration ? $this->estimateCost($render->quality, $duration) : null;

        return new LipSyncStatusResult(
            status:          $normalized,
            fileUrl:         $fileUrl,
            costUsd:         $costUsd,
            durationSeconds: $duration,
            errorMessage:    $data['error']['description'] ?? null,
            raw:             $data,
        );
    }

    public function cancel(TalkingRender $render): bool
    {
        if ($this->mockMode || ! $render->service_job_id) return true;

        $response = Http::withBasicAuth($this->apiKey, '')
            ->timeout(10)
            ->delete(self::BASE_URL . "/talks/{$render->service_job_id}");

        return $response->successful();
    }

    public function estimateCost(string $quality, float $durationSeconds): float
    {
        $rate = self::COST_PER_SECOND[$quality] ?? self::COST_PER_SECOND['production'];
        return round($durationSeconds * $rate, 6);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    private function resolveImageUrl(string $path): string
    {
        if (str_starts_with($path, 'http')) return $path;
        return config('app.url') . '/storage/' . $path;
    }

    private function resolveAudioUrl(string $path): string
    {
        if (str_starts_with($path, 'http')) return $path;
        return config('app.url') . '/storage/' . $path;
    }

    private function mockSubmit(TalkingRender $render): string
    {
        $id = 'mock-did-' . uniqid('', true);
        Log::info('[MOCK] D-ID job submitted', ['job_id' => $id, 'render_id' => $render->id]);
        return $id;
    }

    private function mockStatus(TalkingRender $render): LipSyncStatusResult
    {
        $age = now()->diffInSeconds($render->created_at);

        if ($age < 15) {
            return new LipSyncStatusResult(status: 'processing', fileUrl: null, costUsd: null, durationSeconds: null, errorMessage: null);
        }

        return new LipSyncStatusResult(
            status:          'completed',
            fileUrl:         'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
            costUsd:         $this->estimateCost($render->quality, 5.0),
            durationSeconds: 5.0,
            errorMessage:    null,
            raw:             ['mock' => true],
        );
    }
}
