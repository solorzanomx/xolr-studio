<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\RenderTemplate;
use Illuminate\Database\Seeder;

class RenderTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'slug'             => 'flux_schnell_draft',
                'name'             => 'FLUX.1 Schnell — Draft',
                'base_model'       => 'black-forest-labs/FLUX.1-schnell',
                'default_steps'    => 4,
                'default_cfg'      => 1.0,
                'supports_lora'    => true,
                'supports_pulid'   => false,
            ],
            [
                'slug'             => 'flux_dev_standard',
                'name'             => 'FLUX.1 Dev — Standard',
                'base_model'       => 'black-forest-labs/FLUX.1-dev',
                'default_steps'    => 28,
                'default_cfg'      => 3.5,
                'supports_lora'    => true,
                'supports_pulid'   => false,
            ],
            [
                'slug'             => 'flux_dev_final',
                'name'             => 'FLUX.1 Dev + Upscale 4x — Final',
                'base_model'       => 'black-forest-labs/FLUX.1-dev',
                'default_steps'    => 28,
                'default_cfg'      => 3.5,
                'supports_lora'    => true,
                'supports_pulid'   => true,
            ],
            [
                'slug'             => 'flux_portrait_pulid',
                'name'             => 'FLUX.1 Dev + PuLID — Portrait',
                'base_model'       => 'black-forest-labs/FLUX.1-dev',
                'default_steps'    => 30,
                'default_cfg'      => 3.5,
                'supports_lora'    => true,
                'supports_pulid'   => true,
            ],
            [
                'slug'             => 'wan_video_generation',
                'name'             => 'WAN 2.1 — Video',
                'base_model'       => 'wan-ai/WAN-2.1',
                'default_steps'    => 20,
                'default_cfg'      => 5.0,
                'supports_lora'    => false,
                'supports_pulid'   => false,
            ],
        ];

        foreach ($templates as $template) {
            RenderTemplate::firstOrCreate(['slug' => $template['slug']], $template);
        }
    }
}
