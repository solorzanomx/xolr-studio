<?php

declare(strict_types=1);

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Models\Prop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PropController extends Controller
{
    public function index(Request $request): Response
    {
        $props = Prop::query()
            ->when($request->search, fn($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when($request->active, fn($q) => $q->where('is_active', true))
            ->latest()
            ->paginate(24)
            ->withQueryString();

        return Inertia::render('Library/Props/Index', [
            'items' => $props,
            'filters' => $request->only(['search', 'active']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Library/Props/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'            => 'required|string|max:150',
            'description'     => 'nullable|string',
            'prompt_fragment' => 'nullable|string',
            'is_active'       => 'boolean',
        ]);

        $data['slug'] = Str::slug($data['name']);

        Prop::create($data);

        return redirect()->route('library.props.index')
            ->with('success', 'Prop creado.');
    }

    public function edit(Prop $prop): Response
    {
        return Inertia::render('Library/Props/Edit', [
            'prop' => $prop,
        ]);
    }

    public function update(Request $request, Prop $prop): RedirectResponse
    {
        $data = $request->validate([
            'name'            => 'required|string|max:150',
            'description'     => 'nullable|string',
            'prompt_fragment' => 'nullable|string',
            'is_active'       => 'boolean',
        ]);

        $prop->update($data);

        return redirect()->route('library.props.index')
            ->with('success', 'Prop actualizado.');
    }

    public function destroy(Prop $prop): RedirectResponse
    {
        $prop->delete();

        return redirect()->route('library.props.index')
            ->with('success', 'Prop eliminado.');
    }
}
