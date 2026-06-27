<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Character extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'personality_traits'      => 'array',
            'dna_config'              => 'array',
            'lora_weight'             => 'decimal:2',
            'approval_rate'           => 'decimal:2',
            'avg_renders_to_approve'  => 'decimal:2',
            'lora_trained_at'         => 'datetime',
            'is_active'               => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(CharacterVersion::class);
    }

    public function outfits(): HasMany
    {
        return $this->hasMany(Outfit::class);
    }

    public function virtualTalent(): HasOne
    {
        return $this->hasOne(VirtualTalent::class);
    }

    public function voiceProfiles(): HasMany
    {
        return $this->hasMany(VoiceProfile::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
