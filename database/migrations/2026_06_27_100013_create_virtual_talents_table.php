<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virtual_talents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('title', 100);
            $table->json('specialties')->nullable();
            $table->text('bio_short')->nullable();
            $table->text('bio_full')->nullable();
            $table->text('communication_style')->nullable();
            $table->string('signature_phrase', 255)->nullable();
            $table->string('social_handle', 100)->nullable();
            $table->json('brand_colors')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_talents');
    }
};
