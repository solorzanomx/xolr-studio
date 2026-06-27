<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SubmitRenderJob;
use App\Models\Render;
use App\Services\RenderFarm\RunPodAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RunPodWebhookController extends Controller
{
    public function __construct(private readonly RunPodAdapter $adapter) {}

    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->verifySignature($request)) {
            Log::warning('RunPod webhook: firma HMAC inválida', ['ip' => $request->ip()]);
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
            return response()->json(['ok' => true]);
        }

        if (in_array($render->status, ['completed', 'failed', 'cancelled'], true)) {
            return response()->json(['ok' => true]);
        }

        $rawStatus = $payload['status'] ?? 'FAILED';

        $statusMap = [
            'COMPLETED' => 'completed',
            'FAILED'    => 'failed',
            'CANCELLED' => 'cancelled',
            'TIMED_OUT' => 'failed',
        ];

        $normalized = $statusMap[$rawStatus] ?? null;

        if (! $normalized) {
            // Estado intermedio (IN_QUEUE, IN_PROGRESS) — el poll se encarga
            return response()->json(['ok' => true]);
        }

        if ($normalized === 'completed') {
            // Delega el parsing de output ComfyUI al adapter (maneja base64 + URLs)
            $filePath = $this->adapter->extractOutput($payload, $render);

            $render->update([
                'status'       => 'completed',
                'file_path'    => $filePath,
                'gpu_cost_usd' => $this->parseCost($payload),
                'metadata'     => array_merge($render->metadata ?? [], ['webhook_raw' => $payload]),
            ]);

            Log::info('RunPod webhook: render completado', [
                'render_id' => $render->id,
                'file_path' => $filePath,
            ]);
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
                    'seed'   => random_int(1, 999_999_999),
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

    private function parseCost(array $payload): ?float
    {
        if (isset($payload['executionTime'])) {
            return round((int) $payload['executionTime'] / 1000 * 0.00042, 6);
        }
        return null;
    }
}
