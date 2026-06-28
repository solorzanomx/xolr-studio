<?php

declare(strict_types=1);

namespace App\Services\RenderFarm;

use App\Models\Render;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ComfyUIAdapter implements RenderFarmContract
{
    private const TIER_PARAMS = [
        'draft'    => ['steps' => 12, 'guidance' => 3.5],
        'standard' => ['steps' => 20, 'guidance' => 3.5],
        'final'    => ['steps' => 28, 'guidance' => 3.5],
    ];

    public function __construct(
        private readonly string $baseUrl,
        private readonly array $models = [],
        private readonly bool $mockMode = false,
    ) {}

    // ─── Contract ────────────────────────────────────────────────────────────

    public function submit(Render $render): string
    {
        if ($this->mockMode) {
            return 'mock-' . uniqid('', true);
        }

        $render->loadMissing(['prompt', 'shot.formatPreset']);

        $params  = self::TIER_PARAMS[$render->quality_tier] ?? self::TIER_PARAMS['draft'];
        $preset  = $render->shot?->formatPreset;
        $seed    = $render->seed ?? random_int(1, 999_999_999);
        $width   = $render->width  ?? $preset?->width  ?? 1024;
        $height  = $render->height ?? $preset?->height ?? 1024;
        $text    = $render->prompt?->positive_prompt ?? '';

        $workflow = $this->devWorkflow($text, $seed, $width, $height, $params['steps'], $params['guidance']);

        $response = Http::timeout(30)
            ->post("{$this->baseUrl}/prompt", [
                'prompt'    => $workflow,
                'client_id' => (string) $render->id,
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                "ComfyUI submit failed [{$response->status()}]: " . $response->body()
            );
        }

        $promptId = $response->json('prompt_id');

        if (! $promptId) {
            throw new RuntimeException('ComfyUI sin prompt_id: ' . $response->body());
        }

        Log::info('ComfyUI job submitted', ['prompt_id' => $promptId, 'render_id' => $render->id]);

        return $promptId;
    }

    public function status(Render $render): RenderStatusResult
    {
        if ($this->mockMode) {
            return $this->mockStatus($render);
        }

        $response = Http::timeout(15)
            ->get("{$this->baseUrl}/history/{$render->job_id}");

        if ($response->failed()) {
            return new RenderStatusResult(
                status: 'processing', fileUrl: null, costUsd: null, errorMessage: null, raw: [],
            );
        }

        $data = $response->json();

        // Vacío = todavía en cola o procesando
        if (empty($data) || ! isset($data[$render->job_id])) {
            return new RenderStatusResult(
                status: 'processing', fileUrl: null, costUsd: null, errorMessage: null, raw: [],
            );
        }

        $history      = $data[$render->job_id];
        $statusInfo   = $history['status'] ?? [];
        $completed    = $statusInfo['completed'] ?? false;
        $statusString = $statusInfo['status_string'] ?? 'success';

        if (! $completed) {
            return new RenderStatusResult(
                status: 'processing', fileUrl: null, costUsd: null, errorMessage: null, raw: $history,
            );
        }

        if ($statusString !== 'success') {
            $messages = $statusInfo['messages'] ?? [];
            $error    = ! empty($messages) ? json_encode($messages) : 'ComfyUI execution failed';

            return new RenderStatusResult(
                status: 'failed', fileUrl: null, costUsd: null, errorMessage: $error, raw: $history,
            );
        }

        $filePath = $this->downloadOutputImage($history, $render);

        return new RenderStatusResult(
            status:  'completed',
            fileUrl: $filePath,
            costUsd: null,
            errorMessage: null,
            raw: $history,
        );
    }

    public function cancel(Render $render): bool
    {
        // ComfyUI no tiene cancel REST — simplemente dejamos de hacer poll
        return true;
    }

    // ─── Workflow ─────────────────────────────────────────────────────────────

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
            '1'  => ['class_type' => 'UNETLoader',           'inputs' => ['unet_name' => $modelDev, 'weight_dtype' => 'fp8_e4m3fn']],
            '2'  => ['class_type' => 'DualCLIPLoader',        'inputs' => ['clip_name1' => $clipL, 'clip_name2' => $clipT5, 'type' => 'flux']],
            '3'  => ['class_type' => 'VAELoader',             'inputs' => ['vae_name' => $vae]],
            '4'  => ['class_type' => 'CLIPTextEncode',        'inputs' => ['text' => $prompt, 'clip' => ['2', 0]]],
            '4g' => ['class_type' => 'FluxGuidance',          'inputs' => ['conditioning' => ['4', 0], 'guidance' => $guidance]],
            '5'  => ['class_type' => 'EmptySD3LatentImage',   'inputs' => ['width' => $width, 'height' => $height, 'batch_size' => 1]],
            '6'  => ['class_type' => 'RandomNoise',           'inputs' => ['noise_seed' => $seed]],
            '7'  => ['class_type' => 'BasicGuider',           'inputs' => ['model' => ['1', 0], 'conditioning' => ['4g', 0]]],
            '8'  => ['class_type' => 'KSamplerSelect',        'inputs' => ['sampler_name' => 'euler']],
            '9'  => ['class_type' => 'BasicScheduler',        'inputs' => ['model' => ['1', 0], 'scheduler' => 'beta', 'steps' => $steps, 'denoise' => 1.0]],
            '10' => ['class_type' => 'SamplerCustomAdvanced', 'inputs' => ['noise' => ['6', 0], 'guider' => ['7', 0], 'sampler' => ['8', 0], 'sigmas' => ['9', 0], 'latent_image' => ['5', 0]]],
            '11' => ['class_type' => 'VAEDecode',             'inputs' => ['samples' => ['10', 0], 'vae' => ['3', 0]]],
            '12' => ['class_type' => 'SaveImage',             'inputs' => ['filename_prefix' => 'xolr', 'images' => ['11', 0]]],
        ];
    }

    // ─── Output ──────────────────────────────────────────────────────────────

    private function downloadOutputImage(array $history, Render $render): ?string
    {
        foreach ($history['outputs'] ?? [] as $output) {
            $images = $output['images'] ?? [];
            if (empty($images)) {
                continue;
            }

            $image    = $images[0];
            $filename = $image['filename'] ?? null;

            if (! $filename) {
                continue;
            }

            $imgResponse = Http::timeout(60)->get("{$this->baseUrl}/view", [
                'filename' => $filename,
                'subfolder' => $image['subfolder'] ?? '',
                'type'      => $image['type'] ?? 'output',
            ]);

            if ($imgResponse->successful()) {
                $path = "renders/{$render->shot_id}/{$render->id}.png";
                Storage::disk('public')->put($path, $imgResponse->body());
                Log::info('ComfyUI: imagen guardada', ['path' => $path, 'render_id' => $render->id]);
                return $path;
            }
        }

        Log::warning('ComfyUI: no se encontró imagen en outputs', ['render_id' => $render->id]);
        return null;
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function model(string $key): string
    {
        return $this->models[$key] ?? match ($key) {
            'dev'     => 'FLUX1/flux1-dev-fp8.safetensors',
            'clip_l'  => 'clip_l.safetensors',
            'clip_t5' => 't5/t5xxl_fp16.safetensors',
            'vae'     => 'FLUX1/ae.safetensors',
            default   => throw new RuntimeException("Modelo ComfyUI '{$key}' no definido"),
        };
    }

    private function mockStatus(Render $render): RenderStatusResult
    {
        $age = now()->diffInSeconds($render->created_at);

        if ($age < 10) {
            return new RenderStatusResult(status: 'processing', fileUrl: null, costUsd: null, errorMessage: null, raw: []);
        }

        return new RenderStatusResult(
            status:  'completed',
            fileUrl: 'https://placehold.co/1024x1024/1a1a1a/888888?text=Mock+Render',
            costUsd: 0.01,
            errorMessage: null,
            raw: ['mock' => true],
        );
    }
}
