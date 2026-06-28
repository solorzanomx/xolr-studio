<?php

declare(strict_types=1);

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\Location;
use App\Models\Season;
use App\Models\ShareToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EpisodeController extends Controller
{
    public function store(Request $request, Season $season): RedirectResponse
    {
        $data = $request->validate([
            'title'    => 'required|string|max:255',
            'logline'  => 'nullable|string',
            'status'   => 'required|in:concept,outline,scripted,production,completed,published',
        ]);

        $data['number'] = $season->episodes()->max('number') + 1;

        $season->episodes()->create($data);

        return back()->with('success', 'Episodio añadido.');
    }

    public function show(Episode $episode): Response
    {
        $episode->load([
            'season.project:id,name,slug',
            'scenes' => fn($q) => $q->with([
                'location:id,name',
                'shots' => fn($q) => $q->with([
                    'approvedRender:id,file_path,width,height,quality_tier',
                    'renders' => fn($q) => $q->where('status', 'completed')->latest()->limit(1),
                ])->orderBy('sort_order')->orderBy('number'),
            ])->orderBy('sort_order')->orderBy('number'),
        ]);

        $shareTokens = ShareToken::where('shareable_type', Episode::class)
            ->where('shareable_id', $episode->id)
            ->latest()
            ->get(['id', 'token', 'label', 'expires_at', 'view_count', 'password_hash'])
            ->map(fn($t) => [
                'id'         => $t->id,
                'token'      => $t->token,
                'label'      => $t->label,
                'expires_at' => $t->expires_at?->toDateString(),
                'view_count' => $t->view_count,
                'protected'  => $t->password_hash !== null,
                'expired'    => $t->isExpired(),
                'url'        => url('/preview/' . $t->token),
            ]);

        return Inertia::render('Episodes/Show', [
            'episode'     => $episode,
            'locations'   => Location::where('is_active', true)->get(['id', 'name', 'type']),
            'shareTokens' => $shareTokens,
        ]);
    }

    public function edit(Episode $episode): Response
    {
        $episode->load('season.project:id,name,slug');

        return Inertia::render('Episodes/Edit', [
            'episode' => $episode,
        ]);
    }

    public function update(Request $request, Episode $episode): RedirectResponse
    {
        $data = $request->validate([
            'title'    => 'required|string|max:255',
            'logline'  => 'nullable|string',
            'synopsis' => 'nullable|string',
            'status'   => 'required|in:concept,outline,scripted,production,completed,published',
        ]);

        $episode->update($data);

        return redirect()->route('episodes.show', $episode)
            ->with('success', 'Episodio actualizado.');
    }

    public function updateScript(Request $request, Episode $episode): RedirectResponse
    {
        $request->validate(['script' => 'nullable|string']);

        $episode->update(['script' => $request->script]);

        return back()->with('success', 'Script guardado.');
    }

    public function destroy(Episode $episode): RedirectResponse
    {
        $seasonId  = $episode->season_id;
        $projectId = $episode->season->project_id;
        $episode->delete();

        return redirect()->route('projects.seasons.show', [$projectId, $seasonId])
            ->with('success', 'Episodio eliminado.');
    }
}
