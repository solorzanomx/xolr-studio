<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Jobs\SyncAnalyticsJob;
use App\Models\AnalyticsSnapshot;
use App\Models\Project;
use App\Models\SocialPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function index(Request $request): Response
    {
        $projectId = $request->project_id;
        $project   = $projectId ? Project::findOrFail($projectId) : Project::first();
        $projects  = Project::orderBy('name')->get(['id', 'name']);
        $days      = (int) $request->get('days', 30);

        $snapshotQuery = AnalyticsSnapshot::query()
            ->where('snapshot_date', '>=', now()->subDays($days))
            ->orderBy('snapshot_date');

        if ($project) {
            $snapshotQuery->where('project_id', $project->id);
        }

        $snapshots = $snapshotQuery->get();

        // Aggregate per platform
        $byPlatform = $snapshots->groupBy('platform')->map(fn($g) => [
            'total_views'        => $g->sum('views'),
            'total_reach'        => $g->sum('reach'),
            'total_likes'        => $g->sum('likes'),
            'total_comments'     => $g->sum('comments'),
            'total_shares'       => $g->sum('shares'),
            'total_saves'        => $g->sum('saves'),
            'avg_engagement'     => round($g->avg('engagement_rate') * 100, 2),
            'avg_ctr'            => round($g->avg('click_through_rate') * 100, 2),
            'subscribers_gained' => $g->sum('subscribers_gained'),
            'posts_count'        => $g->unique('platform_post_id')->count(),
        ]);

        // Top performing posts (by views + engagement)
        $topPosts = $snapshots
            ->sortByDesc(fn($s) => ($s->views ?? 0) + ($s->likes ?? 0) * 10)
            ->unique('platform_post_id')
            ->take(5)
            ->map(fn($s) => [
                'platform_post_id' => $s->platform_post_id,
                'platform'         => $s->platform,
                'views'            => $s->views,
                'likes'            => $s->likes,
                'engagement_rate'  => round($s->engagement_rate * 100, 2),
                'date'             => $s->snapshot_date->toDateString(),
            ])->values();

        // Daily trend (last N days)
        $daily = $snapshots
            ->groupBy(fn($s) => $s->snapshot_date->toDateString())
            ->map(fn($g, $date) => [
                'date'   => $date,
                'views'  => $g->sum('views'),
                'likes'  => $g->sum('likes'),
                'reach'  => $g->sum('reach'),
            ])
            ->sortKeys()
            ->values();

        // Published posts count
        $publishedCount = SocialPost::where('status', 'published')
            ->when($project, fn($q) => $q->where('project_id', $project->id))
            ->count();

        return Inertia::render('Analytics/Index', [
            'project'        => $project?->only(['id', 'name']),
            'projects'       => $projects,
            'byPlatform'     => $byPlatform,
            'topPosts'       => $topPosts,
            'daily'          => $daily,
            'days'           => $days,
            'publishedCount' => $publishedCount,
        ]);
    }

    public function syncNow(Request $request): RedirectResponse
    {
        $projectId = $request->input('project_id');
        SyncAnalyticsJob::dispatch($projectId ? (int) $projectId : null)->onQueue('default');

        return back()->with('success', 'Sincronización de analytics en cola.');
    }
}
