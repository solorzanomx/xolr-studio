<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Book;
use App\Models\BookChapter;
use App\Services\HistoriaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Throwable;

class GenerateHistoriaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 300;

    public function __construct(
        private readonly int    $modelId,
        private readonly string $type, // organize | expand | market
    ) {}

    public function handle(HistoriaService $service): void
    {
        $key = $this->cacheKey();
        Cache::put($key, ['status' => 'generating'], now()->addMinutes(10));

        $result = match ($this->type) {
            'organize' => $this->runOrganize($service),
            'expand'   => $this->runExpand($service),
            'market'   => $this->runMarket($service),
            default    => throw new \RuntimeException("Tipo desconocido: {$this->type}"),
        };

        Cache::put($key, ['status' => 'completed', 'result' => $result], now()->addMinutes(5));
    }

    public function failed(Throwable $e): void
    {
        Cache::put($this->cacheKey(), [
            'status' => 'failed',
            'error'  => $e->getMessage(),
        ], now()->addMinutes(5));
    }

    private function runOrganize(HistoriaService $service): array
    {
        $book = Book::findOrFail($this->modelId);
        return $service->organizeIdeas($book);
    }

    private function runExpand(HistoriaService $service): string
    {
        $chapter = BookChapter::findOrFail($this->modelId);
        $html    = $service->expandChapter($chapter);
        $chapter->update(['content' => $html, 'status' => 'draft']);
        return $html;
    }

    private function runMarket(HistoriaService $service): array
    {
        $chapter = BookChapter::findOrFail($this->modelId);
        $intel   = $service->analyzeMarket($chapter);
        $chapter->update(['market_intel' => $intel]);
        return $intel;
    }

    private function cacheKey(): string
    {
        return "historia_{$this->type}_{$this->modelId}";
    }
}
