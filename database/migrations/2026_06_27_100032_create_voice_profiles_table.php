<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voice_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('elevenlabs_voice_id', 100);
            $table->boolean('is_cloned')->default(false);
            $table->string('language', 10)->default('es');
            $table->decimal('default_stability', 3, 2)->default(0.50);
            $table->decimal('default_similarity_boost', 3, 2)->default(0.75);
            $table->decimal('default_style', 3, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_profiles');
    }
};
