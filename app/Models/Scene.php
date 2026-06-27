<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scene extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    public function shots(): HasMany
    {
        return $this->hasMany(Shot::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function soundDesigns(): HasMany
    {
        return $this->hasMany(SoundDesign::class);
    }
}
