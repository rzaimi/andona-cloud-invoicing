<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('id')->change();
            $table->uuid('company_id')->nullable()->after('id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->enum('role', ['admin', 'user'])->default('user');
            $table->enum('status', ['active', 'inactive'])->default('active');
        });
    }

    public function down()
    {
        // Skip rollback for SQLite (has limitations with foreign keys and column modifications)
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        $connection = Schema::getConnection();
        $driverName = $connection->getDriverName();
        
        // Check and drop foreign key if it exists (before Schema::table closure)
        if ($driverName === 'mysql' && Schema::hasTable('users')) {
            $dbName = $connection->getDatabaseName();
            $foreignKeys = $connection->select(
                "SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_NAME = 'users' 
                AND COLUMN_NAME = 'company_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL",
                [$dbName]
            );
            
            if (!empty($foreignKeys)) {
                $foreignKeyName = $foreignKeys[0]->CONSTRAINT_NAME;
                try {
                    $connection->statement("ALTER TABLE `users` DROP FOREIGN KEY `{$foreignKeyName}`");
                } catch (\Illuminate\Database\QueryException $e) {
                    // Foreign key might have already been dropped, continue
                }
            }
        }
        
        Schema::table('users', function (Blueprint $table) {
            // Drop columns only if they exist
            $columnsToDrop = [];
            if (Schema::hasColumn('users', 'company_id')) {
                $columnsToDrop[] = 'company_id';
            }
            if (Schema::hasColumn('users', 'role')) {
                $columnsToDrop[] = 'role';
            }
            if (Schema::hasColumn('users', 'status')) {
                $columnsToDrop[] = 'status';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
            
            // Don't change id column back - users table now uses UUIDs (HasUuids trait)
            // Cannot convert UUID strings back to auto-incrementing bigint without data loss
        });
    }
};
