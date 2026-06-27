<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoProjectSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('email', 'lechugadesign@gmail.com')->first();

        $projects = [
            [
                'slug'       => 'strange-light',
                'name'       => 'Strange Light',
                'type'       => 'fiction_series',
                'status'     => 'production',
                'created_by' => $owner?->id,
            ],
            [
                'slug'       => 'home-del-valle',
                'name'       => 'Home del Valle',
                'type'       => 'real_estate',
                'status'     => 'production',
                'created_by' => $owner?->id,
            ],
            [
                'slug'       => 'the-walking-video-guy',
                'name'       => 'The Walking Video Guy',
                'type'       => 'youtube_channel',
                'status'     => 'production',
                'created_by' => $owner?->id,
            ],
        ];

        foreach ($projects as $project) {
            Project::firstOrCreate(['slug' => $project['slug']], $project);
        }
    }
}
