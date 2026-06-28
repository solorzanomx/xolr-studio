<?php

declare(strict_types=1);

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateScriptJob;
use App\Models\Episode;
use App\Services\ScriptGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ScriptGeneratorController extends Controller
{
    public function __construct(
        private readonly ScriptGeneratorService $service,
    ) {}

    public function generateScript(Episode $episode): JsonResponse
    {
        $key = GenerateScriptJob::cacheKey($episode->id, 'script');
        $current = Cache::get($key);

        if (isset($current['status']) && $current['status'] === 'generating') {
            return response()->json(['ok' => true, 'queued' => true, 'status' => 'generating']);
        }

        Cache::put($key, ['status' => 'queued'], now()->addMinutes(10));
        GenerateScriptJob::dispatch($episode->id, 'script')->onQueue('default');

        return response()->json(['ok' => true, 'queued' => true, 'status' => 'queued']);
    }

    public function scriptStatus(Episode $episode): JsonResponse
    {
        $key  = GenerateScriptJob::cacheKey($episode->id, 'script');
        $data = Cache::get($key);

        if (! $data) {
            return response()->json(['status' => 'idle']);
        }

        return response()->json([
            'status' => $data['status'],
            'result' => $data['result'] ?? null,
            'error'  => $data['error'] ?? null,
        ]);
    }

    public function generateBookChapter(Episode $episode): JsonResponse
    {
        $key = GenerateScriptJob::cacheKey($episode->id, 'chapter');
        $current = Cache::get($key);

        if (isset($current['status']) && $current['status'] === 'generating') {
            return response()->json(['ok' => true, 'queued' => true, 'status' => 'generating']);
        }

        Cache::put($key, ['status' => 'queued'], now()->addMinutes(10));
        GenerateScriptJob::dispatch($episode->id, 'chapter')->onQueue('default');

        return response()->json(['ok' => true, 'queued' => true, 'status' => 'queued']);
    }

    public function chapterStatus(Episode $episode): JsonResponse
    {
        $key  = GenerateScriptJob::cacheKey($episode->id, 'chapter');
        $data = Cache::get($key);

        if (! $data) {
            return response()->json(['status' => 'idle']);
        }

        return response()->json([
            'status' => $data['status'],
            'result' => $data['result'] ?? null,
            'error'  => $data['error'] ?? null,
        ]);
    }

    public function checkContinuity(Episode $episode): JsonResponse
    {
        try {
            $result = $this->service->checkContinuity($episode);

            return response()->json(['ok' => true, 'result' => $result]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
