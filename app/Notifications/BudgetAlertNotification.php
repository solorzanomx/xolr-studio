<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BudgetAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Project $project,
        private readonly float $utilPct,
        private readonly float $spentUsd,
        private readonly float $budgetUsd,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $level = $this->utilPct >= 90 ? 'critical' : 'warning';

        return [
            'type'       => 'budget_alert',
            'icon'       => 'dollar-sign',
            'title'      => $level === 'critical' ? 'Presupuesto crítico' : 'Alerta de presupuesto',
            'body'       => "{$this->project->name} — " . round($this->utilPct, 1) . "% utilizado (\${$this->spentUsd} / \${$this->budgetUsd})",
            'meta'       => $level,
            'project_id' => $this->project->id,
            'url'        => '/budget?project_id=' . $this->project->id,
        ];
    }
}
