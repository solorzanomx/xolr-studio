<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shot_characters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('outfit_id')->nullable()->index();
            $table->unsignedBigInteger('character_version_id')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shot_characters');
    }
};
