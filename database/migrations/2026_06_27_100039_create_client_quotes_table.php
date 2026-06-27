<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_quotes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('project_id')->nullable()->index();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->string('quote_number', 50)->unique();
            $table->json('items');
            $table->decimal('render_cost_estimate', 10, 2)->nullable();
            $table->decimal('production_fee', 10, 2)->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_rate', 4, 2)->nullable();
            $table->decimal('tax_amount', 10, 2)->nullable();
            $table->decimal('total', 10, 2);
            $table->char('currency', 3)->default('MXN');
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'invoiced'])->default('draft');
            $table->date('valid_until')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_quotes');
    }
};
