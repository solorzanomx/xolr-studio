<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('universe_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->enum('category', ['lore', 'rules', 'geography', 'timeline', 'factions', 'other'])->default('lore');
            $table->string('title', 255);
            $table->longText('content');
            $table->smallInteger('sort_order')->unsigned()->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('universe_notes');
    }
};
