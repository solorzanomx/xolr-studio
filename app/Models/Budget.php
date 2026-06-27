<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'budget_usd'      => 'decimal:2',
            'spent_usd'       => 'decimal:2',
            'render_cost_usd' => 'decimal:2',
            'audio_cost_usd'  => 'decimal:2',
            'lipsync_cost_usd'=> 'decimal:2',
            'api_cost_usd'    => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
