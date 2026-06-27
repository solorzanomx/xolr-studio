<?php

declare(strict_types=1);

namespace App\Http\Controllers\Publishing;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateExportJob;
use App\Models\Campaign;
use App\Models\Episode;
use App\Models\Export;
use App\Models\Project;
use App\Models\Season;
use App\Services\ExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(
        private readonly ExportService $service,
    ) {}

    public function index(Project $project): Response
    {
        $project->loadMissing([
            'seasons.episodes',
            'campaigns',
            'exports' => fn($q) => $q->latest()->limit(20),
        ]);

        return Inertia::render('Publishing/Index', [
            'project'   => $project->only(['id', 'name', 'slug']),
            'seasons'   => $project->seasons->map(fn($s) => [
                'id'       => $s->id,
                'number'   => $s->number,
                'title'    => $s->title ?? "Temporada {$s->number}",
                'episodes' => $s->episodes->map(fn($e) => ['id' => $e->id, 'number' => $e->number, 'title' => $e->title])->values(),
            ])->values(),
            'campaigns' => $project->campaigns->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values(),
            'exports'   => $project->exports,
        ]);
    }

    public function storeZip(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
        ]);

        $export = Export::create([
            'project_id' => $project->id,
            'type'       => 'zip_campaign',
            'status'     => 'pending',
            'created_by' => auth()->id(),
            'metadata'   => ['campaign_id' => $data['campaign_id']],
        ]);

        GenerateExportJob::dispatch($export->id)->onQueue('default');

        return back()->with('success', 'Exportación ZIP en cola. Actualiza en unos segundos.');
    }

    public function storeAnimatic(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'episode_id'     => 'required|exists:episodes,id',
            'shot_durations' => 'nullable|array',
        ]);

        // Update shot durations if provided
        if (! empty($data['shot_durations'])) {
            foreach ($data['shot_durations'] as $shotId => $duration) {
                \App\Models\Shot::where('id', $shotId)->update(['duration_seconds' => max(1, (int) $duration)]);
            }
        }

        $export = Export::create([
            'project_id' => $project->id,
            'type'       => 'animatic',
            'status'     => 'completed',
            'created_by' => auth()->id(),
            'metadata'   => ['episode_id' => $data['episode_id']],
        ]);

        return back()->with('success', 'Animatic guardado.');
    }

    public function getAnimaticSlides(Episode $episode): \Illuminate\Http\JsonResponse
    {
        $slides = $this->service->getAnimaticShots($episode);
        return response()->json(['slides' => $slides]);
    }

    public function download(Export $export): StreamedResponse|RedirectResponse
    {
        if ($export->status !== 'completed' || ! $export->file_path) {
            return back()->withErrors(['export' => 'El archivo no está disponible.']);
        }

        if (! Storage::disk('local')->exists($export->file_path)) {
            return back()->withErrors(['export' => 'El archivo fue eliminado del servidor.']);
        }

        return Storage::disk('local')->download($export->file_path);
    }

    public function destroy(Export $export): RedirectResponse
    {
        if ($export->file_path) {
            Storage::disk('local')->delete($export->file_path);
        }
        $export->delete();

        return back()->with('success', 'Exportación eliminada.');
    }
}
