<?php

declare(strict_types=1);

namespace App\Services\Audio;

interface AudioServiceContract
{
    /**
     * Genera un voice-over o diálogo desde texto.
     * Devuelve la URL o path del audio generado.
     */
    public function generateSpeech(
        string $text,
        string $voiceId,
        array  $settings = [],
    ): AudioResult;

    /**
     * Genera un efecto de sonido o ambiente desde una descripción.
     */
    public function generateSoundEffect(
        string $prompt,
        float  $durationSeconds = 5.0,
    ): AudioResult;
}
