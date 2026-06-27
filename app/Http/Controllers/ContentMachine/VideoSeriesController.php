<?php

declare(strict_types=1);

namespace App\Http\Controllers\ContentMachine;

use App\Http\Controllers\Controller;
use App\Models\VideoSeries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VideoSeriesController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_id'  => 'required|exists:projects,id',
            'name'        => 'required|string|max:150',
            'description' => 'nullable|string',
        ]);

        VideoSeries::create($data);

        return back()->with('success', 'Serie creada.');
    }

    public function update(Request $request, VideoSeries $series): RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:150',
            'description' => 'nullable|string',
        ]);

        $series->update($data);

        return back()->with('success', 'Serie actualizada.');
    }

    public function destroy(VideoSeries $series): RedirectResponse
    {
        $series->concepts()->update(['video_series_id' => null]);
        $series->delete();

        return back()->with('success', 'Serie eliminada.');
    }
}
