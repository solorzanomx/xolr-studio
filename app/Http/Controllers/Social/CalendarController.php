<?php

declare(strict_types=1);

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Jobs\PublishSocialPostJob;
use App\Models\ContentCalendarEvent;
use App\Models\Project;
use App\Models\SocialPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function index(Request $request): Response
    {
        $month   = (int) $request->get('month', now()->month);
        $year    = (int) $request->get('year', now()->year);
        $date    = Carbon::createFromDate($year, $month, 1);
        $project = $request->project_id
            ? Project::find($request->project_id)
            : Project::first();

        $query = SocialPost::with('calendarEvent')
            ->whereBetween('scheduled_for', [
                $date->copy()->startOfMonth(),
                $date->copy()->endOfMonth(),
            ]);

        if ($project) {
            $query->where('project_id', $project->id);
        }

        $posts = $query->orderBy('scheduled_for')->get()->groupBy(fn($p) => $p->scheduled_for->format('Y-m-d'));

        $projects = Project::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Calendar/Index', [
            'posts'        => $posts,
            'month'        => $month,
            'year'         => $year,
            'projectId'    => $project?->id,
            'projects'     => $projects,
            'daysInMonth'  => $date->daysInMonth,
            'firstDayDow'  => $date->copy()->startOfMonth()->dayOfWeek, // 0=Sun
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_id'    => 'required|exists:projects,id',
            'platform'      => 'required|in:instagram,youtube,facebook,tiktok,linkedin',
            'post_type'     => 'required|in:post,carousel,story,reel,video,short',
            'caption'       => 'nullable|string|max:2200',
            'hashtags'      => 'nullable|string',
            'scheduled_for' => 'required|date|after:now',
            'media_paths'   => 'nullable|array',
        ]);

        SocialPost::create(array_merge($data, ['status' => 'scheduled']));

        return back()->with('success', 'Post programado.');
    }

    public function update(Request $request, SocialPost $socialPost): RedirectResponse
    {
        $data = $request->validate([
            'caption'       => 'nullable|string|max:2200',
            'hashtags'      => 'nullable|string',
            'scheduled_for' => 'required|date',
            'status'        => 'required|in:draft,scheduled,published,failed',
        ]);

        $socialPost->update($data);

        return back()->with('success', 'Post actualizado.');
    }

    public function destroy(SocialPost $socialPost): RedirectResponse
    {
        $socialPost->delete();

        return back()->with('success', 'Post eliminado.');
    }

    public function publishNow(SocialPost $socialPost): RedirectResponse
    {
        if ($socialPost->status === 'published') {
            return back()->withErrors(['post' => 'Este post ya fue publicado.']);
        }

        PublishSocialPostJob::dispatch($socialPost->id)->onQueue('default');

        return back()->with('success', 'Publicando en ' . ucfirst($socialPost->platform) . '...');
    }
}
