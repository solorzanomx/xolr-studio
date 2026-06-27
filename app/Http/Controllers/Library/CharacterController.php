<?php

declare(strict_types=1);

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Models\Character;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CharacterController extends Controller
{
    public function index(Request $request): Response
    {
        $characters = Character::query()
            ->withCount(['outfits', 'voiceProfiles', 'versions'])
            ->when($request->search, fn($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when($request->type, fn($q, $v) => $q->where('type', $v))
            ->when($request->active, fn($q) => $q->where('is_active', true))
            ->when($request->has_lora, fn($q) => $q->whereNotNull('lora_path'))
            ->latest()
            ->paginate(24)
            ->withQueryString();

        return Inertia::render('Library/Characters/Index', [
            'characters' => $characters,
            'filters' => $request->only(['search', 'type', 'active', 'has_lora']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Library/Characters/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:150',
            'type'                  => 'required|in:fictional,virtual_talent,mascot',
            'description'           => 'nullable|string',
            'physical_description'  => 'nullable|string',
            'personality_traits'    => 'nullable|array',
            'base_prompt'           => 'nullable|string',
            'negative_prompt'       => 'nullable|string',
            'lora_trigger_word'     => 'nullable|string|max:100',
            'lora_weight'           => 'nullable|numeric|min:0|max:2',
            'is_active'             => 'boolean',
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['created_by'] = $request->user()->id;

        $character = Character::create($data);

        return redirect()->route('library.characters.show', $character)
            ->with('success', 'Personaje creado.');
    }

    public function show(Character $character): Response
    {
        $character->load(['outfits', 'versions', 'voiceProfiles', 'createdBy:id,name']);

        return Inertia::render('Library/Characters/Show', [
            'character' => $character,
        ]);
    }

    public function edit(Character $character): Response
    {
        return Inertia::render('Library/Characters/Edit', [
            'character' => $character,
        ]);
    }

    public function update(Request $request, Character $character): RedirectResponse
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:150',
            'type'                  => 'required|in:fictional,virtual_talent,mascot',
            'description'           => 'nullable|string',
            'physical_description'  => 'nullable|string',
            'personality_traits'    => 'nullable|array',
            'base_prompt'           => 'nullable|string',
            'negative_prompt'       => 'nullable|string',
            'lora_trigger_word'     => 'nullable|string|max:100',
            'lora_weight'           => 'nullable|numeric|min:0|max:2',
            'is_active'             => 'boolean',
        ]);

        $character->update($data);

        return redirect()->route('library.characters.show', $character)
            ->with('success', 'Personaje actualizado.');
    }

    public function destroy(Character $character): RedirectResponse
    {
        $character->delete();

        return redirect()->route('library.characters.index')
            ->with('success', 'Personaje eliminado.');
    }
}
