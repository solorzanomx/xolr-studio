<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RenderTemplate extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'workflow_json'   => 'array',
            'supports_lora'   => 'boolean',
            'supports_pulid'  => 'boolean',
            'is_active'       => 'boolean',
            'default_cfg'     => 'decimal:1',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
