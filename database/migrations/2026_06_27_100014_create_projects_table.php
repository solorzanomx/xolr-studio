<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->enum('type', ['fiction_series', 'youtube_channel', 'real_estate', 'commercial', 'corporate', 'social_media', 'client']);
            $table->text('description')->nullable();
            $table->text('synopsis')->nullable();
            $table->unsignedBigInteger('visual_style_id')->nullable()->index();
            $table->unsignedBigInteger('default_format_preset_id')->nullable()->index();
            $table->enum('status', ['development', 'pre_production', 'production', 'post_production', 'completed', 'archived'])->default('development');
            $table->json('brand_colors')->nullable();
            $table->json('brand_fonts')->nullable();
            $table->string('thumbnail_path', 500)->nullable();
            $table->string('cover_path', 500)->nullable();
            $table->decimal('monthly_budget_usd', 10, 2)->nullable();
            $table->string('notion_database_id', 100)->nullable();
            $table->boolean('notion_sync_enabled')->default(false);
            $table->json('settings')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
