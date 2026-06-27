<?php

declare(strict_types=1);

namespace App\Services\Notion;

use App\Models\Episode;
use App\Models\Project;
use Illuminate\Support\Facades\Http;

class NotionAdapter
{
    private bool $mockMode;
    private string $token;

    public function __construct()
    {
        $this->mockMode = (bool) config('services.notion.mock_mode', true);
        $this->token    = (string) config('services.notion.token', '');
    }

    public function syncEpisode(Episode $episode, string $databaseId): array
    {
        if ($this->mockMode) {
            return ['id' => 'notion_mock_' . $episode->id, 'url' => '#', 'synced' => true];
        }

        $response = Http::withToken($this->token)
            ->post('https://api.notion.com/v1/pages', [
                'parent'     => ['database_id' => $databaseId],
                'properties' => [
                    'Name'   => ['title'  => [['text' => ['content' => "E{$episode->number}: {$episode->title}"]]]],
                    'Status' => ['select' => ['name' => ucfirst($episode->status ?? 'concept')]],
                    'Number' => ['number' => $episode->number],
                ],
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Notion sync error: ' . $response->body());
        }

        return $response->json();
    }

    public function syncProject(Project $project, string $databaseId): array
    {
        if ($this->mockMode) {
            return ['synced' => true, 'project' => $project->name];
        }

        $response = Http::withToken($this->token)
            ->post('https://api.notion.com/v1/pages', [
                'parent'     => ['database_id' => $databaseId],
                'properties' => [
                    'Name'   => ['title'  => [['text' => ['content' => $project->name]]]],
                    'Status' => ['select' => ['name' => ucfirst($project->status ?? 'development')]],
                    'Type'   => ['select' => ['name' => ucfirst(str_replace('_', ' ', $project->type ?? ''))]],
                ],
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Notion sync error: ' . $response->body());
        }

        return $response->json();
    }

    public function isConfigured(): bool
    {
        return $this->mockMode || $this->token !== '';
    }
}
