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

        Schema::table('users', function (Blueprint $table) {
            // Try to drop foreign key - wrap in try-catch in case it doesn't exist or has different name
            try {
                // Laravel convention: users_company_id_foreign
                $table->dropForeign(['company_id']);
            } catch (\Illuminate\Database\QueryException $e) {
                // Foreign key might not exist or have different name
                // Try to find and drop it by querying information_schema
                if (Schema::getConnection()->getDriverName() === 'mysql') {
                    $dbName = Schema::getConnection()->getDatabaseName();
                    $foreignKeys = Schema::getConnection()
                        ->select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'users' AND COLUMN_NAME = 'company_id' AND REFERENCED_TABLE_NAME IS NOT NULL", [$dbName]);
                    
                    if (!empty($foreignKeys)) {
                        $foreignKeyName = $foreignKeys[0]->CONSTRAINT_NAME;
                        Schema::getConnection()->statement("ALTER TABLE `users` DROP FOREIGN KEY `{$foreignKeyName}`");
                    }
                }
            }
            
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
