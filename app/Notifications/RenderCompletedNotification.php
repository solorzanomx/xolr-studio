<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Render;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RenderCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Render $render) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $shot    = $this->render->shot;
        $success = $this->render->status === 'completed';

        return [
            'type'         => $success ? 'render_completed' : 'render_failed',
            'icon'         => $success ? 'check-circle' : 'alert-triangle',
            'title'        => $success ? 'Render completado' : 'Render fallido',
            'body'         => $shot?->label ?? 'Shot #' . $this->render->shot_id,
            'meta'         => $this->render->quality_tier,
            'render_id'    => $this->render->id,
            'shot_id'      => $this->render->shot_id,
            'url'          => '/shots/' . $this->render->shot_id,
        ];
    }
}
