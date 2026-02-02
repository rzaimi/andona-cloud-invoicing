<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add discount fields to invoice_items
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->enum('discount_type', ['percentage', 'fixed'])->nullable()->after('tax_rate');
            $table->decimal('discount_value', 10, 2)->nullable()->after('discount_type');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('discount_value');
        });

        // Add discount fields to offer_items
        Schema::table('offer_items', function (Blueprint $table) {
            // Note: Not using after() because tax_rate is added in a later migration
            $table->enum('discount_type', ['percentage', 'fixed'])->nullable();
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->decimal('discount_amount', 10, 2)->default(0);
        });
    }

    public function down()
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value', 'discount_amount']);
        });

        Schema::table('offer_items', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value', 'discount_amount']);
        });
    }
};
