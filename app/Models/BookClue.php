<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookClue extends Model
{
    protected $guarded = [];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(BookChapter::class, 'chapter_id');
    }

    public function shot(): BelongsTo
    {
        return $this->belongsTo(Shot::class);
    }
}
