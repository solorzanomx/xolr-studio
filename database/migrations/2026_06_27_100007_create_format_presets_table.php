<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('format_presets', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->smallInteger('width')->unsigned();
            $table->smallInteger('height')->unsigned();
            $table->string('aspect_ratio', 10);
            $table->enum('platform', ['instagram', 'youtube', 'general', 'print'])->default('general');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('format_presets');
    }
};
