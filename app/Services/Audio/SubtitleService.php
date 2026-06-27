<?php

declare(strict_types=1);

namespace App\Services\Audio;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SubtitleService
{
    private const WHISPER_URL = 'https://api.openai.com/v1/audio/transcriptions';

    public function __construct(
        private readonly ?string $openAiKey,
        private readonly bool    $mockMode = false,
    ) {}

    /**
     * Transcribe un audio y devuelve el contenido SRT como string.
     */
    public function transcribe(string $audioPath, string $language = 'es'): string
    {
        if ($this->mockMode || ! $this->openAiKey) {
            return $this->mockSrt();
        }

        $isExternal = str_starts_with($audioPath, 'http');
        $filePath   = $isExternal ? $this->downloadTemp($audioPath) : Storage::disk('public')->path($audioPath);

        $response = Http::withToken($this->openAiKey)
            ->timeout(120)
            ->attach('file', file_get_contents($filePath), basename($filePath))
            ->post(self::WHISPER_URL, [
                'model'           => 'whisper-1',
                'language'        => $language,
                'response_format' => 'srt',
            ]);

        if ($isExternal) {
            @unlink($filePath);
        }

        if ($response->failed()) {
            throw new RuntimeException("Whisper falló [{$response->status()}]: " . $response->body());
        }

        Log::info('Whisper transcripción completada', ['path' => $audioPath]);

        return $response->body();
    }

    /**
     * Guarda el SRT en storage y devuelve el path.
     */
    public function saveSrt(string $srtContent, string $assetId): string
    {
        $path = "subtitles/audio_{$assetId}.srt";
        Storage::disk('public')->put($path, $srtContent);
        return $path;
    }

    private function downloadTemp(string $url): string
    {
        $tmp = sys_get_temp_dir() . '/' . uniqid('audio_', true) . '.mp3';
        file_put_contents($tmp, file_get_contents($url));
        return $tmp;
    }

    private function mockSrt(): string
    {
        return implode("\n\n", [
            "1\n00:00:00,000 --> 00:00:02,500\nEsta es una transcripción de ejemplo.",
            "2\n00:00:02,500 --> 00:00:05,000\nGenerada por Whisper en modo mock.",
            "3\n00:00:05,000 --> 00:00:08,000\nAquí irán los subtítulos reales.",
        ]);
    }
}
