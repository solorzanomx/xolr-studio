<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CameraStyle;
use App\Models\Character;
use App\Models\Episode;
use App\Models\Location;
use App\Models\UniverseNote;
use App\Models\VisualStyle;
use Illuminate\Support\Facades\Http;

class AIDirectorService
{
    public function __construct(
        private readonly GhostDirectorService $ghostDirector,
    ) {}

    public function analyze(Episode $episode, ?array $ghostProfile = null): array
    {
        $episode->load([
            'season.project.universeNotes',
            'scenes.shots',
        ]);

        $project = $episode->season->project;

        // Datos de contexto disponibles
        $characters   = Character::where('is_active', true)->get(['id', 'name', 'type', 'base_prompt', 'lora_trigger_word']);
        $locations    = Location::where('is_active', true)->get(['id', 'name', 'type', 'description']);
        $cameraStyles = CameraStyle::where('is_active', true)->get(['id', 'name', 'prompt_fragment']);
        $visualStyles = VisualStyle::where('is_active', true)->get(['id', 'name', 'description']);

        $universeContext = $project->universeNotes
            ->map(fn($n) => "- {$n->title}: {$n->content}")
            ->join("\n");

        $charactersContext = $characters->map(fn($c) => "- {$c->name} ({$c->type}): {$c->base_prompt}")->join("\n");
        $locationsContext  = $locations->map(fn($l) => "- {$l->name} ({$l->type})")->join("\n");
        $cameraContext     = $cameraStyles->map(fn($s) => "- {$s->name}")->join("\n");
        $visualContext     = $visualStyles->map(fn($s) => "- {$s->name}: {$s->description}")->join("\n");

        $ghostSection = $ghostProfile
            ? $this->ghostDirector->formatForPrompt($ghostProfile)
            : 'Sin historial de renders previos.';

        $script   = strip_tags($episode->script ?? '');
        $logline  = $episode->logline ?? '';
        $synopsis = $episode->synopsis ?? '';

        $scriptSection = $script
            ? "SCRIPT COMPLETO:\n{$script}"
            : ($synopsis
                ? "SINOPSIS:\n{$synopsis}"
                : "LOGLINE:\n{$logline}");

        $prompt = <<<PROMPT
Eres un director de producción audiovisual especializado en contenido para redes sociales.

PROYECTO: {$project->name}

UNIVERSE BIBLE:
{$universeContext}

EPISODIO #{$episode->number}: {$episode->title}
{$scriptSection}

RECURSOS DISPONIBLES:

PERSONAJES:
{$charactersContext}

LOCACIONES:
{$locationsContext}

ESTILOS DE CÁMARA DISPONIBLES:
{$cameraContext}

ESTILOS VISUALES DISPONIBLES:
{$visualContext}

PERFIL CREATIVO (Ghost Director):
{$ghostSection}

TAREA: Analiza el contenido y genera la estructura de producción completa en JSON.
Crea entre 2-5 escenas, cada una con 2-5 shots. Usa SOLO los nombres exactos de personajes, locaciones y estilos listados arriba.

Responde SOLO con este JSON (sin markdown):
{
  "summary": "Resumen de la propuesta de dirección en 1-2 oraciones",
  "total_scenes": 3,
  "total_shots": 10,
  "scenes": [
    {
      "number": 1,
      "title": "Título de la escena",
      "description": "Descripción de la escena",
      "location_name": "Nombre exacto de locación o null",
      "time_of_day": "day|night|dusk|dawn|unspecified",
      "mood": "calm|tense|dramatic|joyful|mysterious|romantic",
      "shots": [
        {
          "number": 1,
          "description": "Descripción visual del shot",
          "shot_type": "image|video|talking",
          "purpose": "narrative|hero|thumbnail|social|talking_dialogue",
          "character_names": ["Nombre exacto del personaje"],
          "camera_style_name": "Nombre exacto del estilo de cámara o null",
          "visual_style_name": "Nombre exacto del estilo visual o null",
          "director_notes": "Notas técnicas de dirección",
          "dialogue_text": "Texto del diálogo si shot_type es talking, sino null",
          "duration_seconds": null
        }
      ]
    }
  ]
}
PROMPT;

        $response = Http::withHeaders([
            'x-api-key'         => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(90)->post('https://api.anthropic.com/v1/messages', [
            'model'      => config('services.anthropic.model'),
            'max_tokens' => 4000,
            'messages'   => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Error al conectar con Claude API: ' . $response->status());
        }

        $text    = $response->json('content.0.text', '');
        $decoded = json_decode($this->stripMarkdown($text), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Claude devolvió JSON inválido: ' . json_last_error_msg());
        }

        if (empty($decoded['scenes'])) {
            throw new \RuntimeException('Claude no generó escenas en la respuesta.');
        }

        return $decoded;
    }

    private function stripMarkdown(string $text): string
    {
        $text = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $text = preg_replace('/\s*```\s*$/m', '', $text);
        return trim($text);
    }
}
