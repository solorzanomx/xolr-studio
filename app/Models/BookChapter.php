<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookChapter extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'market_intel' => 'array',
        'interlinks'   => 'array',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    public function clues(): HasMany
    {
        return $this->hasMany(BookClue::class, 'chapter_id');
    }
}
