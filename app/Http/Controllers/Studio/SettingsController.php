<?php

declare(strict_types=1);

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function pingRunPod(): JsonResponse
    {
        $apiKey     = config('services.runpod.api_key');
        $endpointId = config('services.runpod.endpoints.image');

        $steps = [
            'api_key'  => ['ok' => false, 'label' => 'API Key',  'message' => ''],
            'endpoint' => ['ok' => false, 'label' => 'Endpoint', 'message' => ''],
        ];

        // ── Paso 1: verificar API Key vía GraphQL (JSON) ─────────────────
        if (! $apiKey) {
            $steps['api_key']['message']  = 'RUNPOD_API_KEY no está en .env';
            $steps['endpoint']['message'] = 'Pendiente de API key';
            return response()->json(['ok' => false, 'steps' => $steps]);
        }

        try {
            $gql = Http::timeout(10)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("https://api.runpod.io/graphql?api_key={$apiKey}", [
                    'query' => '{ myself { id } }',
                ]);

            $body   = $gql->json();
            $errors = $body['errors'] ?? null;
            $id     = $body['data']['myself']['id'] ?? null;

            if ($id) {
                $steps['api_key']['ok']      = true;
                $steps['api_key']['message'] = "Válida — cuenta ID: {$id}";
            } elseif ($gql->status() === 401) {
                $steps['api_key']['message']  = 'API Key inválida (401) — verifica que la copiaste completa';
                $steps['endpoint']['message'] = 'Pendiente de API key válida';
                return response()->json(['ok' => false, 'steps' => $steps]);
            } elseif ($errors) {
                $errMsg = $errors[0]['message'] ?? 'error desconocido';
                $steps['api_key']['message']  = "GraphQL error: {$errMsg}";
                $steps['endpoint']['message'] = 'No verificado';
                return response()->json(['ok' => false, 'steps' => $steps]);
            } else {
                // Si no hay error explícito pero tampoco datos, asumimos key ok
                // y dejamos que el health del endpoint confirme
                $steps['api_key']['ok']      = true;
                $steps['api_key']['message'] = 'Aceptada (verificación vía endpoint)';
            }
        } catch (\Throwable $e) {
            $steps['api_key']['message']  = 'Sin conexión a RunPod: ' . $e->getMessage();
            $steps['endpoint']['message'] = 'No verificado';
            return response()->json(['ok' => false, 'steps' => $steps]);
        }

        // ── Paso 2: verificar Endpoint ───────────────────────────────────
        if (! $endpointId) {
            $steps['endpoint']['message'] = 'RUNPOD_ENDPOINT_IMAGE no está en .env';
            return response()->json(['ok' => false, 'steps' => $steps]);
        }

        try {
            $health = Http::withToken($apiKey)
                ->timeout(10)
                ->get("https://api.runpod.ai/v2/{$endpointId}/health");

            if ($health->successful()) {
                $workers = $health->json()['workers'] ?? [];
                $ready   = $workers['ready']   ?? 0;
                $idle    = $workers['idle']     ?? 0;
                $running = $workers['running']  ?? 0;
                $steps['endpoint']['ok']      = true;
                $steps['endpoint']['message'] = "ID: {$endpointId} — ready:{$ready} idle:{$idle} running:{$running}";
                return response()->json(['ok' => true, 'steps' => $steps, 'workers' => $workers]);
            }

            if ($health->status() === 404) {
                $steps['endpoint']['message'] = "Endpoint '{$endpointId}' no encontrado — verifica el ID";
            } else {
                $steps['endpoint']['message'] = "RunPod respondió {$health->status()}";
            }

        } catch (\Throwable $e) {
            $steps['endpoint']['message'] = 'Error al consultar health: ' . $e->getMessage();
        }

        return response()->json(['ok' => false, 'steps' => $steps]);
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
            'comfyui_url'    => config('services.comfyui.base_url', ''),
            'worker_running' => $this->workerRunning(),
        ]);
    }

    // ── ComfyUI Pod ──────────────────────────────────────────────────

    public function updateComfyUrl(Request $request): JsonResponse
    {
        $url = rtrim((string) $request->input('url', ''), '/');

        if ($url && ! filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json(['ok' => false, 'message' => 'URL inválida'], 422);
        }

        $this->writeEnvValue('COMFYUI_BASE_URL', $url);

        Artisan::call('config:cache');
        Artisan::call('queue:restart');

        return response()->json(['ok' => true, 'url' => $url]);
    }

    public function pingComfyUI(): JsonResponse
    {
        $url = config('services.comfyui.base_url');

        if (! $url) {
            return response()->json(['ok' => false, 'message' => 'URL no configurada — guarda la URL del Pod primero']);
        }

        try {
            $stats = Http::timeout(8)->get("{$url}/system_stats");

            if (! $stats->successful()) {
                return response()->json(['ok' => false, 'message' => "ComfyUI respondió {$stats->status()} — verifica que el Pod esté encendido"]);
            }

            $queue   = Http::timeout(5)->get("{$url}/queue")->json();
            $running = count($queue['queue_running'] ?? []);
            $pending = count($queue['queue_pending'] ?? []);

            return response()->json([
                'ok'      => true,
                'message' => "Conectado — running:{$running} pending:{$pending}",
            ]);

        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Sin conexión: ' . $e->getMessage()]);
        }
    }

    public function workerStatus(): JsonResponse
    {
        return response()->json(['running' => $this->workerRunning()]);
    }

    public function restartWorker(): JsonResponse
    {
        Artisan::call('queue:restart');
        sleep(2);

        if (! $this->workerRunning()) {
            $artisan = base_path('artisan');
            $cmd     = "nohup php {$artisan} queue:work --queue=renders,default --sleep=3 --tries=3 >> /var/log/xolrstudio-queue.log 2>&1 &";

            if (function_exists('proc_open')) {
                $desc = [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']];
                $proc = proc_open('bash -c ' . escapeshellarg($cmd), $desc, $pipes);
                if (is_resource($proc)) {
                    proc_close($proc);
                    sleep(1);
                }
            }
        }

        return response()->json(['ok' => true, 'running' => $this->workerRunning()]);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function workerRunning(): bool
    {
        foreach (glob('/proc/*/cmdline') ?: [] as $file) {
            $cmd = @file_get_contents($file);
            if ($cmd && str_contains($cmd, 'queue:work') && str_contains($cmd, 'xolrstudio')) {
                return true;
            }
        }
        return false;
    }

    private function writeEnvValue(string $key, string $value): void
    {
        $path    = base_path('.env');
        $content = file_get_contents($path);

        if (preg_match("/^{$key}=/m", $content)) {
            $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
        } else {
            $content .= "\n{$key}={$value}\n";
        }

        file_put_contents($path, $content);
    }
}
