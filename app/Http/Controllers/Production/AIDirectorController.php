<?php

declare(strict_types=1);

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAIDirectorJob;
use App\Models\AIDirectorResult;
use App\Models\CameraStyle;
use App\Models\Character;
use App\Models\Episode;
use App\Models\Location;
use App\Models\Scene;
use App\Models\Shot;
use App\Models\VisualStyle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AIDirectorController extends Controller
{
    public function index(): Response
    {
        $episodes = Episode::with([
            'season.project:id,name',
            'aiDirectorResults' => fn($q) => $q->latest()->limit(1),
        ])
            ->orderBy('id', 'desc')
            ->paginate(20);

        return Inertia::render('AIDirector/Index', [
            'episodes' => $episodes,
        ]);
    }

    public function run(Episode $episode): RedirectResponse
    {
        // Cancela resultados pending/processing anteriores
        $episode->aiDirectorResults()
            ->whereIn('status', ['pending', 'processing'])
            ->update(['status' => 'failed', 'error_message' => 'Cancelado — nuevo análisis iniciado.']);

        $result = AIDirectorResult::create([
            'episode_id' => $episode->id,
            'status'     => 'pending',
            'created_by' => auth()->id(),
        ]);

        ProcessAIDirectorJob::dispatch($result->id)->onQueue('default');

        return redirect()->route('ai-director.show', $result)
            ->with('info', 'AI Director procesando el episodio...');
    }

    public function show(AIDirectorResult $aiDirectorResult): Response
    {
        $aiDirectorResult->load('episode.season.project');

        $characters   = Character::where('is_active', true)->get(['id', 'name']);
        $cameraStyles = CameraStyle::where('is_active', true)->get(['id', 'name']);
        $visualStyles = VisualStyle::where('is_active', true)->get(['id', 'name']);
        $locations    = Location::where('is_active', true)->get(['id', 'name']);

        return Inertia::render('AIDirector/Show', [
            'result'       => $aiDirectorResult,
            'characters'   => $characters,
            'cameraStyles' => $cameraStyles,
            'visualStyles' => $visualStyles,
            'locations'    => $locations,
        ]);
    }

    public function apply(AIDirectorResult $aiDirectorResult): RedirectResponse
    {
        if ($aiDirectorResult->status !== 'completed') {
            return back()->withErrors(['status' => 'El análisis no está listo para aplicar.']);
        }

        $structure = $aiDirectorResult->proposed_structure;
        $episode   = $aiDirectorResult->episode;

        $characters   = Character::where('is_active', true)->get(['id', 'name'])->keyBy(fn($c) => strtolower($c->name));
        $cameraStyles = CameraStyle::where('is_active', true)->get(['id', 'name'])->keyBy(fn($s) => strtolower($s->name));
        $visualStyles = VisualStyle::where('is_active', true)->get(['id', 'name'])->keyBy(fn($s) => strtolower($s->name));
        $locations    = Location::where('is_active', true)->get(['id', 'name'])->keyBy(fn($l) => strtolower($l->name));

        DB::transaction(function () use ($structure, $episode, $characters, $cameraStyles, $visualStyles, $locations): void {
            $sceneSortOrder = $episode->scenes()->max('sort_order') ?? 0;

            foreach ($structure['scenes'] as $sceneData) {
                $sceneSortOrder++;
                $locationId = null;

                if (! empty($sceneData['location_name'])) {
                    $locationId = $locations[strtolower($sceneData['location_name'])]?->id;
                }

                $timeOfDay = in_array($sceneData['time_of_day'] ?? '', ['morning', 'day', 'golden_hour', 'night', 'unspecified'])
                    ? $sceneData['time_of_day']
                    : 'unspecified';

                $mood = in_array($sceneData['mood'] ?? '', ['tense', 'action', 'dramatic', 'calm', 'mysterious', 'romantic', 'comedic', 'horror', 'other'])
                    ? $sceneData['mood']
                    : 'calm';

                $scene = Scene::create([
                    'episode_id'   => $episode->id,
                    'title'        => $sceneData['title'],
                    'description'  => $sceneData['description'] ?? null,
                    'location_id'  => $locationId,
                    'time_of_day'  => $timeOfDay,
                    'mood'         => $mood,
                    'number'       => $sceneData['number'],
                    'sort_order'   => $sceneSortOrder,
                ]);

                foreach ($sceneData['shots'] as $shotData) {
                    $cameraStyleId = null;
                    $visualStyleId = null;

                    if (! empty($shotData['camera_style_name'])) {
                        $cameraStyleId = $cameraStyles[strtolower($shotData['camera_style_name'])]?->id;
                    }

                    if (! empty($shotData['visual_style_name'])) {
                        $visualStyleId = $visualStyles[strtolower($shotData['visual_style_name'])]?->id;
                    }

                    $shotType = in_array($shotData['shot_type'] ?? '', ['image', 'video', 'talking'])
                        ? $shotData['shot_type']
                        : 'image';

                    $purpose = in_array($shotData['purpose'] ?? '', ['narrative', 'hero', 'carousel_frame', 'thumbnail', 'social', 'broker_portrait', 'property_hero', 'talking_dialogue'])
                        ? $shotData['purpose']
                        : 'narrative';

                    $shot = Shot::create([
                        'scene_id'         => $scene->id,
                        'number'           => $shotData['number'],
                        'sort_order'       => $shotData['number'],
                        'description'      => $shotData['description'] ?? null,
                        'shot_type'        => $shotType,
                        'purpose'          => $purpose,
                        'director_notes'   => $shotData['director_notes'] ?? null,
                        'dialogue_text'    => $shotData['dialogue_text'] ?? null,
                        'duration_seconds' => $shotData['duration_seconds'] ?? null,
                        'camera_style_id'  => $cameraStyleId,
                        'visual_style_id'  => $visualStyleId,
                        'status'           => 'draft',
                    ]);

                    // Asignar personajes
                    if (! empty($shotData['character_names'])) {
                        foreach ($shotData['character_names'] as $charName) {
                            $char = $characters[strtolower($charName)] ?? null;
                            if ($char) {
                                $shot->characters()->attach($char->id);
                            }
                        }
                    }
                }
            }
        });

        $aiDirectorResult->update([
            'status'     => 'applied',
            'applied_at' => now(),
        ]);

        return redirect()->route('episodes.show', $episode)
            ->with('success', 'Estructura aplicada: ' . count($structure['scenes']) . ' escenas y ' . ($structure['total_shots'] ?? '?') . ' shots creados.');
    }

    public function destroy(AIDirectorResult $aiDirectorResult): RedirectResponse
    {
        $episodeId = $aiDirectorResult->episode_id;
        $aiDirectorResult->delete();

        return back()->with('success', 'Resultado eliminado.');
    }
}
