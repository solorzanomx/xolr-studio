<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->enum('type', ['apartment', 'house', 'penthouse', 'commercial', 'land', 'development'])->default('apartment');
            $table->string('location_description', 255)->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->char('currency', 3)->default('MXN');
            $table->tinyInteger('bedrooms')->unsigned()->nullable();
            $table->decimal('bathrooms', 3, 1)->nullable();
            $table->decimal('area_sqm', 8, 2)->nullable();
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->enum('status', ['available', 'sold', 'rented', 'off_market'])->default('available');
            $table->string('thumbnail_path', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
