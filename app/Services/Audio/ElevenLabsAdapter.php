<?php

declare(strict_types=1);

namespace App\Services\Audio;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ElevenLabsAdapter implements AudioServiceContract
{
    private const TTS_URL = 'https://api.elevenlabs.io/v1/text-to-speech';
    private const SFX_URL = 'https://api.elevenlabs.io/v1/sound-generation';

    public function __construct(
        private readonly string $apiKey,
        private readonly bool   $mockMode = false,
    ) {}

    public function generateSpeech(
        string $text,
        string $voiceId,
        array  $settings = [],
    ): AudioResult {
        if ($this->mockMode) {
            return $this->mockAudio('voice', strlen($text) * 0.06);
        }

        $payload = [
            'text'           => $text,
            'model_id'       => $settings['model_id'] ?? 'eleven_multilingual_v2',
            'voice_settings' => [
                'stability'        => $settings['stability']        ?? 0.5,
                'similarity_boost' => $settings['similarity_boost'] ?? 0.75,
                'style'            => $settings['style']            ?? 0.0,
                'use_speaker_boost'=> true,
            ],
        ];

        $response = Http::withHeaders([
            'xi-api-key'   => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post(self::TTS_URL . "/{$voiceId}", $payload);

        if ($response->failed()) {
            throw new RuntimeException("ElevenLabs TTS falló [{$response->status()}]: " . $response->body());
        }

        $path = $this->storeAudio($response->body(), 'voice');

        // ElevenLabs devuelve duración en headers a veces, estimamos por caracteres si no
        $duration = (float) ($response->header('X-Audio-Duration') ?? strlen($text) * 0.06);

        Log::info('ElevenLabs TTS generado', ['voice_id' => $voiceId, 'chars' => strlen($text)]);

        return new AudioResult(
            filePath:        $path,
            durationSeconds: $duration,
            costUsd:         $this->estimateSpeechCost($text),
            serviceJobId:    null,
            format:          'mp3',
            raw:             ['voice_id' => $voiceId],
        );
    }

    public function generateSoundEffect(
        string $prompt,
        float  $durationSeconds = 5.0,
    ): AudioResult {
        if ($this->mockMode) {
            return $this->mockAudio('sfx', $durationSeconds);
        }

        $response = Http::withHeaders([
            'xi-api-key'   => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post(self::SFX_URL, [
            'text'             => $prompt,
            'duration_seconds' => $durationSeconds,
            'prompt_influence' => 0.3,
        ]);

        if ($response->failed()) {
            throw new RuntimeException("ElevenLabs SFX falló [{$response->status()}]: " . $response->body());
        }

        $path = $this->storeAudio($response->body(), 'sfx');

        return new AudioResult(
            filePath:        $path,
            durationSeconds: $durationSeconds,
            costUsd:         0.008,  // ~$0.008 por SFX
            serviceJobId:    null,
            format:          'mp3',
            raw:             ['prompt' => $prompt],
        );
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    private function storeAudio(string $contents, string $type): string
    {
        $path = "audio/{$type}/" . uniqid('', true) . '.mp3';
        Storage::disk('public')->put($path, $contents);
        return $path;
    }

    private function estimateSpeechCost(string $text): float
    {
        // ElevenLabs: ~$0.30 por 1000 caracteres en plan Creator
        return round(strlen($text) / 1000 * 0.30, 6);
    }

    private function mockAudio(string $type, float $duration): AudioResult
    {
        Log::info("[MOCK] ElevenLabs {$type} generado", ['duration' => $duration]);

        return new AudioResult(
            filePath:        "https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3",
            durationSeconds: $duration,
            costUsd:         $type === 'voice' ? 0.015 : 0.008,
            serviceJobId:    'mock-' . uniqid('', true),
            format:          'mp3',
            raw:             ['mock' => true],
        );
    }
}
