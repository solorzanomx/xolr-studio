<?php

declare(strict_types=1);

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Jobs\SubmitLipSyncJob;
use App\Models\AudioAsset;
use App\Models\Render;
use App\Models\Shot;
use App\Models\TalkingRender;
use App\Services\LipSync\LipSyncContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TalkingRenderController extends Controller
{
    public function store(Request $request, Shot $shot): RedirectResponse
    {
        $data = $request->validate([
            'source_render_id' => 'required|exists:renders,id',
            'audio_asset_id'   => 'required|exists:audio_assets,id',
            'quality'          => 'required|in:draft,production,premium',
            'service'          => 'required|in:did,heygen,runpod_wav2lip,runpod_latentsync',
        ]);

        // Verificar que el render fuente pertenece a este shot
        $sourceRender = Render::where('id', $data['source_render_id'])
            ->where('shot_id', $shot->id)
            ->where('status', 'completed')
            ->firstOrFail();

        // Verificar que el audio asset existe y está completado
        $audioAsset = AudioAsset::where('id', $data['audio_asset_id'])
            ->where('status', 'completed')
            ->firstOrFail();

        $talkingRender = TalkingRender::create([
            'shot_id'          => $shot->id,
            'source_render_id' => $sourceRender->id,
            'audio_asset_id'   => $audioAsset->id,
            'quality'          => $data['quality'],
            'service'          => $data['service'],
            'status'           => 'queued',
            'width'            => $sourceRender->width,
            'height'           => $sourceRender->height,
        ]);

        SubmitLipSyncJob::dispatch($talkingRender);

        return back()->with('success', 'Lip sync encolado.');
    }

    public function approve(TalkingRender $talkingRender): RedirectResponse
    {
        TalkingRender::where('shot_id', $talkingRender->shot_id)
            ->where('id', '!=', $talkingRender->id)
            ->update(['is_approved' => false]);

        $talkingRender->update(['is_approved' => true]);

        $talkingRender->shot->update(['approved_talking_render_id' => $talkingRender->id]);

        return back()->with('success', 'Talking render aprobado.');
    }

    public function destroy(TalkingRender $talkingRender): RedirectResponse
    {
        $talkingRender->delete();
        return back()->with('success', 'Talking render eliminado.');
    }

    public function estimateCost(Request $request, LipSyncContract $lipSync): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'quality'          => 'required|in:draft,production,premium',
            'duration_seconds' => 'required|numeric|min:1|max:300',
        ]);

        $cost = $lipSync->estimateCost($data['quality'], (float) $data['duration_seconds']);

        return response()->json(['cost_usd' => $cost]);
    }
}
