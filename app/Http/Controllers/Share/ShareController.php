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

class ShareController extends Controller
{
    public function storeEpisode(Request $request, Episode $episode): RedirectResponse
    {
        $data = $request->validate([
            'label'      => 'nullable|string|max:255',
            'password'   => 'nullable|string|min:4|max:100',
            'expires_in' => 'nullable|in:7,30,90,never',
        ]);

        ShareToken::create([
            'shareable_type' => Episode::class,
            'shareable_id'   => $episode->id,
            'label'          => $data['label'] ?? null,
            'password_hash'  => isset($data['password']) ? Hash::make($data['password']) : null,
            'expires_at'     => match ($data['expires_in'] ?? 'never') {
                '7'     => now()->addDays(7),
                '30'    => now()->addDays(30),
                '90'    => now()->addDays(90),
                default => null,
            },
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Link de preview generado.');
    }

    public function storeProject(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'label'      => 'nullable|string|max:255',
            'password'   => 'nullable|string|min:4|max:100',
            'expires_in' => 'nullable|in:7,30,90,never',
        ]);

        ShareToken::create([
            'shareable_type' => Project::class,
            'shareable_id'   => $project->id,
            'label'          => $data['label'] ?? null,
            'password_hash'  => isset($data['password']) ? Hash::make($data['password']) : null,
            'expires_at'     => match ($data['expires_in'] ?? 'never') {
                '7'     => now()->addDays(7),
                '30'    => now()->addDays(30),
                '90'    => now()->addDays(90),
                default => null,
            },
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Link de preview generado.');
    }

    public function destroy(ShareToken $shareToken): RedirectResponse
    {
        $shareToken->delete();
        return back()->with('success', 'Link eliminado.');
    }
}
