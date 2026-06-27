<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('social_post_id')->nullable()->index();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->enum('platform', ['instagram', 'youtube', 'facebook'])->default('youtube');
            $table->string('platform_post_id', 255);
            $table->date('snapshot_date');
            $table->integer('views')->unsigned()->nullable();
            $table->integer('reach')->unsigned()->nullable();
            $table->integer('likes')->unsigned()->nullable();
            $table->integer('comments')->unsigned()->nullable();
            $table->integer('shares')->unsigned()->nullable();
            $table->integer('saves')->unsigned()->nullable();
            $table->decimal('click_through_rate', 5, 4)->nullable();
            $table->integer('avg_watch_time_seconds')->unsigned()->nullable();
            $table->decimal('engagement_rate', 5, 4)->nullable();
            $table->integer('subscribers_gained')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['platform', 'platform_post_id', 'snapshot_date'], 'analytics_platform_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_snapshots');
    }
};
