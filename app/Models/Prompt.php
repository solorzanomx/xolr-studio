<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prompt extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'sources'   => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function shot(): BelongsTo
    {
        return $this->belongsTo(Shot::class);
    }

    public function renders(): HasMany
    {
        return $this->hasMany(Render::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
