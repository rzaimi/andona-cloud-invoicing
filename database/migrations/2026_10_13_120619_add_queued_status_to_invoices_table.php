<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL ENUM columns must be altered with a raw statement that
        // lists ALL desired values — there is no addEnumValue() helper.
        DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('draft','queued','sent','paid','overdue','cancelled') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        // First reset any queued invoices back to draft so the down
        // migration does not violate the narrower enum constraint.
        DB::statement("UPDATE invoices SET status = 'draft' WHERE status = 'queued'");

        DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('draft','sent','paid','overdue','cancelled') NOT NULL DEFAULT 'draft'");
    }
};
