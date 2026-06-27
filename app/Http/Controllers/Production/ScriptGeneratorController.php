<?php

declare(strict_types=1);

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Services\ScriptGeneratorService;
use Illuminate\Http\RedirectResponse;

class ScriptGeneratorController extends Controller
{
    public function __construct(
        private readonly ScriptGeneratorService $service,
    ) {}

    public function generateScript(Episode $episode): RedirectResponse
    {
        try {
            $script = $this->service->generateScript($episode);
            $episode->update(['script' => $script]);

            return redirect()->route('episodes.show', $episode)
                ->with('success', 'Script generado por IA y guardado.');
        } catch (\Throwable $e) {
            return back()->withErrors(['script' => $e->getMessage()]);
        }
    }

    public function generateBookChapter(Episode $episode): RedirectResponse
    {
        try {
            $chapter = $this->service->generateBookChapter($episode);

            return redirect()->route('episodes.show', $episode)
                ->with('book_chapter', $chapter)
                ->with('success', 'Capítulo de libro generado.');
        } catch (\Throwable $e) {
            return back()->withErrors(['chapter' => $e->getMessage()]);
        }
    }

    public function checkContinuity(Episode $episode): RedirectResponse
    {
        try {
            $result = $this->service->checkContinuity($episode);

            return redirect()->route('episodes.show', $episode)
                ->with('continuity_result', $result)
                ->with('success', 'Verificación de continuidad completada.');
        } catch (\Throwable $e) {
            return back()->withErrors(['continuity' => $e->getMessage()]);
        }
    }
}
