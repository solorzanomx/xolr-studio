<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\FormatPreset;
use Illuminate\Database\Seeder;

class FormatPresetSeeder extends Seeder
{
    public function run(): void
    {
        $presets = [
            [
                'slug'         => 'instagram_post_square',
                'name'         => 'Instagram Post (1:1)',
                'width'        => 1080,
                'height'       => 1080,
                'aspect_ratio' => '1:1',
                'platform'     => 'instagram',
                'is_system'    => true,
            ],
            [
                'slug'         => 'instagram_story_vertical',
                'name'         => 'Instagram Story / Reel (9:16)',
                'width'        => 1080,
                'height'       => 1920,
                'aspect_ratio' => '9:16',
                'platform'     => 'instagram',
                'is_system'    => true,
            ],
            [
                'slug'         => 'instagram_carousel_frame',
                'name'         => 'Instagram Carousel Frame',
                'width'        => 1080,
                'height'       => 1080,
                'aspect_ratio' => '1:1',
                'platform'     => 'instagram',
                'is_system'    => true,
            ],
            [
                'slug'         => 'instagram_landscape',
                'name'         => 'Instagram Landscape (16:9)',
                'width'        => 1080,
                'height'       => 608,
                'aspect_ratio' => '16:9',
                'platform'     => 'instagram',
                'is_system'    => true,
            ],
            [
                'slug'         => 'youtube_thumbnail',
                'name'         => 'YouTube Thumbnail (16:9)',
                'width'        => 1280,
                'height'       => 720,
                'aspect_ratio' => '16:9',
                'platform'     => 'youtube',
                'is_system'    => true,
            ],
            [
                'slug'         => 'youtube_short_vertical',
                'name'         => 'YouTube Short (9:16)',
                'width'        => 1080,
                'height'       => 1920,
                'aspect_ratio' => '9:16',
                'platform'     => 'youtube',
                'is_system'    => true,
            ],
            [
                'slug'         => 'portrait_hdr',
                'name'         => 'Portrait HDR (9:16)',
                'width'        => 768,
                'height'       => 1344,
                'aspect_ratio' => '9:16',
                'platform'     => 'general',
                'is_system'    => true,
            ],
            [
                'slug'         => 'landscape_cinematic',
                'name'         => 'Landscape Cinematic (16:9)',
                'width'        => 1344,
                'height'       => 768,
                'aspect_ratio' => '16:9',
                'platform'     => 'general',
                'is_system'    => true,
            ],
            [
                'slug'         => 'square_standard',
                'name'         => 'Square Standard (1:1)',
                'width'        => 1024,
                'height'       => 1024,
                'aspect_ratio' => '1:1',
                'platform'     => 'general',
                'is_system'    => true,
            ],
            [
                'slug'         => 'print_a4_landscape',
                'name'         => 'Print A4 Landscape',
                'width'        => 3508,
                'height'       => 2480,
                'aspect_ratio' => '297:210',
                'platform'     => 'print',
                'is_system'    => true,
            ],
        ];

        foreach ($presets as $preset) {
            FormatPreset::firstOrCreate(['slug' => $preset['slug']], $preset);
        }
    }
}
