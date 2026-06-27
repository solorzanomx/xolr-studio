<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterVersion extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'lora_weight' => 'decimal:2',
            'trained_at'  => 'datetime',
            'is_current'  => 'boolean',
        ];
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
