<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialPost extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'media_paths'   => 'array',
            'scheduled_for' => 'datetime',
            'published_at'  => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function calendarEvent(): BelongsTo
    {
        return $this->belongsTo(ContentCalendarEvent::class, 'calendar_event_id');
    }

    public function analyticsSnapshots(): HasMany
    {
        return $this->hasMany(AnalyticsSnapshot::class);
    }
}
