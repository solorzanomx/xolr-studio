<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'token_expires_at'   => 'datetime',
            'last_accessed_at'   => 'datetime',
            'can_download'       => 'boolean',
            'can_comment'        => 'boolean',
            'can_approve'        => 'boolean',
            'is_active'          => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(ClientQuote::class);
    }

    public function annotations(): HasMany
    {
        return $this->hasMany(RenderAnnotation::class);
    }
}
