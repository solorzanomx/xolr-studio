<?php

declare(strict_types=1);

namespace App\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use App\Models\AudioAsset;
use App\Models\Budget;
use App\Models\Project;
use App\Models\Render;
use App\Models\TalkingRender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function index(Request $request): Response
    {
        $projects = Project::orderBy('name')->get(['id', 'name', 'monthly_budget_usd']);
        $project  = $request->filled('project_id')
            ? Project::findOrFail($request->project_id)
            : $projects->first();

        $year  = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);

        // Current period budget record
        $budget = $project
            ? Budget::firstOrNew([
                'project_id'   => $project->id,
                'period_year'  => $year,
                'period_month' => $month,
            ], ['budget_usd' => $project->monthly_budget_usd ?? 0])
            : null;

        // Actual costs from source tables for selected period
        $periodStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $periodEnd   = $periodStart->copy()->endOfMonth();

        $actualCosts = $this->computeActualCosts($project, $periodStart, $periodEnd);

        // Monthly history (last 12 months)
        $history = $project
            ? Budget::where('project_id', $project->id)
                ->orderByDesc('period_year')->orderByDesc('period_month')
                ->limit(12)
                ->get()
                ->map(fn($b) => [
                    'label'          => Carbon::createFromDate($b->period_year, $b->period_month, 1)->format('M Y'),
                    'budget_usd'     => (float) $b->budget_usd,
                    'spent_usd'      => (float) $b->spent_usd,
                    'render_cost'    => (float) $b->render_cost_usd,
                    'audio_cost'     => (float) $b->audio_cost_usd,
                    'lipsync_cost'   => (float) $b->lipsync_cost_usd,
                    'api_cost'       => (float) $b->api_cost_usd,
                ])
                ->values()
            : collect();

        // Top expensive shots (all time for this project)
        $topShots = $project
            ? Render::with('shot:id,label')
                ->whereHas('shot.scene.episode.season', fn($q) => $q->where('project_id', $project->id))
                ->whereNotNull('gpu_cost_usd')
                ->orderByDesc('gpu_cost_usd')
                ->limit(10)
                ->get()
                ->map(fn($r) => [
                    'shot_id'      => $r->shot_id,
                    'label'        => $r->shot?->label ?? 'Shot #' . $r->shot_id,
                    'quality_tier' => $r->quality_tier,
                    'cost'         => (float) $r->gpu_cost_usd,
                    'date'         => $r->created_at->toDateString(),
                ])
            : collect();

        // Burn rate: average daily spend this month (from actual render costs)
        $daysElapsed  = min(now()->day, $periodEnd->day);
        $burnRateDaily = $daysElapsed > 0 ? round($actualCosts['render_cost'] / $daysElapsed, 4) : 0;
        $daysRemaining = $periodEnd->diffInDays(now()->endOfDay());
        $forecastTotal = round($actualCosts['render_cost'] + ($burnRateDaily * $daysRemaining), 2);

        return Inertia::render('Budget/Index', [
            'project'       => $project?->only(['id', 'name', 'monthly_budget_usd']),
            'projects'      => $projects->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'monthly_budget_usd' => (float) ($p->monthly_budget_usd ?? 0)]),
            'budget'        => $budget ? [
                'id'         => $budget->id,
                'budget_usd' => (float) ($budget->exists ? $budget->budget_usd : ($project->monthly_budget_usd ?? 0)),
                'spent_usd'  => (float) $budget->spent_usd,
            ] : null,
            'actualCosts'   => $actualCosts,
            'history'       => $history,
            'topShots'      => $topShots,
            'burnRate'      => $burnRateDaily,
            'forecast'      => $forecastTotal,
            'period'        => ['year' => $year, 'month' => $month],
            'filters'       => $request->only(['project_id', 'year', 'month']),
        ]);
    }

    public function updateBudget(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'monthly_budget_usd' => 'required|numeric|min:0|max:999999',
        ]);

        $project->update(['monthly_budget_usd' => $data['monthly_budget_usd']]);

        return back()->with('success', 'Presupuesto mensual actualizado.');
    }

    public function syncMonth(Request $request, Project $project): RedirectResponse
    {
        $year  = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);

        $periodStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $periodEnd   = $periodStart->copy()->endOfMonth();

        $costs = $this->computeActualCosts($project, $periodStart, $periodEnd);

        Budget::updateOrCreate(
            ['project_id' => $project->id, 'period_year' => $year, 'period_month' => $month],
            [
                'budget_usd'      => $project->monthly_budget_usd ?? 0,
                'render_cost_usd' => $costs['render_cost'],
                'audio_cost_usd'  => $costs['audio_cost'],
                'lipsync_cost_usd'=> $costs['lipsync_cost'],
                'api_cost_usd'    => $costs['api_cost'],
                'spent_usd'       => $costs['total'],
            ]
        );

        return back()->with('success', "Costos de {$periodStart->format('M Y')} sincronizados.");
    }

    public function exportCsv(Project $project): HttpResponse
    {
        $rows = Budget::where('project_id', $project->id)
            ->orderBy('period_year')->orderBy('period_month')
            ->get();

        $csv  = "Periodo,Presupuesto,Gastado,Renders,Audio,Lip Sync,API\n";
        foreach ($rows as $b) {
            $label = Carbon::createFromDate($b->period_year, $b->period_month, 1)->format('Y-m');
            $csv  .= implode(',', [
                $label,
                $b->budget_usd,
                $b->spent_usd,
                $b->render_cost_usd,
                $b->audio_cost_usd,
                $b->lipsync_cost_usd,
                $b->api_cost_usd,
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"budget-{$project->slug}.csv\"",
        ]);
    }

    private function computeActualCosts(
        ?Project $project,
        Carbon $start,
        Carbon $end
    ): array {
        if (! $project) {
            return ['render_cost' => 0, 'audio_cost' => 0, 'lipsync_cost' => 0, 'api_cost' => 0, 'total' => 0];
        }

        $renderCost = (float) Render::whereHas(
            'shot.scene.episode.season', fn($q) => $q->where('project_id', $project->id)
        )->whereBetween('created_at', [$start, $end])->sum('gpu_cost_usd');

        $audioCost = (float) AudioAsset::where('project_id', $project->id)
            ->whereBetween('created_at', [$start, $end])
            ->sum('generation_cost_usd');

        $lipsyncCost = (float) TalkingRender::whereHas(
            'shot.scene.episode.season', fn($q) => $q->where('project_id', $project->id)
        )->whereBetween('created_at', [$start, $end])->sum('service_cost_usd');

        $total = round($renderCost + $audioCost + $lipsyncCost, 6);

        return [
            'render_cost'  => round($renderCost, 6),
            'audio_cost'   => round($audioCost, 6),
            'lipsync_cost' => round($lipsyncCost, 6),
            'api_cost'     => 0,
            'total'        => $total,
        ];
    }
}
