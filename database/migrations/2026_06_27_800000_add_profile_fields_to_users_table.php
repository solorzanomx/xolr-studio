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
            $table->string('avatar_color', 7)->default('#B8743A')->after('email');
            $table->string('bio', 300)->nullable()->after('avatar_color');
            $table->string('timezone', 60)->default('America/Mexico_City')->after('bio');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['avatar_color', 'bio', 'timezone']);
        });
    }
};
