<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scenes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('episode_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('number')->unsigned();
            $table->string('title', 255)->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->enum('time_of_day', ['morning', 'day', 'golden_hour', 'night', 'unspecified'])->default('unspecified');
            $table->enum('mood', ['tense', 'action', 'dramatic', 'calm', 'mysterious', 'romantic', 'comedic', 'horror', 'other'])->default('calm');
            $table->smallInteger('sort_order')->unsigned()->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scenes');
    }
};
