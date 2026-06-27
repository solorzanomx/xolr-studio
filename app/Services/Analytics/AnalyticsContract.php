<?php

declare(strict_types=1);

namespace App\Services\Analytics;

interface AnalyticsContract
{
    public function fetchMetrics(string $platformPostId): array;
    public function platform(): string;
}
