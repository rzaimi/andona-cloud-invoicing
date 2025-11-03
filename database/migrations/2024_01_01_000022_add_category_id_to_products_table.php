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
            try {
                $table->dropIndex(['company_id', 'category']);
            } catch (\Illuminate\Database\QueryException $e) {
                // Index doesn't exist, continue
            }

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

        $connection = Schema::getConnection();
        $driverName = $connection->getDriverName();
        
        // Drop foreign keys if they exist
        if ($driverName === 'mysql' && Schema::hasTable('products')) {
            $dbName = $connection->getDatabaseName();
            
            // Drop category_id foreign key
            $foreignKeys = $connection->select(
                "SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_NAME = 'products' 
                AND COLUMN_NAME = 'category_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL",
                [$dbName]
            );
            
            if (!empty($foreignKeys)) {
                $foreignKeyName = $foreignKeys[0]->CONSTRAINT_NAME;
                try {
                    $connection->statement("ALTER TABLE `products` DROP FOREIGN KEY `{$foreignKeyName}`");
                } catch (\Illuminate\Database\QueryException $e) {
                    // Ignore if already dropped
                }
            }
            
            // Drop default_warehouse_id foreign key
            $foreignKeys = $connection->select(
                "SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_NAME = 'products' 
                AND COLUMN_NAME = 'default_warehouse_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL",
                [$dbName]
            );
            
            if (!empty($foreignKeys)) {
                $foreignKeyName = $foreignKeys[0]->CONSTRAINT_NAME;
                try {
                    $connection->statement("ALTER TABLE `products` DROP FOREIGN KEY `{$foreignKeyName}`");
                } catch (\Illuminate\Database\QueryException $e) {
                    // Ignore if already dropped
                }
            }
        }

        Schema::table('products', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('products', 'category_id')) {
                $columnsToDrop[] = 'category_id';
            }
            if (Schema::hasColumn('products', 'default_warehouse_id')) {
                $columnsToDrop[] = 'default_warehouse_id';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
            
            // Recreate old category column if it doesn't exist
            if (!Schema::hasColumn('products', 'category')) {
                $table->string('category')->nullable();
            }
        });
    }
};
