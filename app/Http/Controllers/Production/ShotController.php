<?php

declare(strict_types=1);

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Scene;
use App\Models\Shot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ShotController extends Controller
{
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
        ]);

        $shot->update($data);

        return back()->with('success', 'Shot actualizado.');
    }

    public function destroy(Shot $shot): RedirectResponse
    {
        $shot->delete();

        return back()->with('success', 'Shot eliminado.');
    }
}
