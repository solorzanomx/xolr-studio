<?php

use App\Jobs\PublishSocialPostJob;
use App\Jobs\SyncAnalyticsJob;
use App\Jobs\SyncNotionJob;
use App\Models\Project;
use App\Models\SocialPost;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Publicar posts programados — cada 5 minutos
Schedule::call(function (): void {
    SocialPost::where('status', 'scheduled')
        ->where('scheduled_for', '<=', now())
        ->chunk(10, function ($posts): void {
            foreach ($posts as $post) {
                PublishSocialPostJob::dispatch($post->id)->onQueue('default');
            }
        });
})->everyFiveMinutes()->name('check-scheduled-posts');

// Sincronizar analytics — diario a las 3 AM
Schedule::call(function (): void {
    SyncAnalyticsJob::dispatch(null)->onQueue('default');
})->dailyAt('03:00')->name('sync-analytics');

// Sincronizar Notion — cada hora para proyectos con notion activado
Schedule::call(function (): void {
    Project::whereJsonContainsKey('settings->notion_episodes_db')
        ->get()
        ->each(fn($p) => SyncNotionJob::dispatch($p->id)->onQueue('default'));
})->hourly()->name('sync-notion');
