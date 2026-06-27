<?php

declare(strict_types=1);

namespace App\Services\Audio;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SunoAdapter implements MusicServiceContract
{
    private const BASE_URL = 'https://studio-api.suno.ai/api';

    private const MOCK_TRACKS = [
        'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-2.mp3',
        'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-3.mp3',
        'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-9.mp3',
    ];

    public function __construct(
        private readonly ?string $apiKey,
        private readonly bool    $mockMode = false,
    ) {}

    public function generate(
        string $prompt,
        float  $durationSeconds,
        string $mood = 'neutral',
    ): AudioResult {
        if ($this->mockMode || ! $this->apiKey) {
            return $this->mockTrack($durationSeconds);
        }

        // Suno API: genera 2 variantes, tomamos la primera
        $response = Http::withToken($this->apiKey)
            ->timeout(120)
            ->post(self::BASE_URL . '/generate/v2/', [
                'prompt'     => "{$mood} {$prompt}",
                'mv'         => 'chirp-v3-5',
                'make_instrumental' => true,
            ]);

        if ($response->failed()) {
            throw new RuntimeException("Suno API falló [{$response->status()}]: " . $response->body());
        }

        $clips = $response->json('clips', []);

        if (empty($clips)) {
            throw new RuntimeException('Suno no devolvió clips.');
        }

        $clip    = $clips[0];
        $fileUrl = $clip['audio_url'] ?? null;

        if (! $fileUrl) {
            throw new RuntimeException('Suno clip sin audio_url.');
        }

        Log::info('Suno música generada', ['clip_id' => $clip['id'] ?? null]);

        return new AudioResult(
            filePath:        $fileUrl,
            durationSeconds: $durationSeconds,
            costUsd:         0.02,  // estimado — Suno cobra por créditos
            serviceJobId:    $clip['id'] ?? null,
            format:          'mp3',
            raw:             $clip,
        );
    }

    private function mockTrack(float $duration): AudioResult
    {
        $url = self::MOCK_TRACKS[array_rand(self::MOCK_TRACKS)];
        Log::info('[MOCK] Suno música generada', ['duration' => $duration]);

        return new AudioResult(
            filePath:        $url,
            durationSeconds: $duration,
            costUsd:         0.02,
            serviceJobId:    'mock-' . uniqid('', true),
            format:          'mp3',
            raw:             ['mock' => true],
        );
    }
}
