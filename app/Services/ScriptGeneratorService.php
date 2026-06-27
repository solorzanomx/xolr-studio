<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Episode;
use Illuminate\Support\Facades\Http;

class ScriptGeneratorService
{
    public function generateScript(Episode $episode): string
    {
        $episode->loadMissing(['season.project.universeNotes']);

        $project       = $episode->season->project;
        $universeNotes = $project->universeNotes
            ->map(fn($n) => "- {$n->title}: {$n->content}")
            ->join("\n");

        // Contexto de episodios anteriores del mismo season
        $previousEpisodes = $episode->season->episodes()
            ->where('number', '<', $episode->number)
            ->whereNotNull('script')
            ->orderBy('number')
            ->get(['number', 'title', 'logline'])
            ->map(fn($e) => "E{$e->number} - {$e->title}: {$e->logline}")
            ->join("\n");

        $prompt = <<<PROMPT
Eres un guionista profesional para producciones audiovisuales y redes sociales.

PROYECTO: {$project->name}

UNIVERSE BIBLE:
{$universeNotes}

EPISODIOS PREVIOS:
{$previousEpisodes}

EPISODIO #{$episode->number}: {$episode->title}
Logline: {$episode->logline}
Sinopsis: {$episode->synopsis}

Escribe el script completo de este episodio. Incluye:
- Descripciones de escena (ACTION)
- Diálogos completos
- Transiciones
- Notas de dirección en paréntesis

Formato: guion cinematográfico estándar en español.
Extensión: apropiada para el tipo de contenido (3-8 minutos de video aproximadamente).

Responde SOLO con el script, sin explicaciones adicionales.
PROMPT;

        return $this->callClaude($prompt, 6000);
    }

    public function generateBookChapter(Episode $episode): string
    {
        $episode->loadMissing(['season.project.universeNotes']);

        $project = $episode->season->project;
        $script  = strip_tags($episode->script ?? '');

        if (empty($script)) {
            throw new \RuntimeException('El episodio no tiene script. Genera el script primero.');
        }

        $prompt = <<<PROMPT
Eres un novelista. Convierte este guion audiovisual en un capítulo de novela.

PROYECTO: {$project->name}
EPISODIO #{$episode->number}: {$episode->title}

SCRIPT:
{$script}

Transforma el guion en prosa narrativa. Mantén la esencia dramática, expande las descripciones, profundiza en los estados internos de los personajes.
Usa tercera persona limitada. Prosa fluida, literaria pero accesible.

Responde SOLO con el capítulo, sin notas previas ni adicionales.
PROMPT;

        return $this->callClaude($prompt, 5000);
    }

    public function checkContinuity(Episode $episode): array
    {
        $episode->loadMissing(['season.project', 'season.episodes']);

        $previousEpisodes = $episode->season->episodes()
            ->where('number', '<', $episode->number)
            ->whereNotNull('script')
            ->orderByDesc('number')
            ->limit(3)
            ->get(['number', 'title', 'script']);

        if ($previousEpisodes->isEmpty()) {
            return [
                'has_issues'  => false,
                'issues'      => [],
                'suggestions' => ['Este es el primer episodio — no hay episodios anteriores para verificar.'],
            ];
        }

        $currentScript = strip_tags($episode->script ?? '');

        if (empty($currentScript)) {
            throw new \RuntimeException('El episodio actual no tiene script para verificar.');
        }

        $previousContext = $previousEpisodes->map(function ($e): string {
            return "=== E{$e->number}: {$e->title} ===\n" . strip_tags($e->script ?? '');
        })->join("\n\n");

        $prompt = <<<PROMPT
Eres un script editor especializado en continuidad narrativa.

SCRIPT DEL EPISODIO ACTUAL ({$episode->title}):
{$currentScript}

EPISODIOS ANTERIORES (referencia de continuidad):
{$previousContext}

Analiza el script actual en busca de inconsistencias de continuidad.
Busca: cambios de personalidad sin justificación, contradicciones de hechos, errores de timeline, inconsistencias de characterización.

Responde en JSON (sin markdown):
{
  "has_issues": true,
  "issues": [
    {"type": "characterization|timeline|facts|setting", "description": "Descripción del problema", "suggestion": "Cómo corregirlo"}
  ],
  "suggestions": ["Sugerencia general de mejora"]
}
PROMPT;

        $text    = $this->callClaude($prompt, 2000);
        $decoded = json_decode($this->stripMarkdown($text), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'has_issues'  => false,
                'issues'      => [],
                'suggestions' => ['No se pudo analizar la continuidad en este momento.'],
            ];
        }

        return $decoded;
    }

    private function callClaude(string $prompt, int $maxTokens = 4000): string
    {
        $response = Http::withHeaders([
            'x-api-key'         => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(90)->post('https://api.anthropic.com/v1/messages', [
            'model'      => config('services.anthropic.model'),
            'max_tokens' => $maxTokens,
            'messages'   => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Error al conectar con Claude API: ' . $response->status());
        }

        return $response->json('content.0.text', '');
    }

    private function stripMarkdown(string $text): string
    {
        $text = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $text = preg_replace('/\s*```\s*$/m', '', $text);
        return trim($text);
    }
}
