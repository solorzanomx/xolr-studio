<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'brand_colors'         => 'array',
            'brand_fonts'          => 'array',
            'settings'             => 'array',
            'notion_sync_enabled'  => 'boolean',
            'monthly_budget_usd'   => 'decimal:2',
        ];
    }

    public function seasons(): HasMany
    {
        return $this->hasMany(Season::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function videoConcepts(): HasMany
    {
        return $this->hasMany(VideoConcept::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function socialPosts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(ContentCalendarEvent::class);
    }

    public function exports(): HasMany
    {
        return $this->hasMany(Export::class);
    }

    public function universeNotes(): HasMany
    {
        return $this->hasMany(UniverseNote::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
