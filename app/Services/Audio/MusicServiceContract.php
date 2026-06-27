<?php

declare(strict_types=1);

namespace App\Services\Audio;

interface MusicServiceContract
{
    /**
     * Genera música desde un prompt y duración.
     */
    public function generate(
        string $prompt,
        float  $durationSeconds,
        string $mood = 'neutral',
    ): AudioResult;
}
