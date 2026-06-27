<?php

declare(strict_types=1);

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Models\Character;
use App\Services\ClaudeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VirtualTalentController extends Controller
{
    public function index(): Response
    {
        $talents = Character::query()
            ->where('type', 'virtual_talent')
            ->with('virtualTalent')
            ->withCount('outfits')
            ->latest()
            ->get();

        return Inertia::render('Library/VirtualTalents/Index', [
            'talents' => $talents,
        ]);
    }

    public function show(Character $character): Response
    {
        $character->load(['virtualTalent', 'outfits', 'voiceProfiles']);

        return Inertia::render('Library/VirtualTalents/Show', [
            'character' => $character,
            'talent'    => $character->virtualTalent,
        ]);
    }

    public function edit(Character $character): Response
    {
        $character->load('virtualTalent');

        return Inertia::render('Library/VirtualTalents/Edit', [
            'character' => $character,
            'talent'    => $character->virtualTalent,
        ]);
    }

    public function update(Request $request, Character $character): RedirectResponse
    {
        $data = $request->validate([
            'title'               => 'required|string|max:100',
            'specialties'         => 'nullable|array',
            'bio_short'           => 'nullable|string|max:300',
            'bio_full'            => 'nullable|string',
            'communication_style' => 'nullable|string',
            'signature_phrase'    => 'nullable|string|max:255',
            'social_handle'       => 'nullable|string|max:100',
            'brand_colors'        => 'nullable|array',
            'is_public'           => 'boolean',
        ]);

        $character->virtualTalent()->updateOrCreate(
            ['character_id' => $character->id],
            $data
        );

        return redirect()->route('library.virtual-talents.show', $character)
            ->with('success', 'Perfil de talent actualizado.');
    }

    public function generateBio(Request $request, Character $character, ClaudeService $claude): JsonResponse
    {
        try {
            $bio = $claude->generateVirtualTalentBio([
                'name'                 => $character->name,
                'description'          => $character->description,
                'physical_description' => $character->physical_description,
                'personality_traits'   => $character->personality_traits ?? [],
                'base_prompt'          => $character->base_prompt,
            ]);

            return response()->json(['success' => true, 'data' => $bio]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
