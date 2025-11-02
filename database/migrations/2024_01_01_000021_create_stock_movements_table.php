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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('company_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('product_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('warehouse_id')->constrained()->onDelete('cascade');

            // Movement details
            $table->enum('type', [
                'adjustment',    // Manual stock adjustment
                'purchase',      // Stock received from supplier
                'sale',          // Stock sold to customer
                'transfer_out',  // Stock transferred to another warehouse
                'transfer_in',   // Stock received from another warehouse
                'return',        // Customer return
                'damage',        // Damaged goods
                'loss',          // Stock loss/theft
                'production',    // Manufactured goods
                'initial'        // Initial stock entry
            ]);

            // Quantities
            $table->decimal('quantity_before', 10, 2);
            $table->decimal('quantity_change', 10, 2); // Can be negative
            $table->decimal('quantity_after', 10, 2);

            // Cost information
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('total_cost', 12, 2)->nullable();

            // Reference information
            $table->string('reference_type')->nullable(); // invoice, offer, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_number')->nullable(); // Document number

            // Transfer information (for warehouse transfers)
            $table->foreignUuid('from_warehouse_id')->nullable()->constrained('warehouses');
            $table->foreignUuid('to_warehouse_id')->nullable()->constrained('warehouses');

            // Audit information
            $table->foreignUuid('created_by')->constrained('users');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes for performance and reporting
            $table->index(['company_id', 'created_at']);
            $table->index(['product_id', 'created_at']);
            $table->index(['warehouse_id', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['created_by', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
