<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AIDirectorResult;
use App\Models\User;
use App\Notifications\AIDirectorCompletedNotification;
use App\Services\AIDirectorService;
use App\Services\GhostDirectorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessAIDirectorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 120;

    public function __construct(
        private readonly int $resultId,
    ) {}

    public function handle(AIDirectorService $service, GhostDirectorService $ghostDirector): void
    {
        $result = AIDirectorResult::findOrFail($this->resultId);
        $result->update(['status' => 'processing']);

        $episode = $result->episode()->with('season.project')->firstOrFail();
        $project = $episode->season->project;

        // Actualiza perfil del Ghost Director antes de analizar
        $ghostProfile = $ghostDirector->buildProfile($project);

        $structure = $service->analyze($episode, $ghostProfile);

        $result->update([
            'status'                  => 'completed',
            'proposed_structure'      => $structure,
            'ghost_profile_snapshot'  => $ghostProfile,
        ]);

        User::all()->each(fn($u) => $u->notify(new AIDirectorCompletedNotification($result->fresh())));
    }

    public function failed(Throwable $e): void
    {
        $result = AIDirectorResult::where('id', $this->resultId)->first();
        $result?->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        if ($result) {
            User::all()->each(fn($u) => $u->notify(new AIDirectorCompletedNotification($result)));
        }
    }
}
