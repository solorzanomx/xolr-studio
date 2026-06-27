<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VirtualTalent extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'specialties'  => 'array',
            'brand_colors' => 'array',
            'is_public'    => 'boolean',
        ];
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
