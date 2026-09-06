<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The original warehouses table had two unique constraints on `code`:
 *   1. A global unique: $table->string('code')->unique()
 *   2. A per-company composite: $table->unique(['company_id', 'code'])
 *
 * The global constraint incorrectly prevents multiple companies from using
 * the same warehouse code (e.g. 'HQ'). Only the composite constraint is correct.
 * This migration drops the global one.
 */
return new class extends Migration
{
    public function up(): void
    {
        // down() skips restoring the global unique when duplicate codes exist,
        // so re-running up() after a rollback must tolerate a missing index.
        $hasGlobalUnique = collect(Schema::getIndexes('warehouses'))
            ->contains(fn ($index) => ! empty($index['unique']) && ($index['columns'] ?? []) === ['code']);

        if (! $hasGlobalUnique) {
            return;
        }

        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });
    }

    public function down(): void
    {
        $hasDuplicateCodes = DB::table('warehouses')
            ->select('code')
            ->groupBy('code')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicateCodes) {
            return;
        }

        Schema::table('warehouses', function (Blueprint $table) {
            $table->unique('code');
        });
    }
};
