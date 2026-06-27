<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('render_annotations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('render_id')->constrained()->cascadeOnDelete();
            $table->decimal('x_percent', 5, 2);
            $table->decimal('y_percent', 5, 2);
            $table->text('content');
            $table->enum('type', ['issue', 'suggestion', 'approval', 'question'])->default('issue');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->boolean('resolved')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('render_annotations');
    }
};
