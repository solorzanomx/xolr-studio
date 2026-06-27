<?php

declare(strict_types=1);

namespace App\Services\RenderFarm;

use App\Models\Render;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class RunPodAdapter implements RenderFarmContract
{
    private const BASE_URL = 'https://api.runpod.io/v2';

    // Parámetros por tier de calidad
    private const TIER_PARAMS = [
        'draft' => [
            'num_inference_steps' => 4,
            'guidance_scale'      => 0.0,  // FLUX Schnell no usa CFG
            'model'               => 'schnell',
        ],
        'standard' => [
            'num_inference_steps' => 28,
            'guidance_scale'      => 3.5,
            'model'               => 'dev',
        ],
        'final' => [
            'num_inference_steps' => 28,
            'guidance_scale'      => 3.5,
            'model'               => 'dev',
            'upscale'             => true,
            'upscale_factor'      => 4,
        ],
    ];

    private const STATUS_MAP = [
        'IN_QUEUE'   => 'queued',
        'IN_PROGRESS'=> 'processing',
        'COMPLETED'  => 'completed',
        'FAILED'     => 'failed',
        'CANCELLED'  => 'cancelled',
        'TIMED_OUT'  => 'failed',
    ];

    public function __construct(
        private readonly string $apiKey,
        private readonly array $endpoints,   // ['image' => id, 'video' => id, 'upscale' => id]
        private readonly ?string $webhookSecret,
        private readonly bool $mockMode = false,
    ) {}

    public function submit(Render $render): string
    {
        if ($this->mockMode) {
            return $this->mockSubmit($render);
        }

        $render->loadMissing(['prompt', 'shot.formatPreset']);

        $endpointId = $this->resolveEndpoint($render);
        $payload    = $this->buildPayload($render);

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->post("{$this->BASE_URL}/{$endpointId}/run", $payload);

            if ($response->failed()) {
                throw new RuntimeException(
                    "RunPod submit failed [{$response->status()}]: " . $response->body()
                );
            }

            $jobId = $response->json('id');

            if (! $jobId) {
                throw new RuntimeException('RunPod devolvió respuesta sin job id: ' . $response->body());
            }

            Log::info('RunPod job submitted', ['job_id' => $jobId, 'render_id' => $render->id, 'tier' => $render->quality_tier]);

            return $jobId;

        } catch (ConnectionException $e) {
            throw new RuntimeException('No se pudo conectar a RunPod: ' . $e->getMessage(), 0, $e);
        }
    }

    public function status(Render $render): RenderStatusResult
    {
        if ($this->mockMode) {
            return $this->mockStatus($render);
        }

        $endpointId = $this->resolveEndpoint($render);

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(15)
                ->get("{$this->BASE_URL}/{$endpointId}/status/{$render->job_id}");

            if ($response->failed()) {
                return new RenderStatusResult(
                    status: 'failed',
                    fileUrl: null,
                    costUsd: null,
                    errorMessage: "RunPod status check failed [{$response->status()}]",
                    raw: $response->json() ?? [],
                );
            }

            $data       = $response->json();
            $rawStatus  = $data['status'] ?? 'FAILED';
            $normalized = self::STATUS_MAP[$rawStatus] ?? 'failed';

            $fileUrl = null;
            if ($normalized === 'completed') {
                $fileUrl = $data['output']['image_url']
                    ?? $data['output']['images'][0]
                    ?? $data['output'][0]
                    ?? null;
            }

            $costUsd = isset($data['executionTime'])
                ? $this->estimateCost($render->quality_tier, (int) $data['executionTime'])
                : null;

            return new RenderStatusResult(
                status: $normalized,
                fileUrl: $fileUrl,
                costUsd: $costUsd,
                errorMessage: $data['error'] ?? null,
                raw: $data,
            );

        } catch (ConnectionException $e) {
            return new RenderStatusResult(
                status: 'processing',  // asumir en progreso si no podemos conectar
                fileUrl: null,
                costUsd: null,
                errorMessage: null,
                raw: [],
            );
        }
    }

    public function cancel(Render $render): bool
    {
        if ($this->mockMode || ! $render->job_id) {
            return true;
        }

        $endpointId = $this->resolveEndpoint($render);

        $response = Http::withToken($this->apiKey)
            ->timeout(10)
            ->post("{$this->BASE_URL}/{$endpointId}/cancel/{$render->job_id}");

        return $response->successful();
    }

    // ─── Helpers privados ────────────────────────────────────────────────

    private function resolveEndpoint(Render $render): string
    {
        $key = match ($render->quality_tier) {
            'final'  => 'upscale',
            default  => $render->file_type === 'video' ? 'video' : 'image',
        };

        $id = $this->endpoints[$key] ?? null;

        if (! $id) {
            throw new RuntimeException("Endpoint RunPod '{$key}' no configurado. Revisa RUNPOD_ENDPOINT_* en .env");
        }

        return $id;
    }

    private function buildPayload(Render $render): array
    {
        $prompt   = $render->prompt;
        $preset   = $render->shot?->formatPreset;
        $params   = self::TIER_PARAMS[$render->quality_tier] ?? self::TIER_PARAMS['draft'];

        $seed = $render->seed ?? random_int(1, 2_147_483_647);

        $input = [
            'prompt'               => $prompt?->positive_prompt ?? '',
            'negative_prompt'      => $prompt?->negative_prompt ?? '',
            'seed'                 => $seed,
            'num_inference_steps'  => $params['num_inference_steps'],
            'guidance_scale'       => $params['guidance_scale'],
            'width'                => $render->width ?? $preset?->width ?? 1024,
            'height'               => $render->height ?? $preset?->height ?? 1024,
        ];

        if ($params['upscale'] ?? false) {
            $input['upscale_factor'] = $params['upscale_factor'];
        }

        // Webhook para notificación asíncrona cuando el job termina
        $webhookUrl = config('app.url') . '/api/webhooks/runpod';
        $input['webhook'] = $webhookUrl;

        return ['input' => $input];
    }

    private function estimateCost(string $tier, int $executionTimeMs): float
    {
        // RunPod cobra por segundo de GPU — aproximación hasta que tengamos billing API
        $ratePerSecond = match ($tier) {
            'draft'    => 0.00025,
            'standard' => 0.00042,
            'final'    => 0.00042,
            default    => 0.00042,
        };

        return round(($executionTimeMs / 1000) * $ratePerSecond, 6);
    }

    // ─── Mock mode ───────────────────────────────────────────────────────

    private function mockSubmit(Render $render): string
    {
        $fakeJobId = 'mock-' . uniqid('', true);
        Log::info('[MOCK] RunPod job submitted', ['job_id' => $fakeJobId, 'render_id' => $render->id]);
        return $fakeJobId;
    }

    private function mockStatus(Render $render): RenderStatusResult
    {
        // Simula progresión: si el render fue creado hace más de 10s, lo completa
        $age = now()->diffInSeconds($render->created_at);

        if ($age < 10) {
            return new RenderStatusResult(status: 'processing', fileUrl: null, costUsd: null, errorMessage: null);
        }

        return new RenderStatusResult(
            status: 'completed',
            fileUrl: 'https://placehold.co/1024x1024/1a1a1a/888888?text=Mock+Render',
            costUsd: match ($render->quality_tier) {
                'draft'    => 0.005,
                'standard' => 0.025,
                'final'    => 0.08,
                default    => 0.005,
            },
            errorMessage: null,
            raw: ['mock' => true],
        );
    }
}
