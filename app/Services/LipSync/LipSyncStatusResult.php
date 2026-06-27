<?php

declare(strict_types=1);

namespace App\Services\LipSync;

final class LipSyncStatusResult
{
    public function __construct(
        public readonly string  $status,        // queued | processing | completed | failed
        public readonly ?string $fileUrl,
        public readonly ?float  $costUsd,
        public readonly ?float  $durationSeconds,
        public readonly ?string $errorMessage,
        public readonly array   $raw = [],
    ) {}

    public function isCompleted(): bool { return $this->status === 'completed'; }
    public function isFailed(): bool    { return $this->status === 'failed'; }
    public function isTerminal(): bool  { return in_array($this->status, ['completed', 'failed'], true); }
}
