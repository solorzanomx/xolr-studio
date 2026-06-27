<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_concepts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('hook')->nullable();
            $table->json('structure')->nullable();
            $table->longText('script')->nullable();
            $table->enum('status', ['idea', 'scripted', 'production', 'published'])->default('idea');
            $table->json('youtube_seo')->nullable();
            $table->tinyInteger('rating')->unsigned()->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('youtube_video_id', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_concepts');
    }
};
