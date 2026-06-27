<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->enum('type', ['interior', 'exterior', 'mixed']);
            $table->text('description')->nullable();
            $table->text('base_prompt')->nullable();
            $table->json('lighting_by_time')->nullable();
            $table->string('thumbnail_path', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
