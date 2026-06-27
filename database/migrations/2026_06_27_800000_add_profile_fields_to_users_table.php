<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // avatar_color is new — add it
            if (! Schema::hasColumn('users', 'avatar_color')) {
                $table->string('avatar_color', 7)->default('#B8743A')->after('email');
            }

            // bio may not exist yet
            if (! Schema::hasColumn('users', 'bio')) {
                $table->string('bio', 300)->nullable()->after('avatar_color');
            }

            // timezone already added by 2026_06_27_100001 — skip if present
            if (! Schema::hasColumn('users', 'timezone')) {
                $table->string('timezone', 60)->default('America/Mexico_City')->after('bio');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $columns = array_filter(
                ['avatar_color', 'bio'],
                fn($col) => Schema::hasColumn('users', $col)
            );

            if ($columns) {
                $table->dropColumn(array_values($columns));
            }
        });
    }
};
