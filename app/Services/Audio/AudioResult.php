<?php

declare(strict_types=1);

namespace App\Services\Audio;

final class AudioResult
{
    public function __construct(
        public readonly string  $filePath,       // path local en storage o URL externa
        public readonly float   $durationSeconds,
        public readonly ?float  $costUsd,
        public readonly ?string $serviceJobId,
        public readonly string  $format = 'mp3',
        public readonly array   $raw    = [],
    ) {}

    public function isExternal(): bool
    {
        return str_starts_with($this->filePath, 'http');
    }
}
