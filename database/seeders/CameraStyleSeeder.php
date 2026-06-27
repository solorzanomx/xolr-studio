<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CameraStyle;
use Illuminate\Database\Seeder;

class CameraStyleSeeder extends Seeder
{
    public function run(): void
    {
        $styles = [
            [
                'slug'            => 'extreme_close_up',
                'name'            => 'Extreme close-up',
                'description'     => 'Encuadre extremo sobre detalles del rostro o elemento.',
                'prompt_fragment' => 'extreme close-up, face filling frame, macro detail',
            ],
            [
                'slug'            => 'close_up',
                'name'            => 'Close-up',
                'description'     => 'Encuadre cerrado mostrando rostro y hombros.',
                'prompt_fragment' => 'close-up shot, face and shoulders visible',
            ],
            [
                'slug'            => 'medium_shot',
                'name'            => 'Medium Shot',
                'description'     => 'Encuadre medio desde la cintura hacia arriba.',
                'prompt_fragment' => 'medium shot, waist up, conversational framing',
            ],
            [
                'slug'            => 'full_body',
                'name'            => 'Full Body',
                'description'     => 'Personaje completo de cabeza a pies.',
                'prompt_fragment' => 'full body shot, head to toe, character fully visible',
            ],
            [
                'slug'            => 'wide_shot',
                'name'            => 'Wide Shot',
                'description'     => 'Plano general mostrando el entorno completo.',
                'prompt_fragment' => 'wide shot, establishing shot, environment fully visible',
            ],
            [
                'slug'            => 'over_the_shoulder',
                'name'            => 'Over the Shoulder',
                'description'     => 'Plano sobre el hombro con sujeto en primer plano.',
                'prompt_fragment' => 'over-the-shoulder shot, subject in foreground',
            ],
            [
                'slug'            => 'low_angle',
                'name'            => 'Low Angle',
                'description'     => 'Cámara apuntando hacia arriba, perspectiva de poder.',
                'prompt_fragment' => 'low angle shot, camera looking upward, powerful perspective',
            ],
            [
                'slug'            => 'bird_eye',
                'name'            => "Bird's Eye",
                'description'     => 'Vista aérea cenital.',
                'prompt_fragment' => "bird's eye view, aerial perspective, top-down",
            ],
        ];

        foreach ($styles as $style) {
            CameraStyle::firstOrCreate(['slug' => $style['slug']], $style);
        }
    }
}
