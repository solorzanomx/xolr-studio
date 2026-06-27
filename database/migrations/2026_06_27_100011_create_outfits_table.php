<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outfits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->text('prompt_fragment')->nullable();
            $table->enum('context', ['formal', 'casual', 'action', 'historical', 'fantasy', 'uniform', 'other'])->default('casual');
            $table->string('thumbnail_path', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outfits');
    }
};
