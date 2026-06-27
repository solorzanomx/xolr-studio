<?php

declare(strict_types=1);

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\CameraStyle;
use App\Models\Character;
use App\Models\FormatPreset;
use App\Models\Prompt;
use App\Models\Scene;
use App\Models\Shot;
use App\Models\VisualStyle;
use App\Services\PromptEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShotController extends Controller
{
    public function show(Shot $shot): Response
    {
        $shot->load([
            'scene.location',
            'scene.episode.season.project',
            'campaign.project',
            'videoConcept.project',
            'characters' => fn($q) => $q->with('outfits:id,character_id,name,prompt_fragment,context'),
            'cameraStyle',
            'visualStyle',
            'formatPreset',
            'prompts',
            'renders' => fn($q) => $q->orderByDesc('created_at'),
        ]);

        // Determinar el contexto del shot para breadcrumb de regreso
        $backUrl = match (true) {
            $shot->scene_id    !== null => "/episodes/{$shot->scene->episode_id}",
            $shot->campaign_id !== null => "/campaigns/{$shot->campaign_id}",
            default                    => '/projects',
        };

        return Inertia::render('Shots/Show', [
            'shot'                => $shot,
            'backUrl'             => $backUrl,
            'availableCharacters' => Character::with('outfits:id,character_id,name,prompt_fragment,context')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'base_prompt', 'lora_trigger_word']),
            'cameraStyles'  => CameraStyle::where('is_active', true)->orderBy('name')->get(['id', 'name', 'prompt_fragment']),
            'visualStyles'  => VisualStyle::where('is_active', true)->orderBy('name')->get(['id', 'name', 'description']),
            'formatPresets' => FormatPreset::where('is_active', true)->orderBy('name')->get(['id', 'name', 'width', 'height', 'aspect_ratio', 'platform']),
        ]);
    }

    public function store(Request $request, Scene $scene): RedirectResponse
    {
        $data = $request->validate([
            'description'      => 'nullable|string',
            'shot_type'        => 'required|in:image,video,talking',
            'purpose'          => 'required|in:narrative,hero,carousel_frame,thumbnail,social,broker_portrait,property_hero,talking_dialogue',
            'dialogue_text'    => 'nullable|string',
            'director_notes'   => 'nullable|string',
            'duration_seconds' => 'nullable|integer|min:1|max:300',
        ]);

        $data['number']     = $scene->shots()->max('number') + 1;
        $data['sort_order'] = $scene->shots()->max('sort_order') + 1;

        $scene->shots()->create($data);

        return back()->with('success', 'Shot añadido.');
    }

    public function update(Request $request, Shot $shot): RedirectResponse
    {
        $data = $request->validate([
            'description'      => 'nullable|string',
            'shot_type'        => 'required|in:image,video,talking',
            'purpose'          => 'required|in:narrative,hero,carousel_frame,thumbnail,social,broker_portrait,property_hero,talking_dialogue',
            'dialogue_text'    => 'nullable|string',
            'director_notes'   => 'nullable|string',
            'duration_seconds' => 'nullable|integer|min:1|max:300',
            'status'           => 'required|in:draft,prompt_ready,rendering,audio_pending,lip_sync_pending,completed,approved',
            'camera_style_id'  => 'nullable|exists:camera_styles,id',
            'visual_style_id'  => 'nullable|exists:visual_styles,id',
            'format_preset_id' => 'nullable|exists:format_presets,id',
        ]);

        $shot->update($data);

        return back()->with('success', 'Shot actualizado.');
    }

    public function destroy(Shot $shot): RedirectResponse
    {
        $shot->delete();

        return back()->with('success', 'Shot eliminado.');
    }

    public function composePrompt(Shot $shot): RedirectResponse
    {
        $engine = app(PromptEngine::class);
        $result = $engine->compose($shot);

        // Desactivar prompt activo anterior
        $shot->prompts()->update(['is_active' => false]);

        $nextVersion = ($shot->prompts()->max('version') ?? 0) + 1;

        Prompt::create([
            'shot_id'         => $shot->id,
            'positive_prompt' => $result['positive_prompt'],
            'negative_prompt' => $result['negative_prompt'],
            'composed_prompt' => $result['composed_prompt'],
            'sources'         => $result['sources'],
            'version'         => $nextVersion,
            'is_active'       => true,
            'created_by'      => auth()->id(),
        ]);

        $shot->update(['status' => 'prompt_ready']);

        return back()->with('success', "Prompt v{$nextVersion} compuesto.");
    }

    public function savePrompt(Request $request, Shot $shot): RedirectResponse
    {
        $data = $request->validate([
            'positive_prompt' => 'required|string',
            'negative_prompt' => 'nullable|string',
        ]);

        $shot->prompts()->update(['is_active' => false]);

        $nextVersion = ($shot->prompts()->max('version') ?? 0) + 1;

        Prompt::create([
            'shot_id'         => $shot->id,
            'positive_prompt' => $data['positive_prompt'],
            'negative_prompt' => $data['negative_prompt'] ?? null,
            'composed_prompt' => $data['positive_prompt'],
            'sources'         => null,
            'version'         => $nextVersion,
            'is_active'       => true,
            'created_by'      => auth()->id(),
        ]);

        $shot->update(['status' => 'prompt_ready']);

        return back()->with('success', "Prompt v{$nextVersion} guardado.");
    }

    public function addCharacter(Request $request, Shot $shot): RedirectResponse
    {
        $data = $request->validate([
            'character_id' => 'required|exists:characters,id',
            'outfit_id'    => 'nullable|exists:outfits,id',
        ]);

        // Evitar duplicados
        if ($shot->characters()->where('character_id', $data['character_id'])->exists()) {
            return back()->withErrors(['character_id' => 'Este personaje ya está en el shot.']);
        }

        $shot->characters()->attach($data['character_id'], [
            'outfit_id' => $data['outfit_id'] ?? null,
        ]);

        return back()->with('success', 'Personaje añadido al shot.');
    }

    public function removeCharacter(Shot $shot, Character $character): RedirectResponse
    {
        $shot->characters()->detach($character->id);

        return back()->with('success', 'Personaje removido del shot.');
    }
}
