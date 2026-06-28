<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Book;
use App\Models\BookChapter;
use Illuminate\Support\Facades\Http;

class HistoriaService
{
    private function callClaude(string $prompt, int $maxTokens = 3000): string
    {
        $response = Http::withHeaders([
            'x-api-key'         => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(270)->post('https://api.anthropic.com/v1/messages', [
            'model'      => config('services.anthropic.model'),
            'max_tokens' => $maxTokens,
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Error Claude API: ' . $response->status());
        }

        return $response->json('content.0.text', '');
    }

    // Organiza las ideas sueltas en propuestas de capítulos
    public function organizeIdeas(Book $book): array
    {
        $ideas = $book->ideas()
            ->where('converted', false)
            ->orderBy('created_at')
            ->get(['id', 'content', 'tag']);

        if ($ideas->isEmpty()) {
            throw new \RuntimeException('No hay ideas para organizar.');
        }

        $ideasText = $ideas->map(fn($i) => "- [{$i->tag}] {$i->content}")->join("\n");

        $existingChapters = $book->chapters()->get(['sort_order', 'title'])
            ->map(fn($c) => "Cap {$c->sort_order}: {$c->title}")->join("\n");

        $prompt = <<<PROMPT
Eres un editor literario especializado en memorias y autobiografías.

LIBRO: {$book->title}
LOGLINE: {$book->logline}
AUDIENCIA: {$book->target_audience}

CAPÍTULOS EXISTENTES:
{$existingChapters}

IDEAS SIN ORGANIZAR DEL AUTOR:
{$ideasText}

Agrupa estas ideas en capítulos narrativos. Cada capítulo debe tener un arco emocional claro y una premisa que enganche.

Responde en JSON (sin markdown):
{
  "chapters": [
    {
      "title": "Título del capítulo",
      "premise": "De qué trata en una línea — el núcleo emocional",
      "idea_ids": [1, 3, 5],
      "emotional_arc": "De dónde a dónde va emocionalmente el lector",
      "hook": "La primera línea o imagen que abre el capítulo",
      "clue_suggestion": "Qué elemento visual podría funcionar como pista enterrada en el video"
    }
  ],
  "narrative_spine": "En una frase: de qué trata el libro completo como arco mayor"
}
PROMPT;

        $text    = $this->callClaude($prompt, 2000);
        $decoded = json_decode($this->stripJson($text), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('No se pudo parsear la respuesta de organización.');
        }

        return $decoded;
    }

    // Expande las notas del borrador a un capítulo completo
    public function expandChapter(BookChapter $chapter): string
    {
        $book  = $chapter->book;
        $draft = strip_tags($chapter->draft_notes ?? $chapter->content ?? '');

        if (empty($draft)) {
            throw new \RuntimeException('El capítulo no tiene notas para expandir.');
        }

        // Contexto de capítulos anteriores para continuidad
        $prevChapters = $book->chapters()
            ->where('sort_order', '<', $chapter->sort_order)
            ->whereNotNull('content')
            ->orderByDesc('sort_order')
            ->limit(2)
            ->get(['title', 'content'])
            ->map(fn($c) => "=== {$c->title} ===\n" . strip_tags($c->content ?? ''))
            ->join("\n\n");

        $prompt = <<<PROMPT
Eres un escritor de memorias y autobiografías. Tu voz es íntima, cinematográfica y honesta.

LIBRO: {$book->title}
AUDIENCIA: {$book->target_audience}

CAPÍTULOS ANTERIORES (contexto):
{$prevChapters}

CAPÍTULO A ESCRIBIR: {$chapter->title}

NOTAS Y BORRADOR DEL AUTOR (esto es la materia prima — tu voz, tu verdad):
{$draft}

Transforma estas notas en un capítulo completo. Reglas:
- Voz en primera persona, íntima y directa
- Abre con una imagen o momento específico que te mete en la escena (no en la explicación)
- Incluye diálogos si hay personajes — la gente real habla raro, no perfecto
- Alterna entre momento específico y reflexión del "yo ahora" mirando hacia atrás
- Cierra con una frase o imagen que deje algo sin resolver — que enganche al siguiente capítulo
- Extensión: 800–1500 palabras
- Usa lenguaje natural, no académico. Que suene a ti, no a libro de texto

Responde SOLO con el HTML del capítulo usando estas etiquetas:
- <h2> para el título del capítulo
- <p> para párrafos normales
- <p><em>texto</em></p> para reflexiones del "yo actual"
- <blockquote>diálogo o frase memorable</blockquote>
PROMPT;

        $text = $this->callClaude($prompt, 4000);
        return $this->cleanHtml($text);
    }

    // Analiza el potencial de mercado del capítulo como video de YouTube
    public function analyzeMarket(BookChapter $chapter): array
    {
        $book = $chapter->book;
        $content = strip_tags($chapter->content ?? $chapter->draft_notes ?? '');

        $prompt = <<<PROMPT
Eres un estratega de contenido de YouTube especializado en storytelling personal y canales de crecimiento orgánico.

LIBRO: {$book->title}
CAPÍTULO: {$chapter->title}
RESUMEN DEL CONTENIDO: {$content}

Analiza el potencial de este capítulo como video de YouTube. Considera el mercado actual (2025-2026).

Responde en JSON (sin markdown):
{
  "viability_score": 8,
  "viability_reason": "Por qué este tema tiene o no tiene potencial ahora",
  "trending_angle": "El ángulo más potente para YouTube hoy — qué hace que esto sea relevante AHORA",
  "seo_titles": [
    "Título principal optimizado (máx 60 chars)",
    "Variación 2",
    "Variación 3"
  ],
  "keywords": ["keyword1", "keyword2", "keyword3", "keyword4", "keyword5"],
  "best_format": "long-form|short-form|series|documental",
  "format_reason": "Por qué ese formato funciona mejor para este contenido",
  "thumbnail_concept": "Qué imagen o momento del capítulo funcionaría como thumbnail de alto CTR",
  "hook_first_30_seconds": "Cómo abrir el video en los primeros 30 segundos para retener al espectador",
  "competitor_gap": "Qué no está haciendo nadie más con este tema y tú podrías hacer",
  "interlink_potential": "high|medium|low",
  "interlink_reason": "Por qué este capítulo puede generar que la gente quiera ver los demás"
}
PROMPT;

        $text    = $this->callClaude($prompt, 1500);
        $decoded = json_decode($this->stripJson($text), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['viability_score' => 0, 'error' => 'No se pudo analizar en este momento.'];
        }

        return $decoded;
    }

    // Genera el plan de interlinking: qué capítulos referenciar en el video y cómo
    public function generateInterlinking(BookChapter $chapter): array
    {
        $book = $chapter->book;

        $allChapters = $book->chapters()
            ->where('id', '!=', $chapter->id)
            ->whereNotNull('content')
            ->get(['id', 'title', 'sort_order', 'content'])
            ->map(fn($c) => [
                'id'      => $c->id,
                'title'   => $c->title,
                'order'   => $c->sort_order,
                'excerpt' => substr(strip_tags($c->content ?? ''), 0, 300),
            ]);

        if ($allChapters->isEmpty()) {
            return ['links' => [], 'note' => 'Escribe más capítulos para generar interlinking.'];
        }

        $chaptersContext = $allChapters->map(
            fn($c) => "Cap {$c['order']}: {$c['title']} — {$c['excerpt']}"
        )->join("\n\n");

        $currentContent = substr(strip_tags($chapter->content ?? $chapter->draft_notes ?? ''), 0, 500);

        $prompt = <<<PROMPT
Eres un productor de contenido especializado en crear series de videos que enganchan al espectador a ver más.

CAPÍTULO ACTUAL: {$chapter->title}
CONTENIDO: {$currentContent}

OTROS CAPÍTULOS DISPONIBLES:
{$chaptersContext}

Identifica qué capítulos anteriores o posteriores se pueden referenciar en el video de este capítulo para:
1. Crear curiosidad en el espectador de ver otros episodios
2. Hacer que el que no leyó el libro sienta que se está perdiendo algo
3. Construir una narrativa cohesiva que recompense a los espectadores fieles

Responde en JSON (sin markdown):
{
  "links": [
    {
      "chapter_id": 2,
      "chapter_title": "Título del capítulo referenciado",
      "moment_in_video": "En qué momento del video hacer la referencia (inicio/medio/final/cards)",
      "reference_line": "La línea exacta de diálogo o narración que hace la referencia — sin revelar demasiado",
      "emotion": "qué emoción genera en el espectador esta referencia",
      "youtube_card_text": "Texto para el YouTube card (máx 30 chars)"
    }
  ],
  "narrative_thread": "El hilo invisible que conecta todos estos capítulos"
}
PROMPT;

        $text    = $this->callClaude($prompt, 1500);
        $decoded = json_decode($this->stripJson($text), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['links' => [], 'error' => 'No se pudo generar el interlinking.'];
        }

        return $decoded;
    }

    // Sugiere pistas visuales (Easter eggs) para el video del capítulo
    public function suggestClues(BookChapter $chapter): array
    {
        $content = strip_tags($chapter->content ?? $chapter->draft_notes ?? '');

        if (empty($content)) {
            throw new \RuntimeException('El capítulo necesita contenido para sugerir pistas.');
        }

        $prompt = <<<PROMPT
Eres un director creativo que diseña Easter eggs narrativos — pistas visuales enterradas en videos que solo tienen sentido si leíste el libro correspondiente.

CAPÍTULO: {$chapter->title}
CONTENIDO DEL CAPÍTULO:
{$content}

Identifica secretos, revelaciones o detalles importantes del capítulo que podrían convertirse en pistas visuales sutiles en el video.

Reglas para las pistas:
- Deben aparecer en el video SIN protagonismo — de fondo, de pasada, sin que la cámara las resalte
- El espectador normal no las nota o las nota pero no sabe por qué están ahí
- El lector del libro tiene un momento de "espera... ESO es el jarrón del capítulo 3"
- Generan curiosidad en el que no leyó: "¿por qué siempre aparece eso?"

Responde en JSON (sin markdown):
{
  "clues": [
    {
      "book_secret": "Lo que el libro revela (el secreto completo)",
      "visual_element": "El objeto, color, gesto o detalle visual que aparece en el video",
      "placement": "background|subtle|passing",
      "scene_suggestion": "En qué tipo de escena del video debería aparecer",
      "viewer_feeling": "Qué siente el espectador que NO leyó — qué pregunta se genera",
      "reader_payoff": "El momento exacto que tiene el lector cuando lo reconoce"
    }
  ]
}
PROMPT;

        $text    = $this->callClaude($prompt, 1500);
        $decoded = json_decode($this->stripJson($text), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['clues' => []];
        }

        return $decoded;
    }

    private function cleanHtml(string $text): string
    {
        // Si ya es HTML, limpiar code fences
        if (str_contains($text, '</p>') || str_contains($text, '</h')) {
            return trim(preg_replace('/^```[a-z]*\n?|```\s*$/m', '', $text));
        }
        return "<p>{$text}</p>";
    }

    private function stripJson(string $text): string
    {
        $text = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $text = preg_replace('/\s*```\s*$/m', '', $text);
        return trim($text);
    }
}
