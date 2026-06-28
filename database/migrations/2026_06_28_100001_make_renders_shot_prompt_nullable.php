<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('renders', function (Blueprint $table): void {
            $table->dropForeign(['shot_id']);
            $table->dropForeign(['prompt_id']);

            $table->unsignedBigInteger('shot_id')->nullable()->change();
            $table->unsignedBigInteger('prompt_id')->nullable()->change();

            $table->foreign('shot_id')->references('id')->on('shots')->nullOnDelete();
            $table->foreign('prompt_id')->references('id')->on('prompts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('renders', function (Blueprint $table): void {
            $table->dropForeign(['shot_id']);
            $table->dropForeign(['prompt_id']);

            $table->unsignedBigInteger('shot_id')->nullable(false)->change();
            $table->unsignedBigInteger('prompt_id')->nullable(false)->change();

            $table->foreign('shot_id')->references('id')->on('shots')->cascadeOnDelete();
            $table->foreign('prompt_id')->references('id')->on('prompts')->cascadeOnDelete();
        });
    }
};
