<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audio_assets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('project_id')->nullable()->index();
            $table->string('name', 255);
            $table->enum('type', ['voice_over', 'dialogue', 'ambient', 'sfx', 'music', 'mixed'])->default('voice_over');
            $table->string('file_path', 500)->nullable();
            $table->enum('file_format', ['mp3', 'wav', 'ogg'])->default('mp3');
            $table->decimal('duration_seconds', 8, 2)->nullable();
            $table->text('transcript')->nullable();
            $table->text('prompt_used')->nullable();
            $table->unsignedBigInteger('voice_profile_id')->nullable()->index();
            $table->enum('service', ['elevenlabs', 'suno', 'musicgen', 'audiogen', 'runpod', 'uploaded'])->default('elevenlabs');
            $table->string('service_job_id', 255)->nullable();
            $table->decimal('generation_cost_usd', 8, 6)->nullable();
            $table->enum('status', ['pending', 'generating', 'completed', 'failed'])->default('pending');
            $table->string('audioable_type', 255)->nullable();
            $table->unsignedBigInteger('audioable_id')->nullable();
            $table->index(['audioable_type', 'audioable_id']);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audio_assets');
    }
};
