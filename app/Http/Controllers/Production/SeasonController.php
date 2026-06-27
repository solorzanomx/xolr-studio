<?php

declare(strict_types=1);

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Season;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SeasonController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'title'       => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status'      => 'required|in:planned,writing,production,completed',
        ]);

        $data['number'] = $project->seasons()->max('number') + 1;

        $project->seasons()->create($data);

        return back()->with('success', 'Temporada añadida.');
    }

    public function show(Project $project, Season $season): Response
    {
        $season->load([
            'project:id,name,slug,type',
            'episodes' => fn($q) => $q->withCount('scenes')->orderBy('number'),
        ]);

        return Inertia::render('Projects/Season', [
            'project' => $project->only('id', 'name', 'slug', 'type'),
            'season'  => $season,
        ]);
    }

    public function update(Request $request, Season $season): RedirectResponse
    {
        $data = $request->validate([
            'title'       => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status'      => 'required|in:planned,writing,production,completed',
        ]);

        $season->update($data);

        return back()->with('success', 'Temporada actualizada.');
    }

    public function destroy(Season $season): RedirectResponse
    {
        $projectId = $season->project_id;
        $season->delete();

        return redirect()->route('projects.show', $projectId)
            ->with('success', 'Temporada eliminada.');
    }
}
