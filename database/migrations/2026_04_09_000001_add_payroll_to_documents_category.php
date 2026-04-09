<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SQLite stores Laravel enum columns as varchar; MySQL uses a real ENUM and must be altered.
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
