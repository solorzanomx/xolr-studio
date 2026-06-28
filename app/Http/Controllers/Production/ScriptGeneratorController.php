<?php

declare(strict_types=1);

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Services\ScriptGeneratorService;
use Illuminate\Http\JsonResponse;

class ScriptGeneratorController extends Controller
{
    public function __construct(
        private readonly ScriptGeneratorService $service,
    ) {}

    public function generateScript(Episode $episode): JsonResponse
    {
        try {
            $script = $this->service->generateScript($episode);
            $episode->update(['script' => $script]);

            return response()->json(['ok' => true, 'script' => $script]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function generateBookChapter(Episode $episode): JsonResponse
    {
        try {
            $chapter = $this->service->generateBookChapter($episode);

            return response()->json(['ok' => true, 'chapter' => $chapter]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
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
