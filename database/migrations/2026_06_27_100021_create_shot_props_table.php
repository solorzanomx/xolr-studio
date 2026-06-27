<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shot_props', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prop_id')->constrained()->cascadeOnDelete();
            $table->text('notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shot_props');
    }
};
