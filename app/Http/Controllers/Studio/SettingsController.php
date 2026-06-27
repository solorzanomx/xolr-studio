<?php

declare(strict_types=1);

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function pingRunPod(): JsonResponse
    {
        $apiKey     = config('services.runpod.api_key');
        $endpointId = config('services.runpod.endpoints.image');

        if (! $apiKey) {
            return response()->json(['ok' => false, 'message' => 'RUNPOD_API_KEY no configurado en .env']);
        }

        if (! $endpointId) {
            return response()->json(['ok' => false, 'message' => 'RUNPOD_ENDPOINT_IMAGE no configurado en .env']);
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(10)
                ->get("https://api.runpod.io/v2/{$endpointId}/health");

            if ($response->successful()) {
                $data    = $response->json();
                $workers = $data['workers'] ?? [];
                $ready   = ($workers['ready'] ?? 0);
                $idle    = ($workers['idle'] ?? 0);
                $msg     = "Endpoint activo — {$ready} workers listos, {$idle} idle";
                return response()->json(['ok' => true, 'message' => $msg, 'raw' => $workers]);
            }

            if ($response->status() === 401) {
                return response()->json(['ok' => false, 'message' => 'API Key inválida (401 Unauthorized)']);
            }

            if ($response->status() === 404) {
                return response()->json(['ok' => false, 'message' => "Endpoint ID '{$endpointId}' no encontrado (404)"]);
            }

            return response()->json(['ok' => false, 'message' => "RunPod respondió {$response->status()}"]);

        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'No se pudo conectar: ' . $e->getMessage()]);
        }
    }

    public function __invoke(Request $request): Response
    {
        return Inertia::render('Studio/Settings', [
            'integrations' => [
                'runpod'     => ['label' => 'RunPod',             'description' => 'Render farm — FLUX, video, upscale, lip sync',   'configured' => ! empty(config('services.runpod.api_key'))],
                'elevenlabs' => ['label' => 'ElevenLabs',         'description' => 'Text-to-speech, voice cloning, sound effects',    'configured' => ! empty(config('services.elevenlabs.api_key'))],
                'anthropic'  => ['label' => 'Claude (Anthropic)', 'description' => 'AI Director, Script Generator, captions, SEO',    'configured' => ! empty(config('services.anthropic.key'))],
                'did'        => ['label' => 'D-ID',               'description' => 'Lip sync — calidad Production (~$0.015/seg)',     'configured' => ! empty(config('services.did.api_key'))],
                'suno'       => ['label' => 'Suno',               'description' => 'Generación de música original (plan Pro)',        'configured' => ! empty(config('services.suno.api_key'))],
                'instagram'  => ['label' => 'Instagram / Facebook','description' => 'Publicación automática vía Graph API',           'configured' => ! empty(config('services.instagram.access_token'))],
                'youtube'    => ['label' => 'YouTube',            'description' => 'Subida automática de videos y analytics',         'configured' => ! empty(config('services.youtube.access_token'))],
                'notion'     => ['label' => 'Notion',             'description' => 'Sincronización de episodios y producción',        'configured' => ! empty(config('services.notion.token'))],
            ],
        ]);
    }
}
