<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('property_id')->nullable()->index();
            $table->string('name', 255);
            $table->string('slug', 255);
            $table->enum('type', ['real_estate', 'product', 'brand', 'event', 'youtube', 'social'])->default('social');
            $table->text('description')->nullable();
            $table->json('asset_checklist')->nullable();
            $table->enum('status', ['planning', 'production', 'review', 'completed', 'archived'])->default('planning');
            $table->date('deadline')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
