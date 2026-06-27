<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('period_year')->unsigned();
            $table->tinyInteger('period_month')->unsigned();
            $table->decimal('budget_usd', 10, 2);
            $table->decimal('spent_usd', 10, 2)->default(0);
            $table->decimal('render_cost_usd', 10, 2)->default(0);
            $table->decimal('audio_cost_usd', 10, 2)->default(0);
            $table->decimal('lipsync_cost_usd', 10, 2)->default(0);
            $table->decimal('api_cost_usd', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
