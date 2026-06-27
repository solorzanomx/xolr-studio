<?php

declare(strict_types=1);

namespace App\Http\Controllers\Publishing;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Season;
use App\Services\ExportService;
use Illuminate\Contracts\View\View;

class PrintController extends Controller
{
    public function __construct(
        private readonly ExportService $service,
    ) {}

    public function bible(Project $project): View
    {
        $data = $this->service->getProductionBibleData($project);
        return view('exports.production-bible', $data);
    }

    public function book(Season $season): View
    {
        $data = $this->service->getBookData($season);
        return view('exports.book', $data);
    }
}
