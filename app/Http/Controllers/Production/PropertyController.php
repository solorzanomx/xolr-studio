<?php

declare(strict_types=1);

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'name'                 => 'required|string|max:255',
            'type'                 => 'required|in:apartment,house,penthouse,commercial,land,development',
            'location_description' => 'nullable|string|max:255',
            'price'                => 'nullable|numeric|min:0',
            'currency'             => 'nullable|string|size:3',
            'bedrooms'             => 'nullable|integer|min:0|max:20',
            'bathrooms'            => 'nullable|numeric|min:0|max:20',
            'area_sqm'             => 'nullable|numeric|min:0',
            'description'          => 'nullable|string',
            'status'               => 'required|in:available,sold,rented,off_market',
        ]);

        $data['slug'] = Str::slug($data['name'] . '-' . now()->format('ymd'));

        $project->properties()->create($data);

        return back()->with('success', 'Propiedad añadida.');
    }

    public function edit(Property $property): Response
    {
        return Inertia::render('Campaigns/PropertyEdit', [
            'property' => $property->load('project:id,name'),
        ]);
    }

    public function update(Request $request, Property $property): RedirectResponse
    {
        $data = $request->validate([
            'name'                 => 'required|string|max:255',
            'type'                 => 'required|in:apartment,house,penthouse,commercial,land,development',
            'location_description' => 'nullable|string|max:255',
            'price'                => 'nullable|numeric|min:0',
            'currency'             => 'nullable|string|size:3',
            'bedrooms'             => 'nullable|integer|min:0|max:20',
            'bathrooms'            => 'nullable|numeric|min:0|max:20',
            'area_sqm'             => 'nullable|numeric|min:0',
            'description'          => 'nullable|string',
            'status'               => 'required|in:available,sold,rented,off_market',
        ]);

        $property->update($data);

        return redirect()->route('campaigns.index')
            ->with('success', 'Propiedad actualizada.');
    }

    public function destroy(Property $property): RedirectResponse
    {
        $property->delete();

        return back()->with('success', 'Propiedad eliminada.');
    }
}
