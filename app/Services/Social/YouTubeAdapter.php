<?php

declare(strict_types=1);

namespace App\Services\Social;

use App\Models\SocialPost;
use Illuminate\Support\Facades\Http;

class YouTubeAdapter implements SocialPostContract
{
    private bool $mockMode;

    public function __construct()
    {
        $this->mockMode = (bool) config('services.youtube.mock_mode', true);
    }

    public function publish(SocialPost $post): string
    {
        if ($this->mockMode) {
            return 'yt_mock_' . strtoupper(substr(uniqid(), -8));
        }

        $accessToken = config('services.youtube.access_token');

        // Crear metadata del video
        $metadata = [
            'snippet' => [
                'title'       => $post->caption ?? 'Sin título',
                'description' => '',
                'tags'        => $post->hashtags ? array_map('trim', explode('#', $post->hashtags)) : [],
                'categoryId'  => '24', // Entertainment
            ],
            'status' => [
                'privacyStatus'  => 'private', // subir como privado primero
                'publishAt'      => $post->scheduled_for?->toIso8601String(),
            ],
        ];

        // Upload resumable (en producción usaría resumable upload)
        $response = Http::withToken($accessToken)
            ->post('https://www.googleapis.com/upload/youtube/v3/videos?uploadType=multipart&part=snippet,status', $metadata);

        if ($response->failed()) {
            throw new \RuntimeException('YouTube upload error: ' . $response->body());
        }

        return $response->json('id');
    }

    public function delete(string $platformPostId): void
    {
        if ($this->mockMode) {
            return;
        }

        Http::withToken(config('services.youtube.access_token'))
            ->delete("https://www.googleapis.com/youtube/v3/videos?id={$platformPostId}");
    }

    public function supportsType(string $postType): bool
    {
        return in_array($postType, ['video', 'short']);
    }
}
