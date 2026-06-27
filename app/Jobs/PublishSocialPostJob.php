<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\SocialPost;
use App\Services\Social\InstagramAdapter;
use App\Services\Social\SocialPostContract;
use App\Services\Social\YouTubeAdapter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class PublishSocialPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(
        private readonly int $postId,
    ) {}

    public function handle(): void
    {
        $post = SocialPost::findOrFail($this->postId);

        if ($post->status === 'published') {
            return;
        }

        $post->update(['status' => 'publishing']);

        $adapter = $this->resolveAdapter($post->platform);
        $platformId = $adapter->publish($post);

        $post->update([
            'status'           => 'published',
            'platform_post_id' => $platformId,
            'published_at'     => now(),
            'error_message'    => null,
        ]);

        // Actualizar el event del calendario si existe
        if ($post->calendar_event_id) {
            $post->calendarEvent?->update(['status' => 'published']);
        }
    }

    private function resolveAdapter(string $platform): SocialPostContract
    {
        return match ($platform) {
            'instagram', 'facebook' => new InstagramAdapter(),
            'youtube', 'tiktok'     => new YouTubeAdapter(),
            default => throw new \RuntimeException("Plataforma no soportada: {$platform}"),
        };
    }

    public function failed(Throwable $e): void
    {
        SocialPost::where('id', $this->postId)->update([
            'status'        => 'failed',
            'error_message' => $e->getMessage(),
        ]);
    }
}
