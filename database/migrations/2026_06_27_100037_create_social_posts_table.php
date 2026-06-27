<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('calendar_event_id')->nullable()->index();
            $table->enum('platform', ['instagram', 'youtube', 'facebook', 'tiktok', 'linkedin'])->default('instagram');
            $table->enum('post_type', ['post', 'carousel', 'story', 'reel', 'video', 'short'])->default('post');
            $table->text('caption')->nullable();
            $table->text('hashtags')->nullable();
            $table->json('media_paths')->nullable();
            $table->string('thumbnail_path', 500)->nullable();
            $table->dateTime('scheduled_for');
            $table->enum('status', ['draft', 'scheduled', 'publishing', 'published', 'failed'])->default('draft');
            $table->string('platform_post_id', 255)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_posts');
    }
};
