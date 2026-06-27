<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RenderAnnotation extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'resolved'  => 'boolean',
            'x_percent' => 'decimal:2',
            'y_percent' => 'decimal:2',
        ];
    }

    public function render(): BelongsTo
    {
        return $this->belongsTo(Render::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
