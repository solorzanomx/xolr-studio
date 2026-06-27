<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talking_renders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_render_id')->constrained('renders')->cascadeOnDelete();
            $table->foreignId('audio_asset_id')->constrained('audio_assets')->cascadeOnDelete();
            $table->enum('quality', ['draft', 'production', 'premium'])->default('production');
            $table->enum('service', ['did', 'heygen', 'runpod_wav2lip', 'runpod_latentsync'])->default('did');
            $table->string('service_job_id', 255)->nullable();
            $table->enum('status', ['queued', 'processing', 'completed', 'failed'])->default('queued');
            $table->string('file_path', 500)->nullable();
            $table->decimal('duration_seconds', 8, 2)->nullable();
            $table->smallInteger('width')->unsigned()->nullable();
            $table->smallInteger('height')->unsigned()->nullable();
            $table->decimal('service_cost_usd', 8, 6)->nullable();
            $table->boolean('is_approved')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talking_renders');
    }
};
