<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            CameraStyleSeeder::class,
            VisualStyleSeeder::class,
            FormatPresetSeeder::class,
            RenderTemplateSeeder::class,
            DemoProjectSeeder::class,
        ]);
    }
}
