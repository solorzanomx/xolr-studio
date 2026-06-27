<?php

declare(strict_types=1);

namespace App\Http\Controllers\Library\Character;

use App\Http\Controllers\Controller;
use App\Models\Character;
use App\Models\Outfit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OutfitController extends Controller
{
    public function store(Request $request, Character $character): RedirectResponse
    {
        $data = $request->validate([
            'name'            => 'required|string|max:150',
            'description'     => 'nullable|string',
            'prompt_fragment' => 'nullable|string',
            'context'         => 'required|in:formal,casual,action,historical,fantasy,uniform,other',
            'is_active'       => 'boolean',
        ]);

        $character->outfits()->create($data);

        return back()->with('success', 'Outfit añadido.');
    }

    public function update(Request $request, Outfit $outfit): RedirectResponse
    {
        $data = $request->validate([
            'name'            => 'required|string|max:150',
            'description'     => 'nullable|string',
            'prompt_fragment' => 'nullable|string',
            'context'         => 'required|in:formal,casual,action,historical,fantasy,uniform,other',
            'is_active'       => 'boolean',
        ]);

        $outfit->update($data);

        return back()->with('success', 'Outfit actualizado.');
    }

    public function destroy(Outfit $outfit): RedirectResponse
    {
        $outfit->delete();

        return back()->with('success', 'Outfit eliminado.');
    }
}
