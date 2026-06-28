<?php

declare(strict_types=1);

namespace App\Http\Controllers\Historia;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateHistoriaJob;
use App\Models\Book;
use App\Models\BookChapter;
use App\Models\BookClue;
use App\Models\BookIdea;
use App\Models\Episode;
use App\Services\HistoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class HistoriaController extends Controller
{
    public function __construct(
        private readonly HistoriaService $service,
    ) {}

    // ── Libro / Overview ────────────────────────────────────────

    public function index(): Response
    {
        $book = Book::firstOrCreate(
            ['user_id' => auth()->id()],
            ['title' => 'Mi Historia', 'status' => 'writing'],
        );

        $book->load([
            'chapters' => fn($q) => $q->withCount('clues'),
            'ideas'    => fn($q) => $q->where('converted', false)->orderByDesc('created_at')->limit(20),
        ]);

        return Inertia::render('Historia/Index', [
            'book'     => $book,
            'chapters' => $book->chapters,
            'ideas'    => $book->ideas,
            'episodes' => Episode::with('season.project:id,name')
                ->orderByDesc('id')
                ->get(['id', 'title', 'number', 'season_id']),
        ]);
    }

    public function updateBook(Request $request): RedirectResponse
    {
        $book = Book::where('user_id', auth()->id())->firstOrFail();
        $book->update($request->only(['title', 'logline', 'target_audience']));

        return back()->with('success', 'Libro actualizado.');
    }

    // ── Ideas ────────────────────────────────────────────────────

    public function storeIdea(Request $request): JsonResponse
    {
        $request->validate(['content' => 'required|string|max:2000', 'tag' => 'nullable|string|max:50']);
        $book = Book::where('user_id', auth()->id())->firstOrFail();

        $idea = $book->ideas()->create($request->only(['content', 'tag']));

        return response()->json(['ok' => true, 'idea' => $idea]);
    }

    public function destroyIdea(BookIdea $bookIdea): JsonResponse
    {
        $bookIdea->delete();
        return response()->json(['ok' => true]);
    }

    public function organizeIdeas(): JsonResponse
    {
        $book = Book::where('user_id', auth()->id())->firstOrFail();
        $key  = "historia_organize_{$book->id}";
        $current = Cache::get($key);

        if (isset($current['status']) && $current['status'] === 'generating') {
            return response()->json(['ok' => true, 'queued' => true, 'status' => 'generating']);
        }

        Cache::put($key, ['status' => 'queued'], now()->addMinutes(10));
        GenerateHistoriaJob::dispatch($book->id, 'organize')->onQueue('default');

        return response()->json(['ok' => true, 'queued' => true]);
    }

    public function organizeStatus(): JsonResponse
    {
        $book = Book::where('user_id', auth()->id())->firstOrFail();
        $key  = "historia_organize_{$book->id}";
        $data = Cache::get($key);

        return response()->json($data ?? ['status' => 'idle']);
    }

    // ── Capítulos ────────────────────────────────────────────────

    public function storeChapter(Request $request): RedirectResponse
    {
        $request->validate(['title' => 'required|string|max:200']);
        $book = Book::where('user_id', auth()->id())->firstOrFail();

        $maxOrder = $book->chapters()->max('sort_order') ?? 0;

        $chapter = $book->chapters()->create([
            'title'      => $request->title,
            'sort_order' => $maxOrder + 1,
            'status'     => 'idea',
        ]);

        return redirect()->route('historia.chapters.show', $chapter);
    }

    public function showChapter(BookChapter $bookChapter): Response
    {
        $bookChapter->load(['book', 'clues.shot', 'episode.season.project']);

        $episodes = Episode::with('season.project:id,name')
            ->orderByDesc('id')
            ->get(['id', 'title', 'number', 'season_id']);

        $allChapters = $bookChapter->book->chapters()
            ->where('id', '!=', $bookChapter->id)
            ->get(['id', 'title', 'sort_order']);

        return Inertia::render('Historia/Chapter', [
            'chapter'     => $bookChapter,
            'allChapters' => $allChapters,
            'episodes'    => $episodes,
        ]);
    }

    public function updateChapter(Request $request, BookChapter $bookChapter): JsonResponse
    {
        $bookChapter->update($request->only([
            'title', 'content', 'draft_notes', 'status', 'seo_title', 'episode_id',
        ]));

        return response()->json(['ok' => true]);
    }

    public function destroyChapter(BookChapter $bookChapter): RedirectResponse
    {
        $bookChapter->delete();
        return redirect()->route('historia.index')->with('success', 'Capítulo eliminado.');
    }

    public function reorderChapters(Request $request): JsonResponse
    {
        $request->validate(['order' => 'required|array']);
        foreach ($request->order as $position => $chapterId) {
            BookChapter::where('id', $chapterId)->update(['sort_order' => $position + 1]);
        }
        return response()->json(['ok' => true]);
    }

    // ── AI: Expandir capítulo ────────────────────────────────────

    public function expandChapter(BookChapter $bookChapter): JsonResponse
    {
        $key     = "historia_expand_{$bookChapter->id}";
        $current = Cache::get($key);

        if (isset($current['status']) && $current['status'] === 'generating') {
            return response()->json(['ok' => true, 'queued' => true, 'status' => 'generating']);
        }

        Cache::put($key, ['status' => 'queued'], now()->addMinutes(10));
        GenerateHistoriaJob::dispatch($bookChapter->id, 'expand')->onQueue('default');

        return response()->json(['ok' => true, 'queued' => true]);
    }

    public function expandStatus(BookChapter $bookChapter): JsonResponse
    {
        $key  = "historia_expand_{$bookChapter->id}";
        $data = Cache::get($key);
        return response()->json($data ?? ['status' => 'idle']);
    }

    // ── AI: Market Intel ─────────────────────────────────────────

    public function marketIntel(BookChapter $bookChapter): JsonResponse
    {
        $key     = "historia_market_{$bookChapter->id}";
        $current = Cache::get($key);

        if (isset($current['status']) && $current['status'] === 'generating') {
            return response()->json(['ok' => true, 'queued' => true, 'status' => 'generating']);
        }

        Cache::put($key, ['status' => 'queued'], now()->addMinutes(10));
        GenerateHistoriaJob::dispatch($bookChapter->id, 'market')->onQueue('default');

        return response()->json(['ok' => true, 'queued' => true]);
    }

    public function marketStatus(BookChapter $bookChapter): JsonResponse
    {
        $key  = "historia_market_{$bookChapter->id}";
        $data = Cache::get($key);
        return response()->json($data ?? ['status' => 'idle']);
    }

    // ── AI: Interlinking ─────────────────────────────────────────

    public function generateInterlinking(BookChapter $bookChapter): JsonResponse
    {
        try {
            $result = $this->service->generateInterlinking($bookChapter);
            $bookChapter->update(['interlinks' => $result]);
            return response()->json(['ok' => true, 'result' => $result]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── AI: Sugerir pistas ───────────────────────────────────────

    public function suggestClues(BookChapter $bookChapter): JsonResponse
    {
        try {
            $result = $this->service->suggestClues($bookChapter);
            return response()->json(['ok' => true, 'clues' => $result['clues'] ?? []]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── Pistas (CRUD) ────────────────────────────────────────────

    public function storeClue(Request $request, BookChapter $bookChapter): JsonResponse
    {
        $request->validate([
            'book_secret'     => 'required|string',
            'visual_element'  => 'required|string',
            'placement'       => 'required|in:background,subtle,passing',
            'viewer_feeling'  => 'nullable|string',
            'reader_payoff'   => 'nullable|string',
            'shot_id'         => 'nullable|integer|exists:shots,id',
        ]);

        $clue = $bookChapter->clues()->create($request->all());

        return response()->json(['ok' => true, 'clue' => $clue]);
    }

    public function updateClue(Request $request, BookClue $bookClue): JsonResponse
    {
        $bookClue->update($request->only([
            'book_secret', 'visual_element', 'placement',
            'viewer_feeling', 'reader_payoff', 'shot_id',
        ]));

        return response()->json(['ok' => true]);
    }

    public function destroyClue(BookClue $bookClue): JsonResponse
    {
        $bookClue->delete();
        return response()->json(['ok' => true]);
    }
}
