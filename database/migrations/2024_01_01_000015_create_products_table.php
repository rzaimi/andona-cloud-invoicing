<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->string('number')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit')->default('Stk.');
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->string('category')->nullable();
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->decimal('tax_rate', 5, 4)->default(0.19);
            $table->integer('stock_quantity')->default(0);
            $table->integer('min_stock_level')->default(0);
            $table->boolean('track_stock')->default(false);
            $table->boolean('is_service')->default(false);
            $table->enum('status', ['active', 'inactive', 'discontinued'])->default('active');
            $table->json('custom_fields')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'category']);
            $table->index(['number', 'company_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
};
