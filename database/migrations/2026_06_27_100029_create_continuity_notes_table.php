<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('continuity_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('notable_type', 255);
            $table->unsignedBigInteger('notable_id');
            $table->string('title', 255);
            $table->text('content');
            $table->enum('type', ['character', 'location', 'prop', 'lighting', 'timeline', 'other'])->default('other');
            $table->boolean('is_resolved')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['notable_type', 'notable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('continuity_notes');
    }
};
