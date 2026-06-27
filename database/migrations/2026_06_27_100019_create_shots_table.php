<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('scene_id')->nullable()->index();
            $table->unsignedBigInteger('campaign_id')->nullable()->index();
            $table->unsignedBigInteger('video_concept_id')->nullable()->index();
            $table->smallInteger('number')->unsigned();
            $table->text('description')->nullable();
            $table->enum('shot_type', ['image', 'video', 'talking'])->default('image');
            $table->enum('purpose', ['narrative', 'hero', 'carousel_frame', 'thumbnail', 'social', 'broker_portrait', 'property_hero', 'talking_dialogue'])->default('narrative');
            $table->unsignedBigInteger('format_preset_id')->nullable()->index();
            $table->unsignedBigInteger('camera_style_id')->nullable()->index();
            $table->unsignedBigInteger('visual_style_id')->nullable()->index();
            $table->text('dialogue_text')->nullable();
            $table->tinyInteger('duration_seconds')->unsigned()->nullable();
            $table->text('director_notes')->nullable();
            $table->enum('status', ['draft', 'prompt_ready', 'rendering', 'audio_pending', 'lip_sync_pending', 'completed', 'approved'])->default('draft');
            $table->unsignedBigInteger('approved_render_id')->nullable();
            $table->unsignedBigInteger('approved_talking_render_id')->nullable();
            $table->smallInteger('sort_order')->unsigned()->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shots');
    }
};
