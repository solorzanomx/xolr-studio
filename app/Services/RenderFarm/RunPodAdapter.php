<?php

declare(strict_types=1);

namespace App\Services\RenderFarm;

use App\Models\Render;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class RunPodAdapter implements RenderFarmContract
{
    private const BASE_URL = 'https://api.runpod.ai/v2';

    private const TIER_PARAMS = [
        // draft usa Dev con pasos reducidos — Schnell requiere endpoint custom
        'draft' => [
            'model'     => 'dev',
            'steps'     => 12,
            'scheduler' => 'beta',
            'guidance'  => 3.5,
        ],
        'standard' => [
            'model'     => 'dev',
            'steps'     => 20,
            'scheduler' => 'beta',
            'guidance'  => 3.5,
        ],
        'final' => [
            'model'     => 'dev',
            'steps'     => 28,
            'scheduler' => 'beta',
            'guidance'  => 3.5,
        ],
    ];

    private const STATUS_MAP = [
        'IN_QUEUE'    => 'queued',
        'IN_PROGRESS' => 'processing',
        'COMPLETED'   => 'completed',
        'FAILED'      => 'failed',
        'CANCELLED'   => 'cancelled',
        'TIMED_OUT'   => 'failed',
    ];

    public function __construct(
        private readonly string $apiKey,
        private readonly array $endpoints,
        private readonly ?string $webhookSecret,
        private readonly array $models = [],
        private readonly bool $mockMode = false,
    ) {}

    // ─── Contract ────────────────────────────────────────────────────────────

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
                throw new RuntimeException('RunPod sin job id: ' . $response->body());
            }

            Log::info('RunPod job submitted', [
                'job_id'    => $jobId,
                'render_id' => $render->id,
                'tier'      => $render->quality_tier,
            ]);

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
                    status:       'failed',
                    fileUrl:      null,
                    costUsd:      null,
                    errorMessage: "Status check failed [{$response->status()}]",
                    raw:          $response->json() ?? [],
                );
            }

            $data      = $response->json();
            $rawStatus = $data['status'] ?? 'FAILED';
            $normalized = self::STATUS_MAP[$rawStatus] ?? 'failed';

            $fileUrl = null;

            if ($normalized === 'completed') {
                $fileUrl = $this->extractOutput($data, $render);
            }

            $costUsd = isset($data['executionTime'])
                ? $this->estimateCost($render->quality_tier, (int) $data['executionTime'])
                : null;

            return new RenderStatusResult(
                status:       $normalized,
                fileUrl:      $fileUrl,
                costUsd:      $costUsd,
                errorMessage: $data['error'] ?? null,
                raw:          $data,
            );

        } catch (ConnectionException) {
            return new RenderStatusResult(
                status:       'processing',
                fileUrl:      null,
                costUsd:      null,
                errorMessage: null,
                raw:          [],
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

    // ─── ComfyUI payload ─────────────────────────────────────────────────────

    private function buildPayload(Render $render): array
    {
        $prompt  = $render->prompt;
        $preset  = $render->shot?->formatPreset;
        $params  = self::TIER_PARAMS[$render->quality_tier] ?? self::TIER_PARAMS['draft'];

        $seed   = $render->seed ?? random_int(1, 999_999_999);
        $width  = $render->width  ?? $preset?->width  ?? 1024;
        $height = $render->height ?? $preset?->height ?? 1024;

        $positivePrompt = $prompt?->positive_prompt ?? '';

        $workflow = $this->devWorkflow($positivePrompt, $seed, $width, $height, $params['steps'], $params['guidance'] ?? 3.5);

        $payload = [
            'input' => ['workflow' => $workflow],
        ];

        // Webhook va a nivel raíz del job, no dentro de input
        $payload['webhook'] = config('app.url') . '/api/webhooks/runpod';

        return $payload;
    }

    /**
     * FLUX Schnell — 4 pasos, sin guidance, para drafts rápidos.
     */
    private function schnellWorkflow(
        string $prompt,
        int $seed,
        int $width,
        int $height,
        int $steps,
    ): array {
        $modelSchnell = $this->model('schnell');
        $clipL        = $this->model('clip_l');
        $clipT5       = $this->model('clip_t5');
        $vae          = $this->model('vae');

        return [
            '1'  => ['class_type' => 'UNETLoader',    'inputs' => ['unet_name' => $modelSchnell, 'weight_dtype' => 'fp8_e4m3fn']],
            '2'  => ['class_type' => 'DualCLIPLoader', 'inputs' => ['clip_name1' => $clipL, 'clip_name2' => $clipT5, 'type' => 'flux']],
            '3'  => ['class_type' => 'VAELoader',      'inputs' => ['vae_name' => $vae]],
            '4'  => ['class_type' => 'CLIPTextEncode', 'inputs' => ['text' => $prompt, 'clip' => ['2', 0]]],
            '5'  => ['class_type' => 'EmptySD3LatentImage', 'inputs' => ['width' => $width, 'height' => $height, 'batch_size' => 1]],
            '6'  => ['class_type' => 'RandomNoise',    'inputs' => ['noise_seed' => $seed]],
            '7'  => ['class_type' => 'BasicGuider',    'inputs' => ['model' => ['1', 0], 'conditioning' => ['4', 0]]],
            '8'  => ['class_type' => 'KSamplerSelect', 'inputs' => ['sampler_name' => 'euler']],
            '9'  => ['class_type' => 'BasicScheduler', 'inputs' => ['model' => ['1', 0], 'scheduler' => 'simple', 'steps' => $steps, 'denoise' => 1.0]],
            '10' => ['class_type' => 'SamplerCustomAdvanced', 'inputs' => ['noise' => ['6', 0], 'guider' => ['7', 0], 'sampler' => ['8', 0], 'sigmas' => ['9', 0], 'latent_image' => ['5', 0]]],
            '11' => ['class_type' => 'VAEDecode',      'inputs' => ['samples' => ['10', 0], 'vae' => ['3', 0]]],
            '12' => ['class_type' => 'SaveImage',      'inputs' => ['filename_prefix' => 'xolr', 'images' => ['11', 0]]],
        ];
    }

    /**
     * FLUX Dev — 20-28 pasos, FluxGuidance, para renders de calidad.
     */
    private function devWorkflow(
        string $prompt,
        int $seed,
        int $width,
        int $height,
        int $steps,
        float $guidance,
    ): array {
        $modelDev = $this->model('dev');
        $clipL    = $this->model('clip_l');
        $clipT5   = $this->model('clip_t5');
        $vae      = $this->model('vae');

        return [
            '1'  => ['class_type' => 'UNETLoader',    'inputs' => ['unet_name' => $modelDev, 'weight_dtype' => 'fp8_e4m3fn']],
            '2'  => ['class_type' => 'DualCLIPLoader', 'inputs' => ['clip_name1' => $clipL, 'clip_name2' => $clipT5, 'type' => 'flux']],
            '3'  => ['class_type' => 'VAELoader',      'inputs' => ['vae_name' => $vae]],
            '4'  => ['class_type' => 'CLIPTextEncode', 'inputs' => ['text' => $prompt, 'clip' => ['2', 0]]],
            // FluxGuidance entre CLIP y Guider — característico de Dev
            '4g' => ['class_type' => 'FluxGuidance',  'inputs' => ['conditioning' => ['4', 0], 'guidance' => $guidance]],
            '5'  => ['class_type' => 'EmptySD3LatentImage', 'inputs' => ['width' => $width, 'height' => $height, 'batch_size' => 1]],
            '6'  => ['class_type' => 'RandomNoise',    'inputs' => ['noise_seed' => $seed]],
            '7'  => ['class_type' => 'BasicGuider',    'inputs' => ['model' => ['1', 0], 'conditioning' => ['4g', 0]]],
            '8'  => ['class_type' => 'KSamplerSelect', 'inputs' => ['sampler_name' => 'euler']],
            '9'  => ['class_type' => 'BasicScheduler', 'inputs' => ['model' => ['1', 0], 'scheduler' => 'beta', 'steps' => $steps, 'denoise' => 1.0]],
            '10' => ['class_type' => 'SamplerCustomAdvanced', 'inputs' => ['noise' => ['6', 0], 'guider' => ['7', 0], 'sampler' => ['8', 0], 'sigmas' => ['9', 0], 'latent_image' => ['5', 0]]],
            '11' => ['class_type' => 'VAEDecode',      'inputs' => ['samples' => ['10', 0], 'vae' => ['3', 0]]],
            '12' => ['class_type' => 'SaveImage',      'inputs' => ['filename_prefix' => 'xolr', 'images' => ['11', 0]]],
        ];
    }

    // ─── Output parsing ──────────────────────────────────────────────────────

    /**
     * Extrae la imagen del output de ComfyUI y la guarda en storage.
     * Soporta: base64 (images_b64), URL directa (image_url), array de outputs.
     */
    public function extractOutput(array $data, Render $render): ?string
    {
        // ComfyUI devuelve output como array (un elemento por prompt ejecutado)
        $output = $data['output'] ?? null;

        if (is_array($output) && array_is_list($output) && isset($output[0])) {
            $output = $output[0];
        }

        if (! $output) {
            return null;
        }

        // Prioridad 1: imagen en base64
        $b64 = $output['images_b64'][0] ?? null;
        if ($b64) {
            return $this->storeBase64Image($b64, $render);
        }

        // Prioridad 2: URL CDN directa
        if (! empty($output['image_url'])) {
            return $output['image_url'];
        }

        // Prioridad 3: mensaje base64 (algunos workers lo ponen en 'message')
        $msg = $output['message'] ?? null;
        if ($msg && ! str_starts_with($msg, 'http')) {
            return $this->storeBase64Image($msg, $render);
        }

        return null;
    }

    private function storeBase64Image(string $b64, Render $render): ?string
    {
        // Quitar prefijo data URI si viene
        $b64 = preg_replace('/^data:image\/\w+;base64,/', '', $b64);

        $contents = base64_decode($b64, strict: true);

        if ($contents === false) {
            Log::warning('RunPod: base64 decode falló', ['render_id' => $render->id]);
            return null;
        }

        $path = "renders/{$render->shot_id}/{$render->id}.png";
        Storage::disk('public')->put($path, $contents);

        Log::info('RunPod: imagen guardada', ['path' => $path, 'render_id' => $render->id]);

        return $path;
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function resolveEndpoint(Render $render): string
    {
        $key = match ($render->quality_tier) {
            'final'  => 'upscale',
            default  => $render->file_type === 'video' ? 'video' : 'image',
        };

        // Si no hay endpoint de upscale configurado, usar el de imagen
        if ($key === 'upscale' && empty($this->endpoints['upscale'])) {
            $key = 'image';
        }

        $id = $this->endpoints[$key] ?? null;

        if (! $id) {
            throw new RuntimeException("Endpoint RunPod '{$key}' no configurado. Revisa RUNPOD_ENDPOINT_* en .env");
        }

        return $id;
    }

    private function model(string $key): string
    {
        return $this->models[$key] ?? match ($key) {
            'schnell' => 'flux1-schnell-fp8.safetensors',
            'dev'     => 'flux1-dev-fp8.safetensors',
            'clip_l'  => 'clip_l.safetensors',
            'clip_t5' => 't5xxl_fp8_e4m3fn.safetensors',
            'vae'     => 'ae.safetensors',
            default   => throw new RuntimeException("Modelo RunPod '{$key}' no definido"),
        };
    }

    private function estimateCost(string $tier, int $executionTimeMs): float
    {
        $ratePerSecond = match ($tier) {
            'draft'    => 0.00025,
            'standard' => 0.00042,
            'final'    => 0.00042,
            default    => 0.00042,
        };

        return round(($executionTimeMs / 1000) * $ratePerSecond, 6);
    }

    // ─── Mock ────────────────────────────────────────────────────────────────

    private function mockSubmit(Render $render): string
    {
        $jobId = 'mock-' . uniqid('', true);
        Log::info('[MOCK] RunPod job submitted', ['job_id' => $jobId, 'render_id' => $render->id]);
        return $jobId;
    }

    private function mockStatus(Render $render): RenderStatusResult
    {
        $age = now()->diffInSeconds($render->created_at);

        if ($age < 10) {
            return new RenderStatusResult(status: 'processing', fileUrl: null, costUsd: null, errorMessage: null);
        }

        return new RenderStatusResult(
            status:  'completed',
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
