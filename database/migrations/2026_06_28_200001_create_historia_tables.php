<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->default('Mi Historia');
            $table->text('logline')->nullable();
            $table->text('target_audience')->nullable();
            $table->string('status')->default('writing'); // writing | ready | published
            $table->timestamps();
        });

        Schema::create('book_ideas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->string('tag')->nullable(); // infancia, trabajo, amor, crisis, logro...
            $table->boolean('converted')->default(false); // ya se convirtió en capítulo
            $table->timestamps();
        });

        Schema::create('book_chapters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('episode_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->longText('content')->nullable();           // HTML del capítulo
            $table->text('draft_notes')->nullable();           // notas del autor
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('status')->default('idea');         // idea|draft|written|ready|published
            $table->string('seo_title')->nullable();           // título YouTube optimizado
            $table->json('market_intel')->nullable();          // análisis de mercado IA
            $table->json('interlinks')->nullable();            // capítulos referenciados
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('book_clues', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('chapter_id')->constrained('book_chapters')->cascadeOnDelete();
            $table->foreignId('shot_id')->nullable()->constrained()->nullOnDelete();
            $table->text('book_secret');       // qué revela el libro ("en ese jarrón estaba el dinero")
            $table->text('visual_element');    // qué aparece en el video ("un jarrón de cerámica azul en la repisa")
            $table->string('placement')->default('background'); // background|foreground|subtle
            $table->text('viewer_feeling')->nullable(); // qué siente el que NO leyó el libro
            $table->text('reader_payoff')->nullable();  // qué siente el que SÍ leyó el libro
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_clues');
        Schema::dropIfExists('book_chapters');
        Schema::dropIfExists('book_ideas');
        Schema::dropIfExists('books');
    }
};
