<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TalkingRender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class DIDWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->verifySignature($request)) {
            Log::warning('D-ID webhook: firma inválida', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $payload = $request->json()->all();
        Log::debug('D-ID webhook recibido', ['payload' => $payload]);

        $jobId = $payload['id'] ?? null;
        if (! $jobId) {
            return response()->json(['ok' => true]);
        }

        $render = TalkingRender::where('service_job_id', $jobId)->first();
        if (! $render || in_array($render->status, ['completed', 'failed'], true)) {
            return response()->json(['ok' => true]);
        }

        $rawStatus = $payload['status'] ?? 'error';

        if ($rawStatus === 'done') {
            $fileUrl  = $payload['result_url'] ?? null;
            $filePath = $fileUrl ? $this->downloadAndStore($fileUrl, $render) : null;
            $duration = isset($payload['duration']) ? (float) $payload['duration'] : null;

            $render->update([
                'status'           => 'completed',
                'file_path'        => $filePath ?? $fileUrl,
                'duration_seconds' => $duration,
                'service_cost_usd' => $duration ? round($duration * 0.010, 6) : null,
            ]);

            Log::info('D-ID webhook: talking render completado', ['render_id' => $render->id]);
        } elseif (in_array($rawStatus, ['error', 'rejected'], true)) {
            $render->update([
                'status'        => 'failed',
                'error_message' => $payload['error']['description'] ?? 'D-ID reportó error.',
            ]);
        }

        return response()->json(['ok' => true]);
    }

    private function verifySignature(Request $request): bool
    {
        $secret = config('services.did.webhook_secret');

        if (! $secret) {
            return app()->environment('local');
        }

        $signature = $request->header('Authorization');
        return $signature && hash_equals('Basic ' . base64_encode($secret), $signature);
    }

    private function downloadAndStore(string $url, TalkingRender $render): ?string
    {
        try {
            $contents = file_get_contents($url);
            if ($contents === false) return null;
            $path = "lipsync/{$render->shot_id}/{$render->id}.mp4";
            Storage::disk('public')->put($path, $contents);
            return $path;
        } catch (\Throwable $e) {
            Log::warning('D-ID webhook: no se pudo descargar video', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
