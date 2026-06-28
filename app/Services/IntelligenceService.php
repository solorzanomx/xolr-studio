<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Models\Render;
use App\Models\Shot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IntelligenceService
{
    public function getProjectIntelligence(Project $project): array
    {
        $project->loadMissing('seasons');
        $seasonIds  = $project->seasons->pluck('id');
        $episodeIds = DB::table('episodes')->whereIn('season_id', $seasonIds)->pluck('id');
        $sceneIds   = DB::table('scenes')->whereIn('episode_id', $episodeIds)->pluck('id');
        $campaignIds = DB::table('campaigns')->where('project_id', $project->id)->pluck('id');

        $shotIds = DB::table('shots')
            ->where(function ($q) use ($sceneIds, $campaignIds): void {
                $q->whereIn('scene_id', $sceneIds)
                  ->orWhereIn('campaign_id', $campaignIds);
            })
            ->pluck('id');

        // Shot stats
        $shots         = DB::table('shots')->whereIn('id', $shotIds)->get();
        $totalShots    = $shots->count();
        $statusCounts  = $shots->groupBy('status')->map->count();
        $shotsWithRender  = DB::table('shots')->whereIn('id', $shotIds)->whereNotNull('approved_render_id')->count();
        $shotsWithPrompt  = DB::table('prompts')->whereIn('shot_id', $shotIds)->where('is_active', true)->distinct('shot_id')->count();

        // Render stats (excluye preview renders sin shot)
        $renders      = Render::whereIn('shot_id', $shotIds)->whereNotNull('shot_id')->get();
        $totalRenders = $renders->count();
        $approved     = $renders->where('is_approved', true)->count();
        $failed       = $renders->where('status', 'failed')->count();
        $totalCostUsd = $renders->sum('gpu_cost_usd');
        $avgCostPerRender = $totalRenders > 0 ? $totalCostUsd / $totalRenders : 0;
        $approvalRate = $totalRenders > 0 ? round($approved / $totalRenders * 100, 1) : 0;

        $byQuality = $renders->groupBy('quality_tier')->map(fn($g) => [
            'count'    => $g->count(),
            'approved' => $g->where('is_approved', true)->count(),
            'cost'     => round((float) $g->sum('gpu_cost_usd'), 4),
        ]);

        // Character insights
        $characterInsights = $this->getCharacterInsights($shotIds);

        // Production Score
        $productionScore = $this->calculateProductionScore(
            totalShots: $totalShots,
            shotsWithRender: $shotsWithRender,
            shotsWithPrompt: $shotsWithPrompt,
            approvalRate: $approvalRate,
            monthlyBudgetUsd: (float) ($project->monthly_budget_usd ?? 0),
            spentThisMonthUsd: $this->getMonthlySpend($shotIds),
        );

        // Alerts
        $alerts = $this->buildAlerts(
            project: $project,
            totalShots: $totalShots,
            shotsWithRender: $shotsWithRender,
            approvalRate: $approvalRate,
            characterInsights: $characterInsights,
            statusCounts: $statusCounts,
            totalCostUsd: (float) $totalCostUsd,
        );

        return [
            'production_score'  => $productionScore,
            'shots' => [
                'total'           => $totalShots,
                'with_render'     => $shotsWithRender,
                'with_prompt'     => $shotsWithPrompt,
                'completion_pct'  => $totalShots > 0 ? round($shotsWithRender / $totalShots * 100) : 0,
                'status_counts'   => $statusCounts,
            ],
            'renders' => [
                'total'             => $totalRenders,
                'approved'          => $approved,
                'failed'            => $failed,
                'approval_rate'     => $approvalRate,
                'total_cost_usd'    => round((float) $totalCostUsd, 4),
                'avg_cost_per_render' => round($avgCostPerRender, 4),
                'by_quality'        => $byQuality,
            ],
            'character_insights' => $characterInsights,
            'alerts'             => $alerts,
        ];
    }

    private function getCharacterInsights(Collection $shotIds): array
    {
        $rows = DB::table('shot_characters as sc')
            ->join('characters as c', 'c.id', '=', 'sc.character_id')
            ->join('shots as s', 's.id', '=', 'sc.shot_id')
            ->leftJoin('renders as r', fn($j) => $j->on('r.shot_id', '=', 's.id')->where('r.is_approved', true))
            ->whereIn('sc.shot_id', $shotIds)
            ->select(
                'c.id',
                'c.name',
                DB::raw('COUNT(DISTINCT sc.shot_id) as total_shots'),
                DB::raw('COUNT(DISTINCT r.id) as approved_renders'),
                DB::raw('AVG(r.seed) as avg_seed'),
                DB::raw('MIN(r.gpu_cost_usd) as min_cost'),
            )
            ->groupBy('c.id', 'c.name')
            ->orderByDesc('total_shots')
            ->get();

        return $rows->map(function ($row): array {
            $rate = $row->total_shots > 0 ? round($row->approved_renders / $row->total_shots * 100, 1) : 0;
            return [
                'id'              => $row->id,
                'name'            => $row->name,
                'total_shots'     => $row->total_shots,
                'approved_renders' => $row->approved_renders,
                'approval_rate'   => $rate,
                'lora_health'     => $rate >= 50 ? 'good' : ($rate >= 25 ? 'warning' : 'critical'),
            ];
        })->values()->toArray();
    }

    private function getMonthlySpend(Collection $shotIds): float
    {
        return (float) Render::whereIn('shot_id', $shotIds)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('gpu_cost_usd');
    }

    private function calculateProductionScore(
        int $totalShots,
        int $shotsWithRender,
        int $shotsWithPrompt,
        float $approvalRate,
        float $monthlyBudgetUsd,
        float $spentThisMonthUsd,
    ): array {
        if ($totalShots === 0) {
            return ['score' => 0, 'grade' => 'N/A', 'breakdown' => []];
        }

        $renderPct  = round($shotsWithRender / $totalShots * 100);
        $promptPct  = round($shotsWithPrompt / $totalShots * 100);
        $qualityPct = $approvalRate;

        $budgetScore = 100;
        if ($monthlyBudgetUsd > 0) {
            $utilization = $spentThisMonthUsd / $monthlyBudgetUsd;
            $budgetScore = $utilization > 1.0 ? 0 : ($utilization > 0.9 ? 50 : 100);
        }

        $score = (int) round(
            ($renderPct * 0.40) +
            ($promptPct * 0.20) +
            ($qualityPct * 0.30) +
            ($budgetScore * 0.10)
        );

        $grade = match (true) {
            $score >= 90 => 'S',
            $score >= 75 => 'A',
            $score >= 60 => 'B',
            $score >= 45 => 'C',
            default      => 'D',
        };

        return [
            'score' => $score,
            'grade' => $grade,
            'breakdown' => [
                'render_completion' => $renderPct,
                'prompt_coverage'   => $promptPct,
                'quality_rate'      => (int) $qualityPct,
                'budget_health'     => $budgetScore,
            ],
        ];
    }

    private function buildAlerts(
        Project $project,
        int $totalShots,
        int $shotsWithRender,
        float $approvalRate,
        array $characterInsights,
        Collection $statusCounts,
        float $totalCostUsd,
    ): array {
        $alerts = [];

        // Budget alert
        $budget = (float) ($project->monthly_budget_usd ?? 0);
        if ($budget > 0) {
            $monthlySpend = Render::whereHas('shot', fn($q) => $q->whereHas('scene.episode.season', fn($q) => $q->where('project_id', $project->id)))
                ->whereMonth('created_at', now()->month)
                ->sum('gpu_cost_usd');
            $utilization = $monthlySpend / $budget;
            if ($utilization > 0.9) {
                $alerts[] = ['type' => 'budget', 'level' => $utilization > 1.0 ? 'critical' : 'warning', 'message' => sprintf('Presupuesto mensual al %.0f%% ($%.4f de $%.2f)', $utilization * 100, $monthlySpend, $budget)];
            }
        }

        // LoRA degradation alerts
        foreach ($characterInsights as $char) {
            if ($char['lora_health'] === 'critical' && $char['total_shots'] >= 3) {
                $alerts[] = ['type' => 'lora', 'level' => 'warning', 'message' => "Baja tasa de aprobación para {$char['name']} ({$char['approval_rate']}%) — posible degradación de LoRA"];
            }
        }

        // Workflow alert: shots stuck in rendering
        $stuckShots = $statusCounts->get('rendering', 0);
        if ($stuckShots > 3) {
            $alerts[] = ['type' => 'workflow', 'level' => 'info', 'message' => "{$stuckShots} shots en estado 'Renderizando' — verifica la cola de renders"];
        }

        // Low coverage alert
        if ($totalShots > 5 && $shotsWithRender / max($totalShots, 1) < 0.2) {
            $alerts[] = ['type' => 'coverage', 'level' => 'info', 'message' => 'Menos del 20% de los shots tienen render aprobado'];
        }

        // Low approval rate
        if ($approvalRate > 0 && $approvalRate < 30) {
            $alerts[] = ['type' => 'quality', 'level' => 'warning', 'message' => sprintf('Tasa de aprobación baja: %.1f%% — revisa configuración de prompts', $approvalRate)];
        }

        return $alerts;
    }

    public function getContinuityCanvas(Project $project): array
    {
        $project->loadMissing('seasons');

        $seasons = $project->seasons()->with([
            'episodes' => fn($q) => $q->orderBy('number')->with([
                'scenes' => fn($q) => $q->orderBy('sort_order')->orderBy('number')->with([
                    'shots' => fn($q) => $q->orderBy('sort_order')->orderBy('number')->with([
                        'characters:id,name',
                        'approvedRender:id,shot_id,file_path,quality_tier',
                    ]),
                ]),
            ]),
        ])->get();

        // Collect all unique characters across all shots
        $allCharacters = collect();
        $allShots      = collect();

        foreach ($seasons as $season) {
            foreach ($season->episodes as $episode) {
                foreach ($episode->scenes as $scene) {
                    foreach ($scene->shots as $shot) {
                        $allShots->push([
                            'id'             => $shot->id,
                            'label'          => "S{$season->number}E{$episode->number} E{$scene->number}S{$shot->number}",
                            'description'    => $shot->description,
                            'character_ids'  => $shot->characters->pluck('id')->toArray(),
                            'render_url'     => $shot->approvedRender?->file_path,
                            'render_tier'    => $shot->approvedRender?->quality_tier,
                            'status'         => $shot->status,
                        ]);
                        foreach ($shot->characters as $char) {
                            $allCharacters->put($char->id, ['id' => $char->id, 'name' => $char->name]);
                        }
                    }
                }
            }
        }

        return [
            'characters' => $allCharacters->values()->toArray(),
            'shots'      => $allShots->toArray(),
        ];
    }
}
