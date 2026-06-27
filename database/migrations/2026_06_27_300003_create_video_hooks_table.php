<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_hooks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('project_id')->nullable()->index();
            $table->enum('category', ['curiosity', 'shock', 'question', 'challenge', 'story', 'data', 'other'])->default('other');
            $table->text('content');
            $table->tinyInteger('rating')->unsigned()->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_hooks');
    }
};
