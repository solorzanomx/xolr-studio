<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Scene;
use Illuminate\Support\Facades\Http;

class ContinuityCheckerService
{
    public function checkScene(Scene $scene): array
    {
        $scene->loadMissing([
            'shots' => fn($q) => $q->orderBy('sort_order')->orderBy('number')->with([
                'characters:id,name',
                'approvedRender.prompt',
            ]),
            'episode.season.project',
        ]);

        $shots = $scene->shots->filter(fn($s) => $s->approvedRender !== null);

        if ($shots->count() < 2) {
            return [
                'has_issues' => false,
                'issues'     => [],
                'message'    => 'Se necesitan al menos 2 shots con renders aprobados para verificar continuidad.',
            ];
        }

        $shotDescriptions = $shots->map(function ($shot): string {
            $characters = $shot->characters->pluck('name')->join(', ') ?: 'Sin personajes';
            $prompt     = $shot->approvedRender?->prompt?->positive_prompt ?? $shot->description ?? '(sin descripción)';
            return "Shot {$shot->number} [{$shot->shot_type}] — Personajes: {$characters}\nPrompt: {$prompt}";
        })->join("\n\n");

        $sceneLabel = "Escena {$scene->number}: {$scene->title} ({$scene->episode?->title})";

        $prompt = <<<PROMPT
Eres un script supervisor especializado en continuidad visual de producción audiovisual.

Analiza la continuidad visual entre estos shots de la misma escena.

{$sceneLabel}

SHOTS CON SUS PROMPTS DE GENERACIÓN:
{$shotDescriptions}

Busca inconsistencias visuales: cambios de vestuario no justificados, iluminación inconsistente, cambios de aspecto de personajes, props que aparecen/desaparecen, etc.

Responde en JSON (sin markdown):
{
  "has_issues": true,
  "issues": [
    {"shot_from": 1, "shot_to": 3, "type": "costume|lighting|character|prop|setting", "description": "Descripción del problema específico", "suggestion": "Cómo corregirlo"}
  ],
  "summary": "Resumen en 1 oración del estado de continuidad"
}
PROMPT;

        $response = Http::withHeaders([
            'x-api-key'         => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
            'model'      => config('services.anthropic.model'),
            'max_tokens' => 1500,
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Error al conectar con Claude API: ' . $response->status());
        }

        $text = $response->json('content.0.text', '');
        $text = trim(preg_replace('/^```(?:json)?\s*|\s*```\s*$/m', '', $text));

        $decoded = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['has_issues' => false, 'issues' => [], 'summary' => 'No se pudo analizar la continuidad.'];
        }

        return $decoded;
    }
}
