<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * For MySQL: alters the real ENUM column to include 'payroll'.
     * For SQLite (tests/local): no-op — payroll is already in the base
     * create_documents_table migration, so RefreshDatabase picks it up automatically.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE documents MODIFY COLUMN category ENUM('payroll','employee','customer','invoice','company','financial','custom') NOT NULL DEFAULT 'custom'"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE documents MODIFY COLUMN category ENUM('employee','customer','invoice','company','financial','custom') NOT NULL DEFAULT 'custom'"
        );
    }
};
