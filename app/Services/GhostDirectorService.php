<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Models\Render;

class GhostDirectorService
{
    public function buildProfile(Project $project): array
    {
        // Carga renders aprobados del proyecto con estilos
        $approvedRenders = Render::query()
            ->where('is_approved', true)
            ->whereHas('shot', function ($q) use ($project): void {
                $q->whereHas('scene.episode.season', fn($q) => $q->where('project_id', $project->id));
            })
            ->with(['shot.cameraStyle', 'shot.visualStyle'])
            ->get();

        $cameraStyles  = [];
        $visualStyles  = [];
        $purposes      = [];
        $shotTypes     = [];
        $totalApproved = $approvedRenders->count();

        foreach ($approvedRenders as $render) {
            $shot = $render->shot;
            if (! $shot) {
                continue;
            }

            if ($shot->cameraStyle) {
                $name               = $shot->cameraStyle->name;
                $cameraStyles[$name] = ($cameraStyles[$name] ?? 0) + 1;
            }

            if ($shot->visualStyle) {
                $name               = $shot->visualStyle->name;
                $visualStyles[$name] = ($visualStyles[$name] ?? 0) + 1;
            }

            $purposes[$shot->purpose]   = ($purposes[$shot->purpose] ?? 0) + 1;
            $shotTypes[$shot->shot_type] = ($shotTypes[$shot->shot_type] ?? 0) + 1;
        }

        arsort($cameraStyles);
        arsort($visualStyles);
        arsort($purposes);
        arsort($shotTypes);

        $profile = [
            'total_approved_renders' => $totalApproved,
            'top_camera_styles'      => array_slice($cameraStyles, 0, 3, true),
            'top_visual_styles'      => array_slice($visualStyles, 0, 3, true),
            'preferred_purposes'     => array_slice($purposes, 0, 3, true),
            'preferred_shot_types'   => array_slice($shotTypes, 0, 3, true),
            'updated_at'             => now()->toISOString(),
        ];

        // Persiste en project.settings para que esté disponible sin recalcular
        $settings = $project->settings ?? [];
        $settings['ghost_director_profile'] = $profile;
        $project->update(['settings' => $settings]);

        return $profile;
    }

    public function getProfile(Project $project): ?array
    {
        return ($project->settings ?? [])['ghost_director_profile'] ?? null;
    }

    public function formatForPrompt(array $profile): string
    {
        if (empty($profile) || ($profile['total_approved_renders'] ?? 0) === 0) {
            return 'No hay historial de renders aprobados aún — sin preferencias establecidas.';
        }

        $lines = ['Perfil creativo aprendido del historial de renders aprobados:'];

        if (! empty($profile['top_camera_styles'])) {
            $styles = implode(', ', array_keys($profile['top_camera_styles']));
            $lines[] = "- Estilos de cámara preferidos: {$styles}";
        }

        if (! empty($profile['top_visual_styles'])) {
            $styles = implode(', ', array_keys($profile['top_visual_styles']));
            $lines[] = "- Estilos visuales preferidos: {$styles}";
        }

        if (! empty($profile['preferred_purposes'])) {
            $purposes = implode(', ', array_keys($profile['preferred_purposes']));
            $lines[] = "- Propósitos más usados: {$purposes}";
        }

        $lines[] = "- Total de renders aprobados: {$profile['total_approved_renders']}";

        return implode("\n", $lines);
    }
}
