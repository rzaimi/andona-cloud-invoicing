<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Correction tracking fields
            $table->boolean('is_correction')->default(false)->after('status');
            $table->uuid('corrects_invoice_id')->nullable()->after('is_correction');
            $table->uuid('corrected_by_invoice_id')->nullable()->after('corrects_invoice_id');
            $table->text('correction_reason')->nullable()->after('corrected_by_invoice_id');
            $table->timestamp('corrected_at')->nullable()->after('correction_reason');
            
            // Foreign keys
            $table->foreign('corrects_invoice_id')->references('id')->on('invoices')->onDelete('set null');
            $table->foreign('corrected_by_invoice_id')->references('id')->on('invoices')->onDelete('set null');
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        $connection = Schema::getConnection();
        $driverName = $connection->getDriverName();
        
        // Drop foreign keys if they exist
        if ($driverName === 'mysql' && Schema::hasTable('invoices')) {
            $dbName = $connection->getDatabaseName();
            
            // Drop corrects_invoice_id foreign key
            $foreignKeys = $connection->select(
                "SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_NAME = 'invoices' 
                AND COLUMN_NAME = 'corrects_invoice_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL",
                [$dbName]
            );
            
            if (!empty($foreignKeys)) {
                $foreignKeyName = $foreignKeys[0]->CONSTRAINT_NAME;
                try {
                    $connection->statement("ALTER TABLE `invoices` DROP FOREIGN KEY `{$foreignKeyName}`");
                } catch (\Illuminate\Database\QueryException $e) {
                    // Ignore if already dropped
                }
            }
            
            // Drop corrected_by_invoice_id foreign key
            $foreignKeys = $connection->select(
                "SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_NAME = 'invoices' 
                AND COLUMN_NAME = 'corrected_by_invoice_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL",
                [$dbName]
            );
            
            if (!empty($foreignKeys)) {
                $foreignKeyName = $foreignKeys[0]->CONSTRAINT_NAME;
                try {
                    $connection->statement("ALTER TABLE `invoices` DROP FOREIGN KEY `{$foreignKeyName}`");
                } catch (\Illuminate\Database\QueryException $e) {
                    // Ignore if already dropped
                }
            }
        }

        Schema::table('invoices', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('invoices', 'is_correction')) {
                $columnsToDrop[] = 'is_correction';
            }
            if (Schema::hasColumn('invoices', 'corrects_invoice_id')) {
                $columnsToDrop[] = 'corrects_invoice_id';
            }
            if (Schema::hasColumn('invoices', 'corrected_by_invoice_id')) {
                $columnsToDrop[] = 'corrected_by_invoice_id';
            }
            if (Schema::hasColumn('invoices', 'correction_reason')) {
                $columnsToDrop[] = 'correction_reason';
            }
            if (Schema::hasColumn('invoices', 'corrected_at')) {
                $columnsToDrop[] = 'corrected_at';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
