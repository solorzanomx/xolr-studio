<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('version_number')->unsigned();
            $table->string('label', 100)->nullable();
            $table->string('lora_path', 500)->nullable();
            $table->string('lora_trigger_word', 100)->nullable();
            $table->decimal('lora_weight', 3, 2)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamp('trained_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_versions');
    }
};
