<?php

declare(strict_types=1);

namespace App\Http\Controllers\Intelligence;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Scene;
use App\Services\ContinuityCheckerService;
use App\Services\IntelligenceService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class IntelligenceController extends Controller
{
    public function __construct(
        private readonly IntelligenceService $intelligence,
        private readonly ContinuityCheckerService $continuityChecker,
    ) {}

    public function show(Project $project): Response
    {
        $data   = $this->intelligence->getProjectIntelligence($project);
        $canvas = $this->intelligence->getContinuityCanvas($project);

        return Inertia::render('Intelligence/Show', [
            'project'  => $project->only(['id', 'name', 'slug', 'monthly_budget_usd', 'settings']),
            'stats'    => $data,
            'canvas'   => $canvas,
        ]);
    }

    public function checkSceneContinuity(Scene $scene): RedirectResponse
    {
        try {
            $result = $this->continuityChecker->checkScene($scene);

            return back()
                ->with('continuity_check', $result)
                ->with('success', 'Continuidad verificada.');
        } catch (\Throwable $e) {
            return back()->withErrors(['continuity' => $e->getMessage()]);
        }
    }
}
