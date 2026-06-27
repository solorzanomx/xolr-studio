<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SocialPost;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PostPublishedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly SocialPost $post) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $success = $this->post->status === 'published';

        return [
            'type'    => $success ? 'post_published' : 'post_failed',
            'icon'    => $success ? 'send' : 'alert-triangle',
            'title'   => $success ? 'Post publicado' : 'Error al publicar',
            'body'    => ucfirst($this->post->platform) . ' — ' . ($this->post->caption ? mb_substr($this->post->caption, 0, 50) . '…' : $this->post->post_type),
            'meta'    => $this->post->platform,
            'post_id' => $this->post->id,
            'url'     => '/calendar',
        ];
    }
}
