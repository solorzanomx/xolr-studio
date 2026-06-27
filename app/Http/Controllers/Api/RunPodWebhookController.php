<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SubmitRenderJob;
use App\Models\Render;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class RunPodWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->verifySignature($request)) {
            Log::warning('RunPod webhook: firma HMAC inválida', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $payload = $request->json()->all();

        Log::debug('RunPod webhook recibido', ['payload' => $payload]);

        $jobId = $payload['id'] ?? null;

        if (! $jobId) {
            return response()->json(['error' => 'Missing job id'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $render = Render::where('job_id', $jobId)->first();

        if (! $render) {
            // Job válido pero no conocemos el render (puede ser de otro sistema)
            return response()->json(['ok' => true]);
        }

        // Si el render ya está en estado terminal lo ignoramos
        if (in_array($render->status, ['completed', 'failed', 'cancelled'], true)) {
            return response()->json(['ok' => true]);
        }

        $rawStatus = $payload['status'] ?? 'FAILED';

        $statusMap = [
            'COMPLETED'  => 'completed',
            'FAILED'     => 'failed',
            'CANCELLED'  => 'cancelled',
            'TIMED_OUT'  => 'failed',
        ];

        $normalized = $statusMap[$rawStatus] ?? null;

        if (! $normalized) {
            // Estado intermedio (IN_QUEUE, IN_PROGRESS) — ignorar, el poll se encarga
            return response()->json(['ok' => true]);
        }

        if ($normalized === 'completed') {
            $fileUrl  = $payload['output']['image_url']
                ?? $payload['output']['images'][0]
                ?? $payload['output'][0]
                ?? null;

            $filePath = null;
            if ($fileUrl) {
                $filePath = $this->downloadAndStore($fileUrl, $render);
            }

            $render->update([
                'status'       => 'completed',
                'file_path'    => $filePath ?? $fileUrl,
                'gpu_cost_usd' => $this->parseCost($payload),
                'metadata'     => array_merge($render->metadata ?? [], ['webhook_raw' => $payload]),
            ]);

            Log::info('RunPod webhook: render completado', ['render_id' => $render->id]);
        } else {
            $render->update([
                'status'        => $normalized,
                'error_message' => $payload['error'] ?? null,
                'metadata'      => array_merge($render->metadata ?? [], ['webhook_raw' => $payload]),
            ]);

            // Smart retry en fallo
            if ($normalized === 'failed' && $render->retry_count < 2) {
                $render->increment('retry_count');
                $render->update([
                    'status' => 'queued',
                    'seed'   => random_int(1, 2_147_483_647),
                    'job_id' => null,
                ]);
                SubmitRenderJob::dispatch($render->fresh())->delay(now()->addSeconds(5));
            }
        }

        return response()->json(['ok' => true]);
    }

    private function verifySignature(Request $request): bool
    {
        $secret = config('services.runpod.webhook_secret');

        // Si no hay secret configurado, solo permitir en modo mock/local
        if (! $secret) {
            return app()->environment('local');
        }

        $signature = $request->header('X-RunPod-Signature')
            ?? $request->header('Runpod-Signature-256');

        if (! $signature) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }

    private function downloadAndStore(string $url, Render $render): ?string
    {
        try {
            $contents = file_get_contents($url);
            if ($contents === false) {
                return null;
            }
            $ext  = str_contains($url, '.mp4') ? 'mp4' : 'webp';
            $path = "renders/{$render->shot_id}/{$render->id}.{$ext}";
            Storage::disk('public')->put($path, $contents);
            return $path;
        } catch (\Throwable $e) {
            Log::warning('Webhook: no se pudo descargar el render', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function parseCost(array $payload): ?float
    {
        if (isset($payload['executionTime'])) {
            return round((int) $payload['executionTime'] / 1000 * 0.00042, 6);
        }
        return null;
    }
}
