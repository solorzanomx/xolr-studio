<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sound_designs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('scene_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->unsignedBigInteger('ambient_asset_id')->nullable()->index();
            $table->unsignedBigInteger('music_asset_id')->nullable()->index();
            $table->tinyInteger('dialogue_volume')->unsigned()->default(100);
            $table->tinyInteger('ambient_volume')->unsigned()->default(35);
            $table->tinyInteger('music_volume')->unsigned()->default(55);
            $table->json('sfx_config')->nullable();
            $table->string('export_path', 500)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sound_designs');
    }
};
