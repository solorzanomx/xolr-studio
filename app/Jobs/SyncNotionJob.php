<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Project;
use App\Services\Notion\NotionAdapter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncNotionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 60;

    public function __construct(
        private readonly int $projectId,
    ) {}

    public function handle(NotionAdapter $notion): void
    {
        $project = Project::with(['seasons.episodes'])->findOrFail($this->projectId);

        $settings    = $project->settings ?? [];
        $episodesDb  = $settings['notion_episodes_db'] ?? null;

        if (! $episodesDb || ! $notion->isConfigured()) {
            return;
        }

        foreach ($project->seasons as $season) {
            foreach ($season->episodes as $episode) {
                try {
                    $notion->syncEpisode($episode, $episodesDb);
                } catch (\Throwable) {
                    continue;
                }
            }
        }
    }
}
