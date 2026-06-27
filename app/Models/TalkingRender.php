<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TalkingRender extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_approved'       => 'boolean',
            'service_cost_usd'  => 'decimal:6',
        ];
    }

    public function shot(): BelongsTo
    {
        return $this->belongsTo(Shot::class);
    }

    public function sourceRender(): BelongsTo
    {
        return $this->belongsTo(Render::class, 'source_render_id');
    }

    public function audioAsset(): BelongsTo
    {
        return $this->belongsTo(AudioAsset::class);
    }
}
