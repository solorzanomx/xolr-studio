<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Render extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_approved'    => 'boolean',
            'metadata'       => 'array',
            'gpu_cost_usd'   => 'decimal:6',
        ];
    }

    public function shot(): BelongsTo
    {
        return $this->belongsTo(Shot::class);
    }

    public function prompt(): BelongsTo
    {
        return $this->belongsTo(Prompt::class);
    }

    public function annotations(): HasMany
    {
        return $this->hasMany(RenderAnnotation::class);
    }

    public function formatPreset(): BelongsTo
    {
        return $this->belongsTo(FormatPreset::class);
    }

    public function talkingRenders(): HasMany
    {
        return $this->hasMany(TalkingRender::class, 'source_render_id');
    }
}
