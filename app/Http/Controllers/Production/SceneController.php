<?php

declare(strict_types=1);

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\Scene;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SceneController extends Controller
{
    public function store(Request $request, Episode $episode): RedirectResponse
    {
        $data = $request->validate([
            'title'       => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'location_id' => 'nullable|exists:locations,id',
            'time_of_day' => 'required|in:morning,day,golden_hour,night,unspecified',
            'mood'        => 'required|in:tense,action,dramatic,calm,mysterious,romantic,comedic,horror,other',
        ]);

        $data['number']     = $episode->scenes()->max('number') + 1;
        $data['sort_order'] = $episode->scenes()->max('sort_order') + 1;

        $episode->scenes()->create($data);

        return back()->with('success', 'Escena añadida.');
    }

    public function update(Request $request, Scene $scene): RedirectResponse
    {
        $data = $request->validate([
            'title'       => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'location_id' => 'nullable|exists:locations,id',
            'time_of_day' => 'required|in:morning,day,golden_hour,night,unspecified',
            'mood'        => 'required|in:tense,action,dramatic,calm,mysterious,romantic,comedic,horror,other',
        ]);

        $scene->update($data);

        return back()->with('success', 'Escena actualizada.');
    }

    public function destroy(Scene $scene): RedirectResponse
    {
        $scene->delete();

        return back()->with('success', 'Escena eliminada.');
    }
}
