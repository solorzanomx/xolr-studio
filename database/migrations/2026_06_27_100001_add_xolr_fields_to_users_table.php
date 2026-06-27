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
            $table->string('avatar_path', 500)->nullable()->after('email_verified_at');
            $table->string('timezone', 50)->default('America/Mexico_City')->after('avatar_path');
            $table->json('preferences')->nullable()->after('timezone');
            $table->json('ghost_director_profile')->nullable()->after('preferences');
            $table->timestamp('last_active_at')->nullable()->after('ghost_director_profile');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['avatar_path', 'timezone', 'preferences', 'ghost_director_profile', 'last_active_at']);
        });
    }
};
