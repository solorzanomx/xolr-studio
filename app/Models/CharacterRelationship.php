<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterRelationship extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function characterA(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'character_a_id');
    }

    public function characterB(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'character_b_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
