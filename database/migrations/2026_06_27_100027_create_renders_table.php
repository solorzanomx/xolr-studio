<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('renders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prompt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shot_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['queued', 'processing', 'completed', 'failed', 'cancelled'])->default('queued');
            $table->enum('quality_tier', ['draft', 'standard', 'final'])->default('draft');
            $table->enum('gpu_service', ['runpod', 'replicate', 'local', 'other'])->default('runpod');
            $table->string('job_id', 255)->nullable();
            $table->bigInteger('seed')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->enum('file_type', ['image', 'video'])->default('image');
            $table->unsignedBigInteger('format_preset_id')->nullable()->index();
            $table->smallInteger('width')->unsigned()->nullable();
            $table->smallInteger('height')->unsigned()->nullable();
            $table->integer('duration_ms')->unsigned()->nullable();
            $table->decimal('gpu_cost_usd', 8, 6)->nullable();
            $table->tinyInteger('user_rating')->unsigned()->nullable();
            $table->boolean('is_approved')->default(false);
            $table->text('error_message')->nullable();
            $table->tinyInteger('retry_count')->unsigned()->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('renders');
    }
};
