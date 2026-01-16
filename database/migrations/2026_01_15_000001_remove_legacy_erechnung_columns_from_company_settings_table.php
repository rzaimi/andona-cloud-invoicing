<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('company_settings')) {
            return;
        }

        $columnsToDrop = [];
        foreach ([
            'erechnung_enabled',
            'xrechnung_enabled',
            'zugferd_enabled',
            'zugferd_profile',
            'business_process_id',
            'electronic_address_scheme',
            'electronic_address',
        ] as $col) {
            if (Schema::hasColumn('company_settings', $col)) {
                $columnsToDrop[] = $col;
            }
        }

        if (empty($columnsToDrop)) {
            return;
        }

        /**
         * Safety for SQLite: dropping columns requires SQLite >= 3.35.
         * If not supported, we skip to avoid breaking local/test migrations.
         */
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            try {
                $row = DB::selectOne('select sqlite_version() as v');
                $version = $row?->v ?? null;
                if ($version && version_compare($version, '3.35.0', '<')) {
                    return;
                }
            } catch (\Throwable $e) {
                // If we can't detect the version, stay safe and skip.
                return;
            }
        }

        Schema::table('company_settings', function (Blueprint $table) use ($columnsToDrop) {
            $table->dropColumn($columnsToDrop);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('company_settings')) {
            return;
        }

        // Recreate legacy columns (not used; kept only for reversibility).
        Schema::table('company_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('company_settings', 'erechnung_enabled')) {
                $table->boolean('erechnung_enabled')->default(false);
            }
            if (!Schema::hasColumn('company_settings', 'xrechnung_enabled')) {
                $table->boolean('xrechnung_enabled')->default(true);
            }
            if (!Schema::hasColumn('company_settings', 'zugferd_enabled')) {
                $table->boolean('zugferd_enabled')->default(true);
            }
            if (!Schema::hasColumn('company_settings', 'zugferd_profile')) {
                $table->string('zugferd_profile')->default('EN16931');
            }
            if (!Schema::hasColumn('company_settings', 'business_process_id')) {
                $table->string('business_process_id')->nullable();
            }
            if (!Schema::hasColumn('company_settings', 'electronic_address_scheme')) {
                $table->string('electronic_address_scheme')->nullable();
            }
            if (!Schema::hasColumn('company_settings', 'electronic_address')) {
                $table->string('electronic_address')->nullable();
            }
        });
    }
};

