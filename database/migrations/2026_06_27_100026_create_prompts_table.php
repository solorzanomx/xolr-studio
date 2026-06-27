<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shot_id')->constrained()->cascadeOnDelete();
            $table->text('positive_prompt');
            $table->text('negative_prompt')->nullable();
            $table->text('composed_prompt')->nullable();
            $table->json('sources')->nullable();
            $table->tinyInteger('version')->unsigned()->default(1);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompts');
    }
};
