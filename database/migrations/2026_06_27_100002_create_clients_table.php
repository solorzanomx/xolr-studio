<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('project_id')->nullable()->index();
            $table->string('name', 150);
            $table->string('email', 255);
            $table->string('company', 150)->nullable();
            $table->string('access_token', 64)->unique();
            $table->timestamp('token_expires_at')->nullable();
            $table->boolean('can_download')->default(false);
            $table->boolean('can_comment')->default(true);
            $table->boolean('can_approve')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
