<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seasons', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('number')->unsigned();
            $table->string('title', 255)->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['planned', 'writing', 'production', 'completed'])->default('planned');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
