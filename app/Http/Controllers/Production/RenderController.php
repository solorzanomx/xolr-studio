<?php

declare(strict_types=1);

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Jobs\SubmitRenderJob;
use App\Models\Render;
use App\Models\Shot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RenderController extends Controller
{
    public function store(Request $request, Shot $shot): RedirectResponse
    {
        $data = $request->validate([
            'quality_tier' => 'required|in:draft,standard,final',
        ]);

        $prompt = $shot->prompt;

        if (! $prompt) {
            return back()->withErrors(['prompt' => 'El shot no tiene prompt activo. Compón uno primero.']);
        }

        $shot->loadMissing('formatPreset');

        $render = Render::create([
            'shot_id'          => $shot->id,
            'prompt_id'        => $prompt->id,
            'quality_tier'     => $data['quality_tier'],
            'status'           => 'queued',
            'gpu_service'      => 'runpod',
            'seed'             => random_int(1, 2_147_483_647),
            'file_type'        => $shot->shot_type === 'video' ? 'video' : 'image',
            'format_preset_id' => $shot->format_preset_id,
            'width'            => $shot->formatPreset?->width,
            'height'           => $shot->formatPreset?->height,
        ]);

        SubmitRenderJob::dispatch($render);

        return back()->with('success', "Render {$data['quality_tier']} encolado.");
    }

    public function approve(Render $render): RedirectResponse
    {
        // Desaprobar renders previos del mismo shot
        Render::where('shot_id', $render->shot_id)
            ->where('id', '!=', $render->id)
            ->update(['is_approved' => false]);

        $render->update(['is_approved' => true]);

        $render->shot->update(['approved_render_id' => $render->id]);

        return back()->with('success', 'Render aprobado.');
    }

    public function destroy(Render $render): RedirectResponse
    {
        $render->delete();
        return back()->with('success', 'Render eliminado.');
    }
}
