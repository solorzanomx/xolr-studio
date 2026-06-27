<?php

declare(strict_types=1);

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Jobs\SubmitRenderJob;
use App\Models\Project;
use App\Models\Render;
use App\Models\Shot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class RenderController extends Controller
{
    public function index(Request $request): Response
    {
        $projects = Project::orderBy('name')->get(['id', 'name']);

        $query = Render::with([
            'shot:id,scene_id,label,shot_type',
            'shot.scene:id,episode_id,title',
            'shot.scene.episode:id,season_id,title',
            'shot.scene.episode.season:id,project_id',
            'shot.scene.episode.season.project:id,name',
            'shot.characters:id,name',
        ]);

        if ($request->filled('project_id')) {
            $query->whereHas('shot.scene.episode.season', fn($q) => $q->where('project_id', $request->project_id));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('quality_tier')) {
            $query->where('quality_tier', $request->quality_tier);
        }

        if ($request->filled('file_type')) {
            $query->where('file_type', $request->file_type);
        }

        if ($request->filled('approved')) {
            $query->where('is_approved', $request->approved === '1');
        }

        $renders = $query->latest()->paginate(24)->through(function (Render $r): array {
            $filePath = $r->file_path;
            $url      = $filePath
                ? (str_starts_with($filePath, 'http') ? $filePath : Storage::disk('public')->url($filePath))
                : null;

            return [
                'id'           => $r->id,
                'status'       => $r->status,
                'quality_tier' => $r->quality_tier,
                'file_type'    => $r->file_type,
                'is_approved'  => $r->is_approved,
                'user_rating'  => $r->user_rating,
                'gpu_cost_usd' => $r->gpu_cost_usd,
                'width'        => $r->width,
                'height'       => $r->height,
                'url'          => $url,
                'created_at'   => $r->created_at->toDateTimeString(),
                'shot'         => [
                    'id'        => $r->shot?->id,
                    'label'     => $r->shot?->label,
                    'shot_type' => $r->shot?->shot_type,
                    'characters'=> $r->shot?->characters->pluck('name'),
                    'project'   => $r->shot?->scene?->episode?->season?->project?->only(['id', 'name']),
                    'episode'   => $r->shot?->scene?->episode?->only(['id', 'title']),
                ],
            ];
        });

        // Stats aggregate (for active filters)
        $statsQuery = Render::when($request->filled('project_id'), fn($q) => $q->whereHas(
            'shot.scene.episode.season', fn($q2) => $q2->where('project_id', $request->project_id)
        ));

        $stats = [
            'total'        => $statsQuery->count(),
            'approved'     => (clone $statsQuery)->where('is_approved', true)->count(),
            'completed'    => (clone $statsQuery)->where('status', 'completed')->count(),
            'failed'       => (clone $statsQuery)->where('status', 'failed')->count(),
            'total_cost'   => round((float) ($statsQuery->sum('gpu_cost_usd') ?? 0), 4),
            'by_tier'      => (clone $statsQuery)->selectRaw('quality_tier, count(*) as cnt')->groupBy('quality_tier')->pluck('cnt', 'quality_tier'),
        ];

        return Inertia::render('Renders/Index', [
            'renders'  => $renders,
            'projects' => $projects,
            'stats'    => $stats,
            'filters'  => $request->only(['project_id', 'status', 'quality_tier', 'file_type', 'approved']),
        ]);
    }

    public function bulkApprove(Request $request): RedirectResponse
    {
        $ids = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer|exists:renders,id'])['ids'];

        foreach ($ids as $id) {
            $render = Render::find($id);
            if (! $render) continue;

            Render::where('shot_id', $render->shot_id)->where('id', '!=', $render->id)->update(['is_approved' => false]);
            $render->update(['is_approved' => true]);
            $render->shot->update(['approved_render_id' => $render->id]);
        }

        return back()->with('success', count($ids) . ' renders aprobados.');
    }

    public function store(Request $request, Shot $shot): RedirectResponse
    {
        $data = $request->validate([
            'quality_tier' => 'required|in:draft,standard,final',
        ]);

        $prompt = $shot->prompt;

        if (! $prompt) {
            return back()->withErrors(['prompt' => 'El shot no tiene prompt activo. Compón uno primero.']);
        }

        $shot->loadMissing('formatPreset');

        $render = Render::create([
            'shot_id'          => $shot->id,
            'prompt_id'        => $prompt->id,
            'quality_tier'     => $data['quality_tier'],
            'status'           => 'queued',
            'gpu_service'      => 'runpod',
            'seed'             => random_int(1, 2_147_483_647),
            'file_type'        => $shot->shot_type === 'video' ? 'video' : 'image',
            'format_preset_id' => $shot->format_preset_id,
            'width'            => $shot->formatPreset?->width,
            'height'           => $shot->formatPreset?->height,
        ]);

        SubmitRenderJob::dispatch($render);

        return back()->with('success', "Render {$data['quality_tier']} encolado.");
    }

    public function approve(Render $render): RedirectResponse
    {
        // Desaprobar renders previos del mismo shot
        Render::where('shot_id', $render->shot_id)
            ->where('id', '!=', $render->id)
            ->update(['is_approved' => false]);

        $render->update(['is_approved' => true]);

        $render->shot->update(['approved_render_id' => $render->id]);

        return back()->with('success', 'Render aprobado.');
    }

    public function destroy(Render $render): RedirectResponse
    {
        $render->delete();
        return back()->with('success', 'Render eliminado.');
    }
}
