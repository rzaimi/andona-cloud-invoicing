<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop the global unique constraint on number alone
            $table->dropUnique(['number']);

            // Drop the old composite index (we'll replace it with a unique one)
            $table->dropIndex(['number', 'company_id']);

            // Add a per-company unique constraint: same number can exist in different companies
            $table->unique(['company_id', 'number'], 'products_company_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_company_number_unique');

            // Restore original constraints
            $table->unique('number');
            $table->index(['number', 'company_id']);
        });
    }
};
