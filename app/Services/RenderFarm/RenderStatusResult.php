<?php

declare(strict_types=1);

namespace App\Services\RenderFarm;

final class RenderStatusResult
{
    public function __construct(
        public readonly string $status,       // queued | processing | completed | failed
        public readonly ?string $fileUrl,     // URL pública cuando status=completed
        public readonly ?float $costUsd,      // costo reportado por el provider
        public readonly ?string $errorMessage,
        public readonly array $raw = [],      // payload crudo del provider
    ) {}

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'cancelled'], true);
    }
}
