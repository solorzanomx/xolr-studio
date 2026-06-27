<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_relationships', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('project_id')->index();
            $table->unsignedBigInteger('character_a_id');
            $table->unsignedBigInteger('character_b_id');
            $table->string('relationship_type', 100);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_relationships');
    }
};
