<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AnalyticsSnapshot;
use App\Models\SocialPost;
use App\Services\Analytics\InstagramInsightsAdapter;
use App\Services\Analytics\YouTubeAnalyticsAdapter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 120;

    public function __construct(
        private readonly ?int $projectId = null,
    ) {}

    public function handle(): void
    {
        $query = SocialPost::where('status', 'published')
            ->whereNotNull('platform_post_id');

        if ($this->projectId) {
            $query->where('project_id', $this->projectId);
        }

        $posts = $query->get();

        foreach ($posts as $post) {
            // No duplicar snapshots del mismo día
            if (AnalyticsSnapshot::where('platform_post_id', $post->platform_post_id)
                ->whereDate('snapshot_date', today())
                ->exists()) {
                continue;
            }

            try {
                $adapter = match (in_array($post->platform, ['instagram', 'facebook'])) {
                    true  => new InstagramInsightsAdapter(),
                    false => new YouTubeAnalyticsAdapter(),
                };

                $metrics = $adapter->fetchMetrics($post->platform_post_id);

                AnalyticsSnapshot::create([
                    'social_post_id'         => $post->id,
                    'project_id'             => $post->project_id,
                    'platform'               => $post->platform,
                    'platform_post_id'       => $post->platform_post_id,
                    'snapshot_date'          => today(),
                    'views'                  => $metrics['views'] ?? null,
                    'reach'                  => $metrics['reach'] ?? null,
                    'likes'                  => $metrics['likes'] ?? null,
                    'comments'               => $metrics['comments'] ?? null,
                    'shares'                 => $metrics['shares'] ?? null,
                    'saves'                  => $metrics['saves'] ?? null,
                    'click_through_rate'     => $metrics['click_through_rate'] ?? null,
                    'avg_watch_time_seconds' => $metrics['avg_watch_time_seconds'] ?? null,
                    'engagement_rate'        => $metrics['engagement_rate'] ?? null,
                    'subscribers_gained'     => $metrics['subscribers_gained'] ?? null,
                ]);
            } catch (\Throwable) {
                // No propagar errores individuales — continuar con el siguiente post
                continue;
            }
        }
    }
}
