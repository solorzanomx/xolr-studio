<?php

declare(strict_types=1);

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    public function index(Request $request): Response
    {
        $locations = Location::query()
            ->when($request->search, fn($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when($request->type, fn($q, $v) => $q->where('type', $v))
            ->when($request->active, fn($q) => $q->where('is_active', true))
            ->latest()
            ->paginate(24)
            ->withQueryString();

        return Inertia::render('Library/Locations/Index', [
            'locations' => $locations,
            'filters' => $request->only(['search', 'type', 'active']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Library/Locations/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'             => 'required|string|max:150',
            'type'             => 'required|in:interior,exterior,mixed',
            'description'      => 'nullable|string',
            'base_prompt'      => 'nullable|string',
            'lighting_by_time' => 'nullable|array',
            'is_active'        => 'boolean',
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['created_by'] = $request->user()->id;

        $location = Location::create($data);

        return redirect()->route('library.locations.show', $location)
            ->with('success', 'Locación creada.');
    }

    public function show(Location $location): Response
    {
        return Inertia::render('Library/Locations/Show', [
            'location' => $location,
        ]);
    }

    public function edit(Location $location): Response
    {
        return Inertia::render('Library/Locations/Edit', [
            'location' => $location,
        ]);
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $data = $request->validate([
            'name'             => 'required|string|max:150',
            'type'             => 'required|in:interior,exterior,mixed',
            'description'      => 'nullable|string',
            'base_prompt'      => 'nullable|string',
            'lighting_by_time' => 'nullable|array',
            'is_active'        => 'boolean',
        ]);

        $location->update($data);

        return redirect()->route('library.locations.show', $location)
            ->with('success', 'Locación actualizada.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        $location->delete();

        return redirect()->route('library.locations.index')
            ->with('success', 'Locación eliminada.');
    }
}
