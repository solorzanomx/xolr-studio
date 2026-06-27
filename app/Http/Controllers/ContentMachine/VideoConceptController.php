<?php

declare(strict_types=1);

namespace App\Http\Controllers\ContentMachine;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Shot;
use App\Models\VideoConcept;
use App\Models\VideoSeries;
use App\Services\ClaudeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VideoConceptController extends Controller
{
    public function index(Request $request): Response
    {
        $projectId = $request->project ?? Project::where('type', 'content_machine')->value('id');

        $concepts = VideoConcept::with('series:id,name')
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->when($request->status,  fn($q, $v) => $q->where('status', $v))
            ->when($request->series,  fn($q, $v) => $q->where('video_series_id', $v))
            ->when($request->rating,  fn($q, $v) => $q->where('rating', $v))
            ->when($request->search,  fn($q, $v) => $q->where('title', 'like', "%{$v}%"))
            ->withCount('thumbnailShots')
            ->orderByRaw('ISNULL(rating), rating DESC')
            ->latest()
            ->paginate(18)
            ->withQueryString();

        $projects = Project::select('id', 'name', 'type')->orderBy('name')->get();
        $series   = VideoSeries::when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->withCount('concepts')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('ContentMachine/Index', [
            'concepts'          => $concepts,
            'projects'          => $projects,
            'series'            => $series,
            'activeProjectId'   => $projectId ? (int) $projectId : null,
            'filters'           => $request->only(['search', 'status', 'series', 'rating', 'project']),
        ]);
    }

    public function create(Request $request): Response
    {
        $projectId = $request->project_id;

        return Inertia::render('ContentMachine/Create', [
            'projects' => Project::select('id', 'name', 'type')->orderBy('name')->get(),
            'series'   => $projectId
                ? VideoSeries::where('project_id', $projectId)->orderBy('sort_order')->get(['id', 'name'])
                : collect(),
            'selectedProjectId' => $projectId ? (int) $projectId : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_id'      => 'required|exists:projects,id',
            'video_series_id' => 'nullable|exists:video_series,id',
            'title'           => 'required|string|max:255',
            'hook'            => 'nullable|string',
            'status'          => 'required|in:idea,scripted,production,published',
            'rating'          => 'nullable|integer|min:1|max:5',
        ]);

        $concept = VideoConcept::create($data);

        return redirect()->route('content-machine.concepts.show', $concept)
            ->with('success', 'Concepto creado.');
    }

    public function show(VideoConcept $concept): Response
    {
        $concept->load(['project:id,name,type', 'series:id,name']);

        $thumbnailShots = Shot::where('video_concept_id', $concept->id)
            ->where('purpose', 'thumbnail')
            ->orderBy('sort_order')
            ->get();

        $series = VideoSeries::where('project_id', $concept->project_id)
            ->orderBy('sort_order')
            ->get(['id', 'name']);

        return Inertia::render('ContentMachine/Show', [
            'concept'        => $concept,
            'thumbnailShots' => $thumbnailShots,
            'series'         => $series,
        ]);
    }

    public function edit(VideoConcept $concept): Response
    {
        return Inertia::render('ContentMachine/Edit', [
            'concept'  => $concept->load('series:id,name'),
            'projects' => Project::select('id', 'name', 'type')->orderBy('name')->get(),
            'series'   => VideoSeries::where('project_id', $concept->project_id)
                ->orderBy('sort_order')
                ->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, VideoConcept $concept): RedirectResponse
    {
        $data = $request->validate([
            'project_id'      => 'required|exists:projects,id',
            'video_series_id' => 'nullable|exists:video_series,id',
            'title'           => 'required|string|max:255',
            'hook'            => 'nullable|string',
            'structure'       => 'nullable|array',
            'youtube_seo'     => 'nullable|array',
            'status'          => 'required|in:idea,scripted,production,published',
            'rating'          => 'nullable|integer|min:1|max:5',
        ]);

        $concept->update($data);

        return back()->with('success', 'Concepto actualizado.');
    }

    public function destroy(VideoConcept $concept): RedirectResponse
    {
        $concept->delete();

        return redirect()->route('content-machine.index')
            ->with('success', 'Concepto eliminado.');
    }

    public function generate(Request $request, VideoConcept $concept): RedirectResponse
    {
        try {
            $service = app(ClaudeService::class);
            $result  = $service->generateVideoConcept(
                $concept->title,
                $concept->project->type ?? 'content_machine',
                $concept->project->name ?? 'Xolr Studio'
            );

            $concept->update([
                'hook'        => $result['hook'] ?? $concept->hook,
                'structure'   => $result['structure'] ?? $concept->structure,
                'youtube_seo' => $result['youtube_seo'] ?? $concept->youtube_seo,
            ]);

            return back()->with('success', 'Concepto generado con IA.');
        } catch (\Throwable $e) {
            return back()->withErrors(['ai' => $e->getMessage()]);
        }
    }

    public function addThumbnailShot(Request $request, VideoConcept $concept): RedirectResponse
    {
        $data = $request->validate([
            'description' => 'nullable|string|max:500',
        ]);

        $number = ($concept->shots()->max('number') ?? 0) + 1;

        Shot::create([
            'video_concept_id' => $concept->id,
            'number'           => $number,
            'sort_order'       => $number,
            'description'      => $data['description'] ?? "Thumbnail variante {$number}",
            'shot_type'        => 'image',
            'purpose'          => 'thumbnail',
            'status'           => 'draft',
        ]);

        return back()->with('success', 'Thumbnail añadido.');
    }
}
