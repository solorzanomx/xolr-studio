<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIDirectorResult extends Model
{
    protected $guarded = [];

    protected $table = 'ai_director_results';

    protected function casts(): array
    {
        return [
            'proposed_structure'    => 'array',
            'ghost_profile_snapshot' => 'array',
            'applied_at'            => 'datetime',
        ];
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
