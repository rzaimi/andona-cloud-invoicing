<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->uuid('product_id')->nullable()->after('invoice_id');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
        });
    }

    public function down()
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        $connection = Schema::getConnection();
        $driverName = $connection->getDriverName();
        
        // Check and drop foreign key if it exists
        if ($driverName === 'mysql' && Schema::hasTable('invoice_items')) {
            $dbName = $connection->getDatabaseName();
            $foreignKeys = $connection->select(
                "SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_NAME = 'invoice_items' 
                AND COLUMN_NAME = 'product_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL",
                [$dbName]
            );
            
            if (!empty($foreignKeys)) {
                $foreignKeyName = $foreignKeys[0]->CONSTRAINT_NAME;
                try {
                    $connection->statement("ALTER TABLE `invoice_items` DROP FOREIGN KEY `{$foreignKeyName}`");
                } catch (\Illuminate\Database\QueryException $e) {
                    // Foreign key might have already been dropped, continue
                }
            }
        }

        Schema::table('invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_items', 'product_id')) {
                $table->dropColumn('product_id');
            }
        });
    }
};
