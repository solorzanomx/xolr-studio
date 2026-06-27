<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\AIDirectorResult;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AIDirectorCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly AIDirectorResult $result) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $success = $this->result->status === 'completed';

        return [
            'type'      => 'ai_director_completed',
            'icon'      => $success ? 'sparkles' : 'alert-triangle',
            'title'     => $success ? 'AI Director listo' : 'AI Director falló',
            'body'      => 'Episodio: ' . ($this->result->episode?->title ?? '#' . $this->result->episode_id),
            'meta'      => $this->result->status,
            'result_id' => $this->result->id,
            'url'       => '/ai-director/' . $this->result->id,
        ];
    }
}
