<?php

declare(strict_types=1);

namespace App\Services\Social;

use App\Models\SocialPost;
use Illuminate\Support\Facades\Http;

class InstagramAdapter implements SocialPostContract
{
    private bool $mockMode;
    private string $accessToken;
    private string $accountId;

    public function __construct()
    {
        $this->mockMode    = (bool) config('services.instagram.mock_mode', true);
        $this->accessToken = (string) config('services.instagram.access_token', '');
        $this->accountId   = (string) config('services.instagram.account_id', '');
    }

    public function publish(SocialPost $post): string
    {
        if ($this->mockMode) {
            return 'ig_mock_' . uniqid();
        }

        // Paso 1: Crear media object
        $mediaIds = [];
        foreach ($post->media_paths ?? [] as $mediaUrl) {
            $createRes = Http::post("https://graph.facebook.com/v19.0/{$this->accountId}/media", [
                'image_url'    => $mediaUrl,
                'caption'      => $post->caption,
                'access_token' => $this->accessToken,
            ]);

            if ($createRes->failed()) {
                throw new \RuntimeException('Instagram media create error: ' . $createRes->body());
            }

            $mediaIds[] = $createRes->json('id');
        }

        if (empty($mediaIds)) {
            throw new \RuntimeException('No hay media para publicar.');
        }

        if (count($mediaIds) === 1 && in_array($post->post_type, ['post', 'reel', 'story'])) {
            // Single image/video publish
            $publishRes = Http::post("https://graph.facebook.com/v19.0/{$this->accountId}/media_publish", [
                'creation_id'  => $mediaIds[0],
                'access_token' => $this->accessToken,
            ]);
        } else {
            // Carousel
            $carouselRes = Http::post("https://graph.facebook.com/v19.0/{$this->accountId}/media", [
                'media_type'   => 'CAROUSEL',
                'children'     => implode(',', $mediaIds),
                'caption'      => $post->caption,
                'access_token' => $this->accessToken,
            ]);

            if ($carouselRes->failed()) {
                throw new \RuntimeException('Instagram carousel create error: ' . $carouselRes->body());
            }

            $publishRes = Http::post("https://graph.facebook.com/v19.0/{$this->accountId}/media_publish", [
                'creation_id'  => $carouselRes->json('id'),
                'access_token' => $this->accessToken,
            ]);
        }

        if ($publishRes->failed()) {
            throw new \RuntimeException('Instagram publish error: ' . $publishRes->body());
        }

        return $publishRes->json('id');
    }

    public function delete(string $platformPostId): void
    {
        if ($this->mockMode) {
            return;
        }

        Http::delete("https://graph.facebook.com/v19.0/{$platformPostId}", [
            'access_token' => $this->accessToken,
        ]);
    }

    public function supportsType(string $postType): bool
    {
        return in_array($postType, ['post', 'carousel', 'story', 'reel']);
    }
}
