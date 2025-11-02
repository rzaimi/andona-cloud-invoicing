<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('warehouse_stocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignUuid('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignUuid('product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('reserved_quantity', 10, 2)->default(0); // Reserved for orders
            $table->decimal('available_quantity', 10, 2)->storedAs('quantity - reserved_quantity');

            // Cost tracking
            $table->decimal('average_cost', 10, 2)->default(0);
            $table->decimal('total_value', 12, 2)->storedAs('quantity * average_cost');

            // Location within warehouse
            $table->string('location')->nullable(); // Shelf, bin, etc.

            // Stock level management
            $table->decimal('min_stock_level', 10, 2)->nullable();
            $table->decimal('max_stock_level', 10, 2)->nullable();
            $table->decimal('reorder_point', 10, 2)->nullable();

            // Physical count tracking
            $table->decimal('physical_count', 10, 2)->nullable();
            $table->timestamp('last_counted_at')->nullable();
            $table->foreignUuid('last_counted_by')->nullable()->constrained('users');

            $table->timestamps();

            // Unique constraint - one stock record per product per warehouse
            $table->unique(['warehouse_id', 'product_id']);

            // Indexes for performance
            $table->index(['company_id']);
            $table->index(['warehouse_id', 'quantity']);
            $table->index(['product_id', 'quantity']);
            $table->index(['warehouse_id', 'available_quantity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_stocks');
    }
};
