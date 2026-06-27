<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsSnapshot extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = [];

    protected $dates = ['created_at'];

    protected function casts(): array
    {
        return [
            'snapshot_date'          => 'date',
            'click_through_rate'     => 'decimal:4',
            'engagement_rate'        => 'decimal:4',
            'created_at'             => 'datetime',
        ];
    }

    public static function boot(): void
    {
        parent::boot();

        static::creating(fn (AnalyticsSnapshot $model) => $model->created_at = now());

        static::updating(fn () => throw new \RuntimeException('AnalyticsSnapshot records are immutable.'));
    }

    public function socialPost(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
