<?php

declare(strict_types=1);

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Project;
use App\Models\Shot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    public function index(Request $request): Response
    {
        $campaigns = Campaign::with(['project:id,name', 'property:id,name,type'])
            ->withCount('shots')
            ->when($request->search,  fn($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when($request->project, fn($q, $v) => $q->where('project_id', $v))
            ->when($request->status,  fn($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate(18)
            ->withQueryString();

        $projects = Project::select('id', 'name')->latest()->get();

        return Inertia::render('Campaigns/Index', [
            'campaigns' => $campaigns,
            'projects'  => $projects,
            'filters'   => $request->only(['search', 'project', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Campaigns/Create', [
            'projects' => Project::with('properties:id,project_id,name,type')->get(['id', 'name', 'type']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'project_id'  => 'required|exists:projects,id',
            'property_id' => 'nullable|exists:properties,id',
            'type'        => 'required|in:real_estate,product,brand,event,youtube,social',
            'description' => 'nullable|string',
            'status'      => 'required|in:planning,production,review,completed,archived',
            'deadline'    => 'nullable|date',
        ]);

        $data['slug']            = Str::slug($data['name']);
        $data['asset_checklist'] = $this->checklistTemplate($data['type']);

        $campaign = Campaign::create($data);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaña creada.');
    }

    public function show(Campaign $campaign): Response
    {
        $campaign->load([
            'project:id,name,type',
            'property:id,name,type,location_description,price,currency',
            'shots' => fn($q) => $q->orderBy('sort_order')->orderBy('number'),
        ]);

        $completedItems = collect($campaign->asset_checklist ?? [])
            ->where('status', 'completed')->count();
        $totalItems = count($campaign->asset_checklist ?? []);

        return Inertia::render('Campaigns/Show', [
            'campaign'       => $campaign,
            'completedItems' => $completedItems,
            'totalItems'     => $totalItems,
        ]);
    }

    public function edit(Campaign $campaign): Response
    {
        return Inertia::render('Campaigns/Edit', [
            'campaign' => $campaign->load('project:id,name'),
            'projects' => Project::with('properties:id,project_id,name,type')->get(['id', 'name', 'type']),
        ]);
    }

    public function update(Request $request, Campaign $campaign): RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'project_id'  => 'required|exists:projects,id',
            'property_id' => 'nullable|exists:properties,id',
            'type'        => 'required|in:real_estate,product,brand,event,youtube,social',
            'description' => 'nullable|string',
            'status'      => 'required|in:planning,production,review,completed,archived',
            'deadline'    => 'nullable|date',
        ]);

        $campaign->update($data);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaña actualizada.');
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $campaign->delete();

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaña eliminada.');
    }

    public function generateShots(Campaign $campaign): RedirectResponse
    {
        $checklist = $campaign->asset_checklist ?? [];
        $number    = $campaign->shots()->max('number') ?? 0;

        foreach ($checklist as &$item) {
            if ($item['shot_id'] !== null) continue;

            $number++;
            $shot = Shot::create([
                'campaign_id' => $campaign->id,
                'number'      => $number,
                'sort_order'  => $number,
                'description' => $item['label'],
                'shot_type'   => $item['shot_type'],
                'purpose'     => $item['purpose'],
                'status'      => 'draft',
            ]);

            $item['shot_id'] = $shot->id;
            $item['status']  = 'in_progress';
        }
        unset($item);

        $campaign->update(['asset_checklist' => $checklist]);

        return back()->with('success', 'Shots generados desde el checklist.');
    }

    private function checklistTemplate(string $type): array
    {
        return match ($type) {
            'real_estate' => [
                ['id' => 1, 'label' => 'Render de fachada exterior', 'shot_type' => 'image', 'purpose' => 'property_hero', 'status' => 'pending', 'shot_id' => null],
                ['id' => 2, 'label' => 'Interior — sala', 'shot_type' => 'image', 'purpose' => 'narrative', 'status' => 'pending', 'shot_id' => null],
                ['id' => 3, 'label' => 'Interior — cocina', 'shot_type' => 'image', 'purpose' => 'narrative', 'status' => 'pending', 'shot_id' => null],
                ['id' => 4, 'label' => 'Interior — recámara principal', 'shot_type' => 'image', 'purpose' => 'narrative', 'status' => 'pending', 'shot_id' => null],
                ['id' => 5, 'label' => 'Foto broker en propiedad', 'shot_type' => 'image', 'purpose' => 'broker_portrait', 'status' => 'pending', 'shot_id' => null],
                ['id' => 6, 'label' => 'Video presentación 60s', 'shot_type' => 'talking', 'purpose' => 'talking_dialogue', 'status' => 'pending', 'shot_id' => null],
                ['id' => 7, 'label' => 'Carrusel frame 1', 'shot_type' => 'image', 'purpose' => 'carousel_frame', 'status' => 'pending', 'shot_id' => null],
                ['id' => 8, 'label' => 'Carrusel frame 2', 'shot_type' => 'image', 'purpose' => 'carousel_frame', 'status' => 'pending', 'shot_id' => null],
                ['id' => 9, 'label' => 'Carrusel frame 3', 'shot_type' => 'image', 'purpose' => 'carousel_frame', 'status' => 'pending', 'shot_id' => null],
                ['id' => 10, 'label' => 'Story vertical 9:16', 'shot_type' => 'image', 'purpose' => 'social', 'status' => 'pending', 'shot_id' => null],
                ['id' => 11, 'label' => 'Thumbnail de reel', 'shot_type' => 'image', 'purpose' => 'thumbnail', 'status' => 'pending', 'shot_id' => null],
            ],
            'youtube' => [
                ['id' => 1, 'label' => 'Thumbnail principal', 'shot_type' => 'image', 'purpose' => 'thumbnail', 'status' => 'pending', 'shot_id' => null],
                ['id' => 2, 'label' => 'Thumbnail variante A', 'shot_type' => 'image', 'purpose' => 'thumbnail', 'status' => 'pending', 'shot_id' => null],
                ['id' => 3, 'label' => 'Thumbnail variante B', 'shot_type' => 'image', 'purpose' => 'thumbnail', 'status' => 'pending', 'shot_id' => null],
                ['id' => 4, 'label' => 'Intro del host', 'shot_type' => 'talking', 'purpose' => 'talking_dialogue', 'status' => 'pending', 'shot_id' => null],
                ['id' => 5, 'label' => 'Hero visual del destino', 'shot_type' => 'image', 'purpose' => 'hero', 'status' => 'pending', 'shot_id' => null],
            ],
            default => [
                ['id' => 1, 'label' => 'Hero visual', 'shot_type' => 'image', 'purpose' => 'hero', 'status' => 'pending', 'shot_id' => null],
                ['id' => 2, 'label' => 'Post principal', 'shot_type' => 'image', 'purpose' => 'social', 'status' => 'pending', 'shot_id' => null],
                ['id' => 3, 'label' => 'Story vertical', 'shot_type' => 'image', 'purpose' => 'social', 'status' => 'pending', 'shot_id' => null],
            ],
        };
    }
}
