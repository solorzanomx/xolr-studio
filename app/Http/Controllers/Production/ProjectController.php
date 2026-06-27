<?php

declare(strict_types=1);

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\VisualStyle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $projects = Project::query()
            ->withCount(['seasons', 'campaigns', 'videoConcepts'])
            ->when($request->search, fn($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when($request->type, fn($q, $v) => $q->where('type', $v))
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate(18)
            ->withQueryString();

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'filters'  => $request->only(['search', 'type', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Projects/Create', [
            'visualStyles' => VisualStyle::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'type'                => 'required|in:fiction_series,youtube_channel,real_estate,commercial,corporate,social_media,client',
            'description'         => 'nullable|string',
            'synopsis'            => 'nullable|string',
            'visual_style_id'     => 'nullable|exists:visual_styles,id',
            'status'              => 'required|in:development,pre_production,production,post_production,completed,archived',
            'brand_colors'        => 'nullable|array',
            'monthly_budget_usd'  => 'nullable|numeric|min:0',
        ]);

        $data['slug']       = Str::slug($data['name']);
        $data['created_by'] = $request->user()->id;

        $project = Project::create($data);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Proyecto creado.');
    }

    public function show(Project $project): Response
    {
        $project->load([
            'seasons' => fn($q) => $q->withCount('episodes')->orderBy('number'),
            'createdBy:id,name',
        ]);

        return Inertia::render('Projects/Show', [
            'project' => $project,
        ]);
    }

    public function edit(Project $project): Response
    {
        return Inertia::render('Projects/Edit', [
            'project'      => $project,
            'visualStyles' => VisualStyle::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'type'                => 'required|in:fiction_series,youtube_channel,real_estate,commercial,corporate,social_media,client',
            'description'         => 'nullable|string',
            'synopsis'            => 'nullable|string',
            'visual_style_id'     => 'nullable|exists:visual_styles,id',
            'status'              => 'required|in:development,pre_production,production,post_production,completed,archived',
            'brand_colors'        => 'nullable|array',
            'monthly_budget_usd'  => 'nullable|numeric|min:0',
        ]);

        $project->update($data);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Proyecto actualizado.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Proyecto eliminado.');
    }
}
