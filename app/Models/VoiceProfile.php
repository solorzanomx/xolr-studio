<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VoiceProfile extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_cloned'                => 'boolean',
            'is_default'               => 'boolean',
            'default_stability'        => 'decimal:2',
            'default_similarity_boost' => 'decimal:2',
            'default_style'            => 'decimal:2',
        ];
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function audioAssets(): HasMany
    {
        return $this->hasMany(AudioAsset::class);
    }
}
