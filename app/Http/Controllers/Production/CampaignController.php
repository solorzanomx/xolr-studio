<?php

declare(strict_types=1);

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Character;
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
        $campaigns = Campaign::with(['project:id,name', 'property:id,name,type', 'virtualTalent:id,character_id'])
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
            'projects'       => Project::with('properties:id,project_id,name,type')->get(['id', 'name', 'type']),
            'virtualTalents' => Character::whereHas('virtualTalent')->with('virtualTalent:id,character_id')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'project_id'        => 'required|exists:projects,id',
            'property_id'       => 'nullable|exists:properties,id',
            'virtual_talent_id' => 'nullable|exists:virtual_talents,id',
            'type'              => 'required|in:real_estate,product,brand,event,youtube,social',
            'template'          => 'nullable|string|max:50',
            'description'       => 'nullable|string',
            'status'            => 'required|in:planning,production,review,completed,archived',
            'deadline'          => 'nullable|date',
        ]);

        $data['slug']            = Str::slug($data['name']);
        $data['asset_checklist'] = $this->checklistTemplate($data['template'] ?? $data['type']);

        $campaign = Campaign::create($data);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaña creada.');
    }

    public function show(Campaign $campaign): Response
    {
        $campaign->load([
            'project:id,name,type',
            'property:id,name,type,location_description,price,currency,bedrooms,bathrooms,area_sqm',
            'virtualTalent:id,character_id',
            'virtualTalent.character:id,name',
            'shots' => fn($q) => $q->orderBy('sort_order')->orderBy('number'),
        ]);

        $checklist = $campaign->asset_checklist ?? [];
        $completedItems = collect($checklist)->where('status', 'completed')->count();
        $totalItems     = count($checklist);

        // Agrupar checklist por group
        $checklistByGroup = collect($checklist)->groupBy('group')->toArray();

        return Inertia::render('Campaigns/Show', [
            'campaign'        => $campaign,
            'completedItems'  => $completedItems,
            'totalItems'      => $totalItems,
            'checklistByGroup'=> $checklistByGroup,
        ]);
    }

    public function edit(Campaign $campaign): Response
    {
        return Inertia::render('Campaigns/Edit', [
            'campaign'       => $campaign->load(['project:id,name', 'virtualTalent:id,character_id']),
            'projects'       => Project::with('properties:id,project_id,name,type')->get(['id', 'name', 'type']),
            'virtualTalents' => Character::whereHas('virtualTalent')->with('virtualTalent:id,character_id')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Campaign $campaign): RedirectResponse
    {
        $data = $request->validate([
            'name'              => 'required|string|max:255',
            'project_id'        => 'required|exists:projects,id',
            'property_id'       => 'nullable|exists:properties,id',
            'virtual_talent_id' => 'nullable|exists:virtual_talents,id',
            'type'              => 'required|in:real_estate,product,brand,event,youtube,social',
            'template'          => 'nullable|string|max:50',
            'description'       => 'nullable|string',
            'status'            => 'required|in:planning,production,review,completed,archived',
            'deadline'          => 'nullable|date',
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
            if ($item['shot_id'] !== null) {
                continue;
            }

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

        return back()->with('success', count(array_filter($checklist, fn($i) => $i['shot_id'])) . ' shots generados desde el checklist.');
    }

    private function checklistTemplate(string $template): array
    {
        return match ($template) {
            'launch' => [
                // Infomerciales
                ['id' => 1,  'group' => 'Infomerciales', 'label' => 'Video presentación broker 60s',   'shot_type' => 'talking', 'purpose' => 'talking_dialogue', 'status' => 'pending', 'shot_id' => null],
                ['id' => 2,  'group' => 'Infomerciales', 'label' => 'Video bienvenida al desarrollo',   'shot_type' => 'talking', 'purpose' => 'talking_dialogue', 'status' => 'pending', 'shot_id' => null],
                // Carrusel Instagram
                ['id' => 3,  'group' => 'Carrusel Instagram', 'label' => 'Portada del carrusel',               'shot_type' => 'image', 'purpose' => 'carousel_frame', 'status' => 'pending', 'shot_id' => null],
                ['id' => 4,  'group' => 'Carrusel Instagram', 'label' => 'Carrusel — Fachada exterior',        'shot_type' => 'image', 'purpose' => 'carousel_frame', 'status' => 'pending', 'shot_id' => null],
                ['id' => 5,  'group' => 'Carrusel Instagram', 'label' => 'Carrusel — Sala o living',           'shot_type' => 'image', 'purpose' => 'carousel_frame', 'status' => 'pending', 'shot_id' => null],
                ['id' => 6,  'group' => 'Carrusel Instagram', 'label' => 'Carrusel — Cocina',                  'shot_type' => 'image', 'purpose' => 'carousel_frame', 'status' => 'pending', 'shot_id' => null],
                ['id' => 7,  'group' => 'Carrusel Instagram', 'label' => 'Carrusel — Recámara principal',      'shot_type' => 'image', 'purpose' => 'carousel_frame', 'status' => 'pending', 'shot_id' => null],
                ['id' => 8,  'group' => 'Carrusel Instagram', 'label' => 'Carrusel — Amenidades / área común', 'shot_type' => 'image', 'purpose' => 'carousel_frame', 'status' => 'pending', 'shot_id' => null],
                // Posts Facebook & Instagram
                ['id' => 9,  'group' => 'Posts Facebook & Instagram', 'label' => 'Post hero — Fachada exterior', 'shot_type' => 'image', 'purpose' => 'property_hero', 'status' => 'pending', 'shot_id' => null],
                ['id' => 10, 'group' => 'Posts Facebook & Instagram', 'label' => 'Post interior destacado',       'shot_type' => 'image', 'purpose' => 'narrative',     'status' => 'pending', 'shot_id' => null],
                // Stories
                ['id' => 11, 'group' => 'Stories', 'label' => 'Story broker 9:16',        'shot_type' => 'image',   'purpose' => 'broker_portrait',   'status' => 'pending', 'shot_id' => null],
                ['id' => 12, 'group' => 'Stories', 'label' => 'Story precio / CTA',       'shot_type' => 'image',   'purpose' => 'social',            'status' => 'pending', 'shot_id' => null],
                ['id' => 13, 'group' => 'Stories', 'label' => 'Story video broker 9:16',  'shot_type' => 'talking', 'purpose' => 'talking_dialogue',  'status' => 'pending', 'shot_id' => null],
                // Reel
                ['id' => 14, 'group' => 'Reel', 'label' => 'Thumbnail del reel',           'shot_type' => 'image', 'purpose' => 'thumbnail', 'status' => 'pending', 'shot_id' => null],
                ['id' => 15, 'group' => 'Reel', 'label' => 'Video de propiedad para reel', 'shot_type' => 'video', 'purpose' => 'hero',      'status' => 'pending', 'shot_id' => null],
            ],
            'available' => [
                ['id' => 1,  'group' => 'Infomerciales',              'label' => 'Video presentación 30s con broker', 'shot_type' => 'talking', 'purpose' => 'talking_dialogue', 'status' => 'pending', 'shot_id' => null],
                ['id' => 2,  'group' => 'Carrusel Instagram',         'label' => 'Carrusel — Fachada exterior',       'shot_type' => 'image',   'purpose' => 'carousel_frame',   'status' => 'pending', 'shot_id' => null],
                ['id' => 3,  'group' => 'Carrusel Instagram',         'label' => 'Carrusel — Interior sala',          'shot_type' => 'image',   'purpose' => 'carousel_frame',   'status' => 'pending', 'shot_id' => null],
                ['id' => 4,  'group' => 'Carrusel Instagram',         'label' => 'Carrusel — Interior recámara',      'shot_type' => 'image',   'purpose' => 'carousel_frame',   'status' => 'pending', 'shot_id' => null],
                ['id' => 5,  'group' => 'Carrusel Instagram',         'label' => 'Carrusel — Precio y CTA',           'shot_type' => 'image',   'purpose' => 'carousel_frame',   'status' => 'pending', 'shot_id' => null],
                ['id' => 6,  'group' => 'Posts Facebook & Instagram', 'label' => 'Post hero principal',               'shot_type' => 'image',   'purpose' => 'property_hero',    'status' => 'pending', 'shot_id' => null],
                ['id' => 7,  'group' => 'Posts Facebook & Instagram', 'label' => 'Post interior 2',                   'shot_type' => 'image',   'purpose' => 'narrative',        'status' => 'pending', 'shot_id' => null],
                ['id' => 8,  'group' => 'Posts Facebook & Instagram', 'label' => 'Post interior 3',                   'shot_type' => 'image',   'purpose' => 'narrative',        'status' => 'pending', 'shot_id' => null],
                ['id' => 9,  'group' => 'Stories',                    'label' => 'Story precio disponible',           'shot_type' => 'image',   'purpose' => 'social',           'status' => 'pending', 'shot_id' => null],
                ['id' => 10, 'group' => 'Stories',                    'label' => 'Story broker 9:16',                 'shot_type' => 'image',   'purpose' => 'broker_portrait',  'status' => 'pending', 'shot_id' => null],
            ],
            'tips' => [
                ['id' => 1, 'group' => 'Infomerciales',              'label' => 'Reel tip 30s con Sofía',     'shot_type' => 'talking', 'purpose' => 'talking_dialogue', 'status' => 'pending', 'shot_id' => null],
                ['id' => 2, 'group' => 'Posts Facebook & Instagram', 'label' => 'Imagen del tip',             'shot_type' => 'image',   'purpose' => 'social',           'status' => 'pending', 'shot_id' => null],
                ['id' => 3, 'group' => 'Posts Facebook & Instagram', 'label' => 'Portada visual del consejo', 'shot_type' => 'image',   'purpose' => 'hero',             'status' => 'pending', 'shot_id' => null],
                ['id' => 4, 'group' => 'Stories',                    'label' => 'Story del tip vertical',     'shot_type' => 'image',   'purpose' => 'social',           'status' => 'pending', 'shot_id' => null],
            ],
            'market_analysis' => [
                ['id' => 1, 'group' => 'Infomerciales',              'label' => 'Video análisis de mercado 90s',     'shot_type' => 'talking', 'purpose' => 'talking_dialogue', 'status' => 'pending', 'shot_id' => null],
                ['id' => 2, 'group' => 'Carrusel Instagram',         'label' => 'Carrusel infográfico — Portada',    'shot_type' => 'image',   'purpose' => 'carousel_frame',   'status' => 'pending', 'shot_id' => null],
                ['id' => 3, 'group' => 'Carrusel Instagram',         'label' => 'Carrusel — Dato 1: precio por m²', 'shot_type' => 'image',   'purpose' => 'carousel_frame',   'status' => 'pending', 'shot_id' => null],
                ['id' => 4, 'group' => 'Carrusel Instagram',         'label' => 'Carrusel — Dato 2: plusvalía',     'shot_type' => 'image',   'purpose' => 'carousel_frame',   'status' => 'pending', 'shot_id' => null],
                ['id' => 5, 'group' => 'Carrusel Instagram',         'label' => 'Carrusel — Dato 3: tendencia',     'shot_type' => 'image',   'purpose' => 'carousel_frame',   'status' => 'pending', 'shot_id' => null],
                ['id' => 6, 'group' => 'Carrusel Instagram',         'label' => 'Carrusel — Dato 4: comparativa',   'shot_type' => 'image',   'purpose' => 'carousel_frame',   'status' => 'pending', 'shot_id' => null],
                ['id' => 7, 'group' => 'Carrusel Instagram',         'label' => 'Carrusel — CTA inversión',         'shot_type' => 'image',   'purpose' => 'carousel_frame',   'status' => 'pending', 'shot_id' => null],
                ['id' => 8, 'group' => 'Posts Facebook & Instagram', 'label' => 'Post de datos clave del mercado',  'shot_type' => 'image',   'purpose' => 'social',           'status' => 'pending', 'shot_id' => null],
                ['id' => 9, 'group' => 'Posts Facebook & Instagram', 'label' => 'Post de Diego con datos',          'shot_type' => 'image',   'purpose' => 'broker_portrait',  'status' => 'pending', 'shot_id' => null],
            ],
            'closing' => [
                ['id' => 1, 'group' => 'Posts Facebook & Instagram', 'label' => 'Post de celebración del cierre',   'shot_type' => 'image', 'purpose' => 'social',          'status' => 'pending', 'shot_id' => null],
                ['id' => 2, 'group' => 'Posts Facebook & Instagram', 'label' => 'Post broker con cliente (simulado)', 'shot_type' => 'image', 'purpose' => 'broker_portrait', 'status' => 'pending', 'shot_id' => null],
                ['id' => 3, 'group' => 'Stories',                    'label' => 'Story de testimonio',               'shot_type' => 'image', 'purpose' => 'social',          'status' => 'pending', 'shot_id' => null],
                ['id' => 4, 'group' => 'Stories',                    'label' => 'Story celebración animada',          'shot_type' => 'image', 'purpose' => 'social',          'status' => 'pending', 'shot_id' => null],
            ],
            default => [
                ['id' => 1, 'group' => 'Posts Facebook & Instagram', 'label' => 'Hero visual principal', 'shot_type' => 'image', 'purpose' => 'hero',   'status' => 'pending', 'shot_id' => null],
                ['id' => 2, 'group' => 'Posts Facebook & Instagram', 'label' => 'Post Instagram',         'shot_type' => 'image', 'purpose' => 'social', 'status' => 'pending', 'shot_id' => null],
                ['id' => 3, 'group' => 'Stories',                    'label' => 'Story vertical 9:16',    'shot_type' => 'image', 'purpose' => 'social', 'status' => 'pending', 'shot_id' => null],
            ],
        };
    }
}
