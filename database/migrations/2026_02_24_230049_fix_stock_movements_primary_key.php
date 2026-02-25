<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The original stock_movements migration used $table->id() (BIGINT auto-increment)
 * but the StockMovement model uses HasUuids. This migration recreates the table
 * with a proper UUID primary key so the schema matches the model in all databases.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('stock_movements');

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('product_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('warehouse_id')->constrained()->onDelete('cascade');

            $table->enum('type', [
                'adjustment',
                'purchase',
                'sale',
                'transfer_out',
                'transfer_in',
                'return',
                'damage',
                'loss',
                'production',
                'initial',
            ]);

            $table->decimal('quantity_before', 10, 2)->default(0);
            $table->decimal('quantity_change', 10, 2);
            $table->decimal('quantity_after', 10, 2)->default(0);

            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('total_cost', 12, 2)->nullable();

            $table->string('reference_type')->nullable();
            $table->string('reference_number')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->foreignUuid('from_warehouse_id')->nullable()->constrained('warehouses');
            $table->foreignUuid('to_warehouse_id')->nullable()->constrained('warehouses');

            $table->foreignUuid('created_by')->constrained('users');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'created_at']);
            $table->index(['product_id', 'created_at']);
            $table->index(['warehouse_id', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['created_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
