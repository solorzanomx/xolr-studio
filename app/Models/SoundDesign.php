<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoundDesign extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'sfx_config' => 'array',
        ];
    }

    public function scene(): BelongsTo
    {
        return $this->belongsTo(Scene::class);
    }

    public function ambientAsset(): BelongsTo
    {
        return $this->belongsTo(AudioAsset::class, 'ambient_asset_id');
    }

    public function musicAsset(): BelongsTo
    {
        return $this->belongsTo(AudioAsset::class, 'music_asset_id');
    }
}
