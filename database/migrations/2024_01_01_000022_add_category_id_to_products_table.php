<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {

        Schema::table('products', function (Blueprint $table) {
            // Drop index manually if exists
            $table->dropIndex(['company_id', 'category']); // Might throw if not present

            // Drop old category column
            if (Schema::hasColumn('products', 'category')) {
                $table->dropColumn('category');
            }

            // Add new foreign keys
            $table->foreignUuid('category_id')->nullable()->after('company_id')->constrained()->onDelete('set null');
            $table->foreignUuid('default_warehouse_id')->nullable()->after('category_id')->constrained('warehouses')->onDelete('set null');

            // Add new indexes
            $table->index(['company_id', 'category_id']);
            $table->index(['company_id', 'default_warehouse_id']);
        });
    }

    public function down()
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['default_warehouse_id']);
            $table->dropColumn(['category_id', 'default_warehouse_id']);
            $table->string('category')->nullable();
        });
    }
};
