<?php

declare(strict_types=1);

namespace App\Http\Controllers\ContentMachine;

use App\Http\Controllers\Controller;
use App\Models\VideoHook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VideoHookController extends Controller
{
    public function index(Request $request): Response
    {
        $hooks = VideoHook::when($request->project, fn($q, $v) => $q->where('project_id', $v)->orWhereNull('project_id'))
            ->when(!$request->project, fn($q) => $q->whereNull('project_id'))
            ->when($request->category, fn($q, $v) => $q->where('category', $v))
            ->orderByDesc('rating')
            ->orderByDesc('usage_count')
            ->get();

        return Inertia::render('ContentMachine/HooksBank', [
            'hooks'    => $hooks,
            'filters'  => $request->only(['project', 'category']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'category'   => 'required|in:curiosity,shock,question,challenge,story,data,other',
            'content'    => 'required|string',
            'rating'     => 'nullable|integer|min:1|max:5',
        ]);

        VideoHook::create($data);

        return back()->with('success', 'Hook guardado en el banco.');
    }

    public function update(Request $request, VideoHook $hook): RedirectResponse
    {
        $data = $request->validate([
            'category' => 'required|in:curiosity,shock,question,challenge,story,data,other',
            'content'  => 'required|string',
            'rating'   => 'nullable|integer|min:1|max:5',
        ]);

        $hook->update($data);

        return back()->with('success', 'Hook actualizado.');
    }

    public function destroy(VideoHook $hook): RedirectResponse
    {
        $hook->delete();

        return back()->with('success', 'Hook eliminado.');
    }
}
