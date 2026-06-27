<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\VisualStyle;
use Illuminate\Database\Seeder;

class VisualStyleSeeder extends Seeder
{
    public function run(): void
    {
        $styles = [
            [
                'slug'            => 'cinematic_dark',
                'name'            => 'Cinematic Dark',
                'description'     => 'Iluminación dramática de alto contraste, estética cinematográfica oscura.',
                'example_prompts' => ['cinematic dark, dramatic lighting, high contrast, film grain, 35mm aesthetic'],
            ],
            [
                'slug'            => 'photorealistic',
                'name'            => 'Photorealistic',
                'description'     => 'Foto ultrarrealista con detalle nítido e iluminación natural.',
                'example_prompts' => ['photorealistic, sharp detail, natural lighting, 8k resolution, RAW photo'],
            ],
            [
                'slug'            => 'editorial',
                'name'            => 'Editorial',
                'description'     => 'Fotografía editorial limpia de calidad revista.',
                'example_prompts' => ['editorial photography, clean composition, professional, studio lighting, magazine quality'],
            ],
            [
                'slug'            => 'moody_noir',
                'name'            => 'Moody Noir',
                'description'     => 'Estética noir con sombras profundas y tonos desaturados.',
                'example_prompts' => ['noir aesthetic, deep shadows, high contrast, desaturated tones, mystery'],
            ],
            [
                'slug'            => 'vibrant_lifestyle',
                'name'            => 'Vibrant Lifestyle',
                'description'     => 'Colores vibrantes, luz dorada y sensación candid de lifestyle.',
                'example_prompts' => ['vibrant colors, golden hour light, lifestyle photography, warm tones, candid feel'],
            ],
        ];

        foreach ($styles as $style) {
            VisualStyle::firstOrCreate(['slug' => $style['slug']], $style);
        }
    }
}
