<?php

declare(strict_types=1);

namespace App\Http\Controllers\Share;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\Project;
use App\Models\ShareToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PreviewController extends Controller
{
    public function show(Request $request, string $token): Response|RedirectResponse
    {
        $share = ShareToken::where('token', $token)->firstOrFail();

        if ($share->isExpired()) {
            return Inertia::render('Preview/Expired');
        }

        // Password gate — check session
        if ($share->isPasswordProtected()) {
            $unlocked = $request->session()->get("preview_unlocked_{$share->id}");
            if (! $unlocked) {
                return Inertia::render('Preview/Password', ['token' => $token]);
            }
        }

        $share->incrementViews();

        return match (true) {
            $share->shareable_type === Episode::class => $this->renderEpisode($share),
            $share->shareable_type === Project::class => $this->renderProject($share),
            default => abort(404),
        };
    }

    public function authenticate(Request $request, string $token): RedirectResponse
    {
        $share = ShareToken::where('token', $token)->firstOrFail();

        $request->validate(['password' => 'required|string']);

        if (! Hash::check($request->password, $share->password_hash)) {
            return back()->withErrors(['password' => 'Contraseña incorrecta.']);
        }

        $request->session()->put("preview_unlocked_{$share->id}", true);

        return redirect()->route('preview.show', $token);
    }

    private function renderEpisode(ShareToken $share): Response
    {
        $episode = Episode::with([
            'season.project:id,name,type',
            'scenes' => fn($q) => $q->orderBy('order'),
            'scenes.shots' => fn($q) => $q->orderBy('order'),
            'scenes.shots.approvedRender:id,shot_id,file_path,quality_tier,width,height,file_type',
            'scenes.shots.characters:id,name',
        ])->findOrFail($share->shareable_id);

        $scenes = $episode->scenes->map(fn($scene) => [
            'id'          => $scene->id,
            'title'       => $scene->title,
            'description' => $scene->description,
            'time_of_day' => $scene->time_of_day,
            'mood'        => $scene->mood,
            'shots'       => $scene->shots->map(fn($shot) => [
                'id'         => $shot->id,
                'label'      => $shot->label,
                'shot_type'  => $shot->shot_type,
                'characters' => $shot->characters->pluck('name'),
                'render'     => $shot->approvedRender ? [
                    'url'       => $this->resolveUrl($shot->approvedRender->file_path),
                    'file_type' => $shot->approvedRender->file_type,
                    'width'     => $shot->approvedRender->width,
                    'height'    => $shot->approvedRender->height,
                    'tier'      => $shot->approvedRender->quality_tier,
                ] : null,
            ]),
        ]);

        return Inertia::render('Preview/Show', [
            'share'      => ['token' => $share->token, 'label' => $share->label, 'view_count' => $share->view_count],
            'type'       => 'episode',
            'title'      => $episode->title,
            'subtitle'   => $episode->season?->project?->name,
            'synopsis'   => $episode->synopsis,
            'logline'    => $episode->logline,
            'scenes'     => $scenes,
            'sceneCount' => $scenes->count(),
            'shotCount'  => $scenes->sum(fn($s) => count($s['shots'])),
            'renderCount'=> $scenes->sum(fn($s) => collect($s['shots'])->filter(fn($sh) => $sh['render'])->count()),
        ]);
    }

    private function renderProject(ShareToken $share): Response
    {
        $project = Project::with([
            'seasons:id,project_id,title,order',
            'seasons.episodes:id,season_id,title,synopsis,status',
        ])->findOrFail($share->shareable_id);

        $seasons = $project->seasons->map(fn($season) => [
            'id'       => $season->id,
            'title'    => $season->title,
            'episodes' => $season->episodes->map(fn($ep) => [
                'id'       => $ep->id,
                'title'    => $ep->title,
                'synopsis' => $ep->synopsis,
                'status'   => $ep->status,
            ]),
        ]);

        return Inertia::render('Preview/Show', [
            'share'    => ['token' => $share->token, 'label' => $share->label, 'view_count' => $share->view_count],
            'type'     => 'project',
            'title'    => $project->name,
            'subtitle' => ucfirst(str_replace('_', ' ', $project->type)),
            'synopsis' => $project->synopsis,
            'seasons'  => $seasons,
        ]);
    }

    private function resolveUrl(?string $path): ?string
    {
        if (! $path) return null;
        if (str_starts_with($path, 'http')) return $path;
        return Storage::disk('public')->url($path);
    }
}
