<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ClaudeService
{
    public function generateVideoConcept(string $title, string $projectType, string $projectName): array
    {
        $prompt = <<<PROMPT
Eres un experto en producción de contenido para YouTube, especializado en walking tours y viajes.

Proyecto: "{$projectName}" (tipo: {$projectType})
Título del video: "{$title}"

Genera el concepto completo en formato JSON (sin markdown, solo el JSON):
{
  "hook": "Una oración de gancho inicial potente (máximo 150 caracteres). Debe generar curiosidad o sorpresa.",
  "structure": {
    "intro": "Descripción de la intro (10-15 seg). Qué dirá/mostrará el host para enganchar.",
    "sections": [
      {"title": "Nombre de la sección", "description": "Qué cubre esta sección del walk (1-2 oraciones)", "duration_min": 3},
      {"title": "Nombre", "description": "...", "duration_min": 4},
      {"title": "Nombre", "description": "...", "duration_min": 5},
      {"title": "Nombre", "description": "...", "duration_min": 4},
      {"title": "Nombre", "description": "...", "duration_min": 3}
    ],
    "tips": [
      {"title": "Nombre del tip", "content": "Dato útil para el viajero (1 oración)"},
      {"title": "Nombre del tip", "content": "..."},
      {"title": "Nombre del tip", "content": "..."},
      {"title": "Nombre del tip", "content": "..."},
      {"title": "Nombre del tip", "content": "..."}
    ],
    "cta": "Call to action para el final del video (suscribirse, comentar el destino favorito, etc.)"
  },
  "youtube_seo": {
    "title": "Título principal optimizado para SEO (max 70 chars, incluye keyword + engagement)",
    "title_variants": ["Variante 2 del título", "Variante 3 del título"],
    "description": "Descripción de YouTube (primeras 2-3 líneas antes del 'más', con keyword principal y gancho).",
    "tags": ["tag1", "tag2", "tag3", "tag4", "tag5", "tag6", "tag7", "tag8", "tag9", "tag10"],
    "hashtags": ["#hashtag1", "#hashtag2", "#hashtag3", "#hashtag4", "#hashtag5"],
    "timestamps": [
      {"time": "0:00", "label": "Introducción"},
      {"time": "0:30", "label": "Llegada al destino"}
    ]
  }
}

Responde SOLO con el JSON, sin explicaciones adicionales. Idioma: español.
PROMPT;

        $response = Http::withHeaders([
            'x-api-key'         => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model'      => config('services.anthropic.model'),
            'max_tokens' => 3000,
            'messages'   => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Error al conectar con Claude API: ' . $response->status());
        }

        $text    = $response->json('content.0.text', '');
        $decoded = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Claude devolvió una respuesta inválida.');
        }

        return $decoded;
    }

    public function generateVirtualTalentBio(array $characterData): array
    {
        $name        = $characterData['name'];
        $description = $characterData['description'] ?? '';
        $physical    = $characterData['physical_description'] ?? '';
        $traits      = implode(', ', $characterData['personality_traits'] ?? []);
        $basePrompt  = $characterData['base_prompt'] ?? '';

        $prompt = <<<PROMPT
Eres un copywriter experto en branding personal para talento virtual e influencers de IA.

Crea el perfil de texto para este talento virtual llamado "{$name}":

Descripción: {$description}
Apariencia física: {$physical}
Rasgos de personalidad: {$traits}
Prompt base de generación: {$basePrompt}

Genera exactamente esto en formato JSON (sin markdown, solo el JSON):
{
  "bio_short": "Bio de 1-2 oraciones, máximo 160 caracteres, para perfil de Instagram/redes. Directa, con personalidad.",
  "bio_full": "Bio completa de 3-4 párrafos. Incluye quién es, qué hace, su misión o visión, y qué la hace única. Tono acorde a los rasgos de personalidad.",
  "communication_style": "Guía de estilo de comunicación: tono de voz, vocabulario preferido, emojis que usaría, frases que evita, cómo interactúa con su audiencia.",
  "signature_phrase": "Frase icónica corta (máximo 10 palabras) que define a este talento."
}

Responde SOLO con el JSON, sin explicaciones adicionales.
PROMPT;

        $response = Http::withHeaders([
            'x-api-key'         => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
            'model'      => config('services.anthropic.model'),
            'max_tokens' => 2048,
            'messages'   => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Error al conectar con Claude API: ' . $response->status());
        }

        $text = $response->json('content.0.text', '');

        $decoded = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Claude devolvió una respuesta inválida.');
        }

        return $decoded;
    }
}
