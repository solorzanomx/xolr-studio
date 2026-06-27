<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('render_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->text('description')->nullable();
            $table->json('workflow_json')->nullable();
            $table->string('base_model', 255)->nullable();
            $table->boolean('supports_lora')->default(true);
            $table->boolean('supports_pulid')->default(false);
            $table->tinyInteger('default_steps')->unsigned()->default(28);
            $table->decimal('default_cfg', 3, 1)->default(7.0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('render_templates');
    }
};
