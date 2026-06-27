<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use Illuminate\Support\Facades\Http;

class InstagramInsightsAdapter implements AnalyticsContract
{
    private bool $mockMode;

    public function __construct()
    {
        $this->mockMode = (bool) config('services.instagram.mock_mode', true);
    }

    public function platform(): string
    {
        return 'instagram';
    }

    public function fetchMetrics(string $platformPostId): array
    {
        if ($this->mockMode) {
            return $this->mockMetrics($platformPostId);
        }

        $accessToken = config('services.instagram.access_token');
        $response    = Http::get("https://graph.facebook.com/v19.0/{$platformPostId}/insights", [
            'metric'       => 'reach,impressions,likes,comments,shares,saved,video_views',
            'access_token' => $accessToken,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Instagram Insights error: ' . $response->status());
        }

        $data = collect($response->json('data', []))->keyBy('name');

        return [
            'reach'          => $data->get('reach')['values.0.value'] ?? 0,
            'views'          => $data->get('impressions')['values.0.value'] ?? 0,
            'likes'          => $data->get('likes')['values.0.value'] ?? 0,
            'comments'       => $data->get('comments')['values.0.value'] ?? 0,
            'shares'         => $data->get('shares')['values.0.value'] ?? 0,
            'saves'          => $data->get('saved')['values.0.value'] ?? 0,
            'engagement_rate' => 0,
            'click_through_rate' => 0,
        ];
    }

    private function mockMetrics(string $postId): array
    {
        $seed   = crc32($postId);
        $reach  = abs($seed % 20000) + 500;
        $likes  = (int) ($reach * (abs($seed % 12) + 3) / 100);
        $saves  = (int) ($reach * (abs($seed % 5) + 1) / 100);

        return [
            'reach'              => $reach,
            'views'              => (int) ($reach * 1.3),
            'likes'              => $likes,
            'comments'           => (int) ($likes * 0.08),
            'shares'             => (int) ($likes * 0.05),
            'saves'              => $saves,
            'engagement_rate'    => round(($likes + $saves) / max($reach, 1), 4),
            'click_through_rate' => round(abs($seed % 30) / 1000, 4),
        ];
    }
}
