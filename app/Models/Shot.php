<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shot extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function scene(): BelongsTo
    {
        return $this->belongsTo(Scene::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function videoConcept(): BelongsTo
    {
        return $this->belongsTo(VideoConcept::class);
    }

    public function characters(): BelongsToMany
    {
        return $this->belongsToMany(Character::class, 'shot_characters')
            ->withPivot('outfit_id', 'character_version_id', 'notes')
            ->withTimestamps();
    }

    public function props(): BelongsToMany
    {
        return $this->belongsToMany(Prop::class, 'shot_props')
            ->withPivot('notes');
    }

    public function prompt(): HasOne
    {
        return $this->hasOne(Prompt::class)->where('is_active', true)->latest();
    }

    public function prompts(): HasMany
    {
        return $this->hasMany(Prompt::class)->orderByDesc('version');
    }

    public function renders(): HasMany
    {
        return $this->hasMany(Render::class);
    }

    public function talkingRenders(): HasMany
    {
        return $this->hasMany(TalkingRender::class);
    }

    public function approvedRender(): BelongsTo
    {
        return $this->belongsTo(Render::class, 'approved_render_id');
    }

    public function approvedTalkingRender(): BelongsTo
    {
        return $this->belongsTo(TalkingRender::class, 'approved_talking_render_id');
    }

    public function formatPreset(): BelongsTo
    {
        return $this->belongsTo(FormatPreset::class);
    }

    public function cameraStyle(): BelongsTo
    {
        return $this->belongsTo(CameraStyle::class);
    }

    public function visualStyle(): BelongsTo
    {
        return $this->belongsTo(VisualStyle::class);
    }
}
