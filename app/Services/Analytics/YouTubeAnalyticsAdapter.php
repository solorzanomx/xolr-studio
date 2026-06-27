<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use Illuminate\Support\Facades\Http;

class YouTubeAnalyticsAdapter implements AnalyticsContract
{
    private bool $mockMode;

    public function __construct()
    {
        $this->mockMode = (bool) config('services.youtube.mock_mode', true);
    }

    public function platform(): string
    {
        return 'youtube';
    }

    public function fetchMetrics(string $platformPostId): array
    {
        if ($this->mockMode) {
            return $this->mockMetrics($platformPostId);
        }

        $accessToken = config('services.youtube.access_token');
        $response    = Http::withToken($accessToken)
            ->get('https://youtubeanalytics.googleapis.com/v2/reports', [
                'ids'        => 'channel==MINE',
                'startDate'  => now()->subDays(7)->toDateString(),
                'endDate'    => now()->toDateString(),
                'metrics'    => 'views,estimatedMinutesWatched,likes,dislikes,comments,shares,subscribersGained',
                'filters'    => "video=={$platformPostId}",
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('YouTube Analytics error: ' . $response->status());
        }

        $rows = $response->json('rows.0', []);

        return [
            'views'                  => $rows[0] ?? 0,
            'avg_watch_time_seconds' => isset($rows[1]) ? (int) ($rows[1] * 60 / max($rows[0], 1)) : 0,
            'likes'                  => $rows[2] ?? 0,
            'comments'               => $rows[4] ?? 0,
            'shares'                 => $rows[5] ?? 0,
            'subscribers_gained'     => $rows[6] ?? 0,
            'click_through_rate'     => 0,
            'engagement_rate'        => isset($rows[0], $rows[2]) && $rows[0] > 0
                ? round(($rows[2] + $rows[4]) / $rows[0], 4)
                : 0,
        ];
    }

    private function mockMetrics(string $postId): array
    {
        // Genera datos mock razonables basados en el ID del post
        $seed  = crc32($postId);
        $views = abs($seed % 50000) + 1000;

        return [
            'views'                  => $views,
            'avg_watch_time_seconds' => abs($seed % 240) + 60,
            'likes'                  => (int) ($views * (abs($seed % 8) + 2) / 100),
            'comments'               => (int) ($views * 0.005),
            'shares'                 => (int) ($views * 0.002),
            'subscribers_gained'     => abs($seed % 50),
            'click_through_rate'     => round((abs($seed % 80) + 20) / 1000, 4),
            'engagement_rate'        => round((abs($seed % 60) + 10) / 1000, 4),
        ];
    }
}
