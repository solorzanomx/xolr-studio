<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_calendar_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title', 255);
            $table->enum('type', ['instagram_post', 'instagram_carousel', 'instagram_story', 'instagram_reel', 'youtube_video', 'youtube_short'])->default('instagram_post');
            $table->text('description')->nullable();
            $table->dateTime('scheduled_for');
            $table->enum('status', ['draft', 'ready', 'scheduled', 'published', 'failed'])->default('draft');
            $table->json('asset_ids')->nullable();
            $table->text('caption')->nullable();
            $table->text('hashtags')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_calendar_events');
    }
};
