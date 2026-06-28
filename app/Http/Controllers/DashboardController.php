<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Render;
use App\Models\TalkingRender;
use App\Models\AudioAsset;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $projects = Project::query()
            ->withCount(['seasons', 'campaigns', 'videoConcepts'])
            ->latest()
            ->limit(6)
            ->get(['id', 'name', 'slug', 'type', 'status', 'thumbnail_path', 'created_at']);

        $activeRenders = Render::query()
            ->whereIn('status', ['queued', 'processing'])
            ->count();

        $activeLipSync = TalkingRender::query()
            ->whereIn('status', ['queued', 'processing'])
            ->count();

        $activeAudio = AudioAsset::query()
            ->whereIn('status', ['pending', 'generating'])
            ->count();

        $todayCost = Render::query()
            ->whereDate('created_at', today())
            ->whereNotNull('gpu_cost_usd')
            ->sum('gpu_cost_usd');

        $todayCost += TalkingRender::query()
            ->whereDate('created_at', today())
            ->whereNotNull('service_cost_usd')
            ->sum('service_cost_usd');

        $todayCost += AudioAsset::query()
            ->whereDate('created_at', today())
            ->whereNotNull('generation_cost_usd')
            ->sum('generation_cost_usd');

        $recentRenders = Render::query()
            ->with('shot:id,description,number')
            ->whereNotNull('file_path')
            ->latest()
            ->limit(8)
            ->get(['id', 'shot_id', 'status', 'quality_tier', 'gpu_cost_usd', 'is_approved', 'file_path', 'created_at'])
            ->map(fn ($r) => array_merge($r->toArray(), [
                'thumbnail_url' => asset('storage/' . $r->file_path),
            ]));

        return Inertia::render('Dashboard', [
            'projects' => $projects,
            'stats' => [
                'activeJobs' => $activeRenders + $activeLipSync + $activeAudio,
                'activeRenders' => $activeRenders,
                'activeLipSync' => $activeLipSync,
                'activeAudio' => $activeAudio,
                'todayCost' => number_format((float) $todayCost, 2),
            ],
            'recentRenders' => $recentRenders,
        ]);
    }
}
