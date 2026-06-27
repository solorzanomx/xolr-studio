<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('render_insights', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('insight_type', 100);
            $table->json('data');
            $table->timestamp('computed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('render_insights');
    }
};
