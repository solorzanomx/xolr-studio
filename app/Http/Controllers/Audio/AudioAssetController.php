<?php

declare(strict_types=1);

namespace App\Http\Controllers\Audio;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateAmbientJob;
use App\Jobs\GenerateMusicJob;
use App\Jobs\GenerateSubtitlesJob;
use App\Jobs\GenerateVoiceJob;
use App\Models\AudioAsset;
use App\Models\Character;
use App\Models\Project;
use App\Models\VoiceProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AudioAssetController extends Controller
{
    public function index(Request $request): Response
    {
        $projectId = $request->input('project_id');
        $type      = $request->input('type');
        $status    = $request->input('status');

        $assets = AudioAsset::with(['voiceProfile.character', 'project', 'createdBy'])
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->when($type, fn($q) => $q->where('type', $type))
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest()
            ->paginate(24);

        return Inertia::render('Audio/Index', [
            'assets'   => $assets,
            'projects' => Project::orderBy('name')->get(['id', 'name']),
            'filters'  => compact('projectId', 'type', 'status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_id'       => 'nullable|exists:projects,id',
            'name'             => 'required|string|max:255',
            'type'             => 'required|in:voice_over,dialogue,ambient,sfx,music,mixed',
            'transcript'       => 'nullable|string',
            'prompt_used'      => 'nullable|string',
            'voice_profile_id' => 'nullable|exists:voice_profiles,id',
            'duration_seconds' => 'nullable|numeric|min:1|max:600',
            'mood'             => 'nullable|string|max:50',
            'audioable_type'   => 'nullable|string',
            'audioable_id'     => 'nullable|integer',
        ]);

        $asset = AudioAsset::create([
            ...$data,
            'service'    => $this->resolveService($data['type']),
            'status'     => 'pending',
            'created_by' => auth()->id(),
        ]);

        $this->dispatchJob($asset, $data['mood'] ?? 'neutral');

        return back()->with('success', 'Generando audio…');
    }

    public function destroy(AudioAsset $audioAsset): RedirectResponse
    {
        $audioAsset->delete();
        return back()->with('success', 'Asset eliminado.');
    }

    public function generateSubtitles(AudioAsset $audioAsset): RedirectResponse
    {
        if ($audioAsset->status !== 'completed') {
            return back()->withErrors(['error' => 'El audio debe estar completado para generar subtítulos.']);
        }

        GenerateSubtitlesJob::dispatch($audioAsset);

        return back()->with('success', 'Generando subtítulos…');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    private function resolveService(string $type): string
    {
        return match ($type) {
            'music'               => 'suno',
            'ambient', 'sfx'      => 'elevenlabs',
            default               => 'elevenlabs',
        };
    }

    private function dispatchJob(AudioAsset $asset, string $mood): void
    {
        match ($asset->type) {
            'voice_over', 'dialogue' => GenerateVoiceJob::dispatch($asset),
            'ambient', 'sfx'         => GenerateAmbientJob::dispatch($asset),
            'music'                  => GenerateMusicJob::dispatch($asset, $mood),
            default                  => null,
        };
    }
}
