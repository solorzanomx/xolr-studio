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

        $draft = trim(strip_tags($episode->script ?? ''));

        $draftSection = $draft
            ? "\n\nBORRADOR / NOTAS DEL AUTOR (usa esto como materia prima — desarrolla, expande y transforma en un guion cinematográfico completo, dale estructura, ganchos y tensión dramática):\n{$draft}"
            : '';

        $logline  = $episode->logline  ? "\nLogline: {$episode->logline}"   : '';
        $synopsis = $episode->synopsis ? "\nSinopsis: {$episode->synopsis}" : '';

        $prompt = <<<PROMPT
Eres un guionista profesional para producciones audiovisuales y redes sociales.

PROYECTO: {$project->name}{$logline}{$synopsis}

UNIVERSE BIBLE:
{$universeNotes}

EPISODIOS PREVIOS:
{$previousEpisodes}

EPISODIO #{$episode->number}: {$episode->title}{$draftSection}

Escribe el script completo de este episodio. Incluye:
- Descripciones de escena (ACTION) vívidas y cinematográficas
- Diálogos naturales y con carácter propio
- Transiciones
- Notas de dirección en paréntesis cuando aporten valor
- Gancho de apertura que enganche en los primeros 5 segundos
- Cierre memorable

Extensión: apropiada para el tipo de contenido (3-8 minutos de video aproximadamente).

FORMATO DE SALIDA — usa SOLO estas etiquetas HTML, sin markdown, sin backticks, sin explicaciones:
- <h2>TÍTULO DEL ACTO</h2> para actos (FADE IN, ACT ONE, ACT TWO, etc.)
- <h3>INT. LUGAR — MOMENTO</h3> para encabezados de escena
- <p><strong>NOMBRE PERSONAJE</strong></p> para el nombre del personaje antes de su diálogo
- <p><em>(acotación de actuación o dirección)</em></p> para acotaciones
- <p>texto del diálogo</p> para el diálogo hablado
- <p><em>Descripción de acción o ambiente.</em></p> para descripciones de escena

Responde ÚNICAMENTE con el HTML del script, nada más.
PROMPT;

        return $this->markdownToHtml($this->callClaude($prompt, 6000));
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

    private function markdownToHtml(string $text): string
    {
        // Si ya es HTML (Claude obedeció), lo devolvemos limpio
        if (str_contains($text, '</p>') || str_contains($text, '</h')) {
            return trim(preg_replace('/^```[a-z]*\n?|```\s*$/m', '', $text));
        }

        // Strip code fences y separadores decorativos
        $text = preg_replace('/^```[a-z]*\n?/m', '', $text);
        $text = preg_replace('/^```\s*$/m', '', $text);

        $lines = preg_split('/\r?\n/', $text);
        $html  = '';

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || preg_match('/^[-*_]{3,}$/', $line)) {
                continue;
            }

            // ## Acto / FADE IN
            if (preg_match('/^#{1,2}\s+(.+)$/', $line, $m)) {
                $html .= '<h2>' . $this->inlineFormat($m[1]) . '</h2>';
                continue;
            }

            // ### Encabezado de escena INT./EXT.
            if (preg_match('/^###\s+(.+)$/', $line, $m)) {
                $html .= '<h3>' . $this->inlineFormat($m[1]) . '</h3>';
                continue;
            }

            // Línea completamente en mayúsculas y sin puntuación final → escena o personaje
            if (preg_match('/^[A-ZÁÉÍÓÚÜÑ0-9 \/\.\(\)\-:\'\"]{4,}$/', $line) && ! str_ends_with($line, '.')) {
                $html .= '<h3>' . $this->inlineFormat($line) . '</h3>';
                continue;
            }

            $html .= '<p>' . $this->inlineFormat($line) . '</p>';
        }

        return $html ?: '<p></p>';
    }

    private function inlineFormat(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
        // **bold**
        $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);
        // *(italic)*  or  *italic*
        $text = preg_replace('/\*\((.+?)\)\*/s', '<em>($1)</em>', $text);
        $text = preg_replace('/\*([^*\n]+?)\*/s', '<em>$1</em>', $text);
        // `code` → plain
        $text = preg_replace('/`([^`]+)`/', '$1', $text);
        return $text;
    }

    private function stripMarkdown(string $text): string
    {
        $text = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $text = preg_replace('/\s*```\s*$/m', '', $text);
        return trim($text);
    }
}
