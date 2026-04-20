<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_invoice_items', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('recurring_profile_id');
            $table->foreign('recurring_profile_id', 'rii_profile_fk')
                ->references('id')->on('recurring_invoice_profiles')
                ->onDelete('cascade');

            $table->uuid('product_id')->nullable();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');

            $table->text('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->string('unit', 50)->default('Stk.');
            $table->decimal('tax_rate', 5, 4)->nullable();
            $table->string('discount_type', 20)->nullable();
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['recurring_profile_id', 'sort_order'], 'rii_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_invoice_items');
    }
};
