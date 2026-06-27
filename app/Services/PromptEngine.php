<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Shot;

class PromptEngine
{
    // Color keys para el preview coloreado en el frontend
    private const SOURCE_COLORS = [
        'lora'        => 'violet',
        'character'   => 'blue',
        'outfit'      => 'amber',
        'location'    => 'green',
        'lighting'    => 'teal',
        'camera'      => 'orange',
        'style'       => 'purple',
        'description' => 'amber',
        'dialogue'    => 'pink',
        'notes'       => 'red',
        'purpose'     => 'gray',
    ];

    private const PURPOSE_MODIFIERS = [
        'thumbnail'       => 'highly detailed, eye-catching, dynamic composition, rule of thirds, vibrant colors',
        'hero'            => 'wide angle, establishing shot, cinematic scale, dramatic perspective',
        'carousel_frame'  => 'clean composition, social media optimized, visually balanced',
        'broker_portrait' => 'professional portrait, confident expression, clean background, business attire',
        'property_hero'   => 'architectural photography, wide angle, golden hour lighting, aspirational',
        'narrative'       => 'cinematic, storytelling composition, atmospheric',
        'social'          => 'social media ready, vibrant, engaging, square composition',
        'talking_dialogue' => 'portrait, face clearly visible, expressive, direct eye contact, clear lip area',
    ];

    private const DEFAULT_NEGATIVE = 'blurry, low quality, deformed, watermark, text overlay, signature, extra limbs, distorted face, disfigured, ugly, duplicate, morbid, mutilated, bad anatomy, bad proportions, out of frame';

    public function compose(Shot $shot): array
    {
        $shot->loadMissing([
            'characters.outfits',
            'cameraStyle',
            'visualStyle',
            'scene.location',
        ]);

        $segments = [];
        $parts    = [];

        // 1. LoRA trigger words (primero para FLUX)
        foreach ($shot->characters as $character) {
            if ($character->lora_trigger_word) {
                $segments[] = [
                    'key'   => 'lora',
                    'label' => "LoRA: {$character->name}",
                    'color' => self::SOURCE_COLORS['lora'],
                    'text'  => $character->lora_trigger_word,
                ];
                $parts[] = $character->lora_trigger_word;
            }
        }

        // 2. Character base prompts
        foreach ($shot->characters as $character) {
            if ($character->base_prompt) {
                $segments[] = [
                    'key'   => 'character',
                    'label' => $character->name,
                    'color' => self::SOURCE_COLORS['character'],
                    'text'  => $character->base_prompt,
                ];
                $parts[] = $character->base_prompt;
            }

            // 3. Outfit del personaje en este shot (pivot outfit_id)
            $outfitId = $character->pivot->outfit_id ?? null;
            if ($outfitId) {
                $outfit = $character->outfits->firstWhere('id', $outfitId);
                if ($outfit && $outfit->prompt_fragment) {
                    $segments[] = [
                        'key'   => 'outfit',
                        'label' => "Outfit: {$outfit->name}",
                        'color' => self::SOURCE_COLORS['outfit'],
                        'text'  => $outfit->prompt_fragment,
                    ];
                    $parts[] = $outfit->prompt_fragment;
                }
            }
        }

        // 4. Location (de la escena si existe)
        $location = $shot->scene?->location;
        if ($location) {
            $locationText = $location->base_prompt ?? $location->description ?? '';
            if ($locationText) {
                $segments[] = [
                    'key'   => 'location',
                    'label' => $location->name,
                    'color' => self::SOURCE_COLORS['location'],
                    'text'  => $locationText,
                ];
                $parts[] = $locationText;
            }

            // 5. Iluminación por hora del día
            $timeOfDay = $shot->scene?->time_of_day ?? 'unspecified';
            if ($timeOfDay !== 'unspecified' && $location->lighting_by_time) {
                $lighting = $location->lighting_by_time[$timeOfDay] ?? null;
                if ($lighting) {
                    $segments[] = [
                        'key'   => 'lighting',
                        'label' => "Iluminación: {$timeOfDay}",
                        'color' => self::SOURCE_COLORS['lighting'],
                        'text'  => $lighting,
                    ];
                    $parts[] = $lighting;
                }
            }
        }

        // 6. Camera style
        if ($shot->cameraStyle?->prompt_fragment) {
            $segments[] = [
                'key'   => 'camera',
                'label' => $shot->cameraStyle->name,
                'color' => self::SOURCE_COLORS['camera'],
                'text'  => $shot->cameraStyle->prompt_fragment,
            ];
            $parts[] = $shot->cameraStyle->prompt_fragment;
        }

        // 7. Visual style
        if ($shot->visualStyle) {
            $styleText = $shot->visualStyle->name;
            if ($shot->visualStyle->description) {
                $styleText .= ', ' . $shot->visualStyle->description;
            }
            $segments[] = [
                'key'   => 'style',
                'label' => $shot->visualStyle->name,
                'color' => self::SOURCE_COLORS['style'],
                'text'  => $styleText,
            ];
            $parts[] = $styleText;
        }

        // 8. Shot description
        if ($shot->description) {
            $segments[] = [
                'key'   => 'description',
                'label' => 'Shot',
                'color' => self::SOURCE_COLORS['description'],
                'text'  => $shot->description,
            ];
            $parts[] = $shot->description;
        }

        // 9. Dialogue text (para talking shots)
        if ($shot->dialogue_text && $shot->shot_type === 'talking') {
            $segments[] = [
                'key'   => 'dialogue',
                'label' => 'Diálogo',
                'color' => self::SOURCE_COLORS['dialogue'],
                'text'  => "speaking: \"{$shot->dialogue_text}\"",
            ];
            $parts[] = "speaking: \"{$shot->dialogue_text}\"";
        }

        // 10. Director notes
        if ($shot->director_notes) {
            $segments[] = [
                'key'   => 'notes',
                'label' => 'Director',
                'color' => self::SOURCE_COLORS['notes'],
                'text'  => $shot->director_notes,
            ];
            $parts[] = $shot->director_notes;
        }

        // 11. Purpose modifiers
        $purposeMod = self::PURPOSE_MODIFIERS[$shot->purpose] ?? null;
        if ($purposeMod) {
            $segments[] = [
                'key'   => 'purpose',
                'label' => ucfirst($shot->purpose),
                'color' => self::SOURCE_COLORS['purpose'],
                'text'  => $purposeMod,
            ];
            $parts[] = $purposeMod;
        }

        $positive = implode(', ', array_filter($parts));

        return [
            'positive_prompt' => $positive,
            'negative_prompt' => self::DEFAULT_NEGATIVE,
            'composed_prompt' => $positive,
            'sources'         => $segments,
        ];
    }
}
