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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('smtp_host')->nullable()->after('logo');
            $table->integer('smtp_port')->nullable()->after('smtp_host');
            $table->string('smtp_username')->nullable()->after('smtp_port');
            $table->string('smtp_password')->nullable()->after('smtp_username');
            $table->string('smtp_encryption')->nullable()->after('smtp_password'); // 'tls', 'ssl', or null
            $table->string('smtp_from_address')->nullable()->after('smtp_encryption');
            $table->string('smtp_from_name')->nullable()->after('smtp_from_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Note: These columns may have been dropped by normalize_company_smtp_bank_to_settings migration
            // Only drop if they still exist
            $columnsToDrop = [];
            if (Schema::hasColumn('companies', 'smtp_host')) {
                $columnsToDrop[] = 'smtp_host';
            }
            if (Schema::hasColumn('companies', 'smtp_port')) {
                $columnsToDrop[] = 'smtp_port';
            }
            if (Schema::hasColumn('companies', 'smtp_username')) {
                $columnsToDrop[] = 'smtp_username';
            }
            if (Schema::hasColumn('companies', 'smtp_password')) {
                $columnsToDrop[] = 'smtp_password';
            }
            if (Schema::hasColumn('companies', 'smtp_encryption')) {
                $columnsToDrop[] = 'smtp_encryption';
            }
            if (Schema::hasColumn('companies', 'smtp_from_address')) {
                $columnsToDrop[] = 'smtp_from_address';
            }
            if (Schema::hasColumn('companies', 'smtp_from_name')) {
                $columnsToDrop[] = 'smtp_from_name';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
