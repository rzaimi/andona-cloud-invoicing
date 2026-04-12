<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add optional employee profile columns to the users table.
     * On SQLite the previous migration already recreated the table with these columns,
     * so we skip if they already exist.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'staff_number')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('staff_number', 50)->nullable()->after('status');
                $table->string('department', 100)->nullable()->after('staff_number');
                $table->string('job_title', 100)->nullable()->after('department');
            });
        }
    }

    public function down(): void
    {
        $columns = ['staff_number', 'department', 'job_title'];
        $toDrop  = array_filter($columns, fn ($c) => Schema::hasColumn('users', $c));

        if (!empty($toDrop)) {
            Schema::table('users', function (Blueprint $table) use ($toDrop) {
                $table->dropColumn(array_values($toDrop));
            });
        }
    }
};
