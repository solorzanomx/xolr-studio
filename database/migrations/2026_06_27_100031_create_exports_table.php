<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['production_packet', 'animatic', 'pdf_book', 'zip_campaign', 'csv'])->default('zip_campaign');
            $table->string('file_path', 500)->nullable();
            $table->enum('status', ['pending', 'generating', 'completed', 'failed'])->default('pending');
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
