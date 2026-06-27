<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Campaign;
use App\Models\Episode;
use App\Models\Export;
use App\Models\Project;
use App\Models\Season;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ExportService
{
    public function generateZipCampaign(Export $export, Campaign $campaign): void
    {
        $export->update(['status' => 'generating']);

        $campaign->loadMissing([
            'shots' => fn($q) => $q->with('approvedRender'),
        ]);

        $shots = $campaign->shots->filter(fn($s) => $s->approvedRender && $s->approvedRender->file_path);

        if ($shots->isEmpty()) {
            $export->update([
                'status'   => 'failed',
                'metadata' => array_merge($export->metadata ?? [], ['error' => 'No hay renders aprobados con archivos en esta campaña.']),
            ]);
            return;
        }

        $slug    = str($campaign->name)->slug()->value();
        $zipName = "exports/zip_{$slug}_{$export->id}.zip";
        $zipPath = Storage::disk('local')->path($zipName);

        Storage::disk('local')->makeDirectory('exports');

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $export->update(['status' => 'failed', 'metadata' => ['error' => 'No se pudo crear el archivo ZIP.']]);
            return;
        }

        $added = 0;
        foreach ($shots as $shot) {
            $path = $shot->approvedRender->file_path;

            // Si es una URL real (mock), descargar temporalmente
            if (str_starts_with($path, 'http')) {
                $content  = @file_get_contents($path);
                $ext      = 'jpg';
                $filename = "shot_{$shot->number}.{$ext}";
                if ($content !== false) {
                    $zip->addFromString($filename, $content);
                    $added++;
                }
            } elseif (Storage::exists($path)) {
                $zip->addFile(Storage::path($path), "shot_{$shot->number}.jpg");
                $added++;
            }
        }

        $zip->close();

        $export->update([
            'status'    => $added > 0 ? 'completed' : 'failed',
            'file_path' => $added > 0 ? $zipName : null,
            'metadata'  => array_merge($export->metadata ?? [], ['files_included' => $added]),
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function getAnimaticShots(Episode $episode): array
    {
        $episode->loadMissing([
            'scenes' => fn($q) => $q->orderBy('sort_order')->orderBy('number')->with([
                'shots' => fn($q) => $q->orderBy('sort_order')->orderBy('number')->with([
                    'approvedRender:id,shot_id,file_path,quality_tier',
                    'characters:id,name',
                ]),
            ]),
        ]);

        $slides = [];
        foreach ($episode->scenes as $scene) {
            foreach ($scene->shots as $shot) {
                $slides[] = [
                    'id'               => $shot->id,
                    'label'            => "E{$scene->number}S{$shot->number}",
                    'description'      => $shot->description,
                    'duration_seconds' => $shot->duration_seconds ?? 3,
                    'render_url'       => $shot->approvedRender?->file_path,
                    'render_tier'      => $shot->approvedRender?->quality_tier,
                    'characters'       => $shot->characters->pluck('name'),
                    'shot_type'        => $shot->shot_type,
                    'has_render'       => $shot->approvedRender !== null,
                ];
            }
        }

        return $slides;
    }

    public function getProductionBibleData(Project $project): array
    {
        $project->loadMissing([
            'seasons.episodes' => fn($q) => $q->orderBy('number')->with([
                'scenes' => fn($q) => $q->orderBy('sort_order')->with([
                    'shots' => fn($q) => $q->orderBy('sort_order')->with([
                        'characters:id,name',
                        'approvedRender:id,shot_id,file_path',
                    ]),
                ]),
            ]),
            'universeNotes',
        ]);

        return [
            'project'   => $project,
            'generated' => now()->toDateTimeString(),
        ];
    }

    public function getBookData(Season $season): array
    {
        $season->loadMissing([
            'project:id,name',
            'episodes' => fn($q) => $q->orderBy('number')->with([
                'scenes' => fn($q) => $q->orderBy('sort_order')->orderBy('number')->with([
                    'shots' => fn($q) => $q->orderBy('sort_order')->orderBy('number')->with([
                        'characters:id,name',
                        'approvedRender:id,shot_id,file_path,quality_tier',
                    ]),
                ]),
            ]),
        ]);

        return [
            'season'    => $season,
            'generated' => now()->toDateTimeString(),
        ];
    }
}
