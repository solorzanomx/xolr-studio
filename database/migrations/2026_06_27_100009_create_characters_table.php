<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('characters', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->enum('type', ['fictional', 'virtual_talent', 'mascot']);
            $table->text('description')->nullable();
            $table->text('physical_description')->nullable();
            $table->json('personality_traits')->nullable();
            $table->text('base_prompt')->nullable();
            $table->text('negative_prompt')->nullable();
            $table->string('lora_path', 500)->nullable();
            $table->string('lora_trigger_word', 100)->nullable();
            $table->decimal('lora_weight', 3, 2)->nullable();
            $table->timestamp('lora_trained_at')->nullable();
            $table->json('dna_config')->nullable();
            $table->string('thumbnail_path', 500)->nullable();
            $table->decimal('approval_rate', 5, 2)->nullable();
            $table->decimal('avg_renders_to_approve', 4, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('characters');
    }
};
