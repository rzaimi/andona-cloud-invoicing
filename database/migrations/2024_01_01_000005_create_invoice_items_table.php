<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('invoice_id');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->string('description');
            $table->decimal('quantity', 8, 2);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total', 10, 2);
            $table->string('unit')->default('Stk.');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['invoice_id', 'sort_order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('invoice_items');
    }
};
