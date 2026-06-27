<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('episodes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('number')->unsigned();
            $table->string('title', 255);
            $table->text('logline')->nullable();
            $table->text('synopsis')->nullable();
            $table->longText('script')->nullable();
            $table->enum('status', ['concept', 'outline', 'scripted', 'production', 'completed', 'published'])->default('concept');
            $table->timestamp('published_at')->nullable();
            $table->string('youtube_video_id', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
