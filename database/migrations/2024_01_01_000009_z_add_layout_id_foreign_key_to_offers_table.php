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
        Schema::table('offers', function (Blueprint $table) {
            $table->foreign('layout_id')
                ->references('id')
                ->on('offer_layouts')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        $connection = Schema::getConnection();
        $driverName = $connection->getDriverName();
        
        // Check and drop foreign key if it exists
        if ($driverName === 'mysql' && Schema::hasTable('offers')) {
            $dbName = $connection->getDatabaseName();
            $foreignKeys = $connection->select(
                "SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_NAME = 'offers' 
                AND COLUMN_NAME = 'layout_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL",
                [$dbName]
            );
            
            if (!empty($foreignKeys)) {
                $foreignKeyName = $foreignKeys[0]->CONSTRAINT_NAME;
                try {
                    $connection->statement("ALTER TABLE `offers` DROP FOREIGN KEY `{$foreignKeyName}`");
                } catch (\Illuminate\Database\QueryException $e) {
                    // Foreign key might have already been dropped, continue
                }
            }
        }
    }
};
