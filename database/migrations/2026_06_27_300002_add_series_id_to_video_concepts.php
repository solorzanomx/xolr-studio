<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_concepts', function (Blueprint $table): void {
            $table->unsignedBigInteger('video_series_id')->nullable()->after('project_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('video_concepts', function (Blueprint $table): void {
            $table->dropColumn('video_series_id');
        });
    }
};
