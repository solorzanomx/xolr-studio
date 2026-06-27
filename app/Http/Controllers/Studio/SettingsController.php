<?php

declare(strict_types=1);

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Studio/Settings', [
            'integrations' => [
                'runpod' => [
                    'label' => 'RunPod',
                    'description' => 'Render farm — FLUX, video, upscale, lip sync',
                    'configured' => ! empty(config('services.runpod.key')),
                ],
                'elevenlabs' => [
                    'label' => 'ElevenLabs',
                    'description' => 'Text-to-speech, voice cloning, sound effects',
                    'configured' => ! empty(config('services.elevenlabs.key')),
                ],
                'anthropic' => [
                    'label' => 'Claude (Anthropic)',
                    'description' => 'AI Director, Script Generator, captions, SEO',
                    'configured' => ! empty(config('services.anthropic.key')),
                ],
                'did' => [
                    'label' => 'D-ID',
                    'description' => 'Lip sync — calidad Production (~$0.015/seg)',
                    'configured' => ! empty(config('services.did.key')),
                ],
                'heygen' => [
                    'label' => 'HeyGen',
                    'description' => 'Lip sync — calidad Premium (~$0.025/seg)',
                    'configured' => ! empty(config('services.heygen.key')),
                ],
                'suno' => [
                    'label' => 'Suno',
                    'description' => 'Generación de música original (plan Pro)',
                    'configured' => ! empty(config('services.suno.key')),
                ],
            ],
        ]);
    }
}
