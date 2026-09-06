<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guarded drops: down() legitimately skips restoring the global unique
        // when duplicate numbers exist, so a rollback + re-migrate must not
        // fail on a missing index.
        $indexes = collect(Schema::getIndexes('products'));
        $hasGlobalUnique = $indexes->contains(fn ($index) => ! empty($index['unique']) && ($index['columns'] ?? []) === ['number']);
        $hasNumberCompanyIndex = $indexes->contains(fn ($index) => ($index['columns'] ?? []) === ['number', 'company_id']);
        $hasComposite = $indexes->contains(fn ($index) => ($index['name'] ?? '') === 'products_company_number_unique');

        Schema::table('products', function (Blueprint $table) use ($hasGlobalUnique, $hasNumberCompanyIndex, $hasComposite) {
            if ($hasGlobalUnique) {
                $table->dropUnique(['number']);
            }
            if ($hasNumberCompanyIndex) {
                $table->dropIndex(['number', 'company_id']);
            }
            if (! $hasComposite) {
                $table->unique(['company_id', 'number'], 'products_company_number_unique');
            }
        });
    }

    public function down(): void
    {
        $indexes = collect(Schema::getIndexes('products'));
        $hasComposite = $indexes->contains(fn ($index) => ($index['name'] ?? '') === 'products_company_number_unique');
        $hasGlobalUnique = $indexes->contains(fn ($index) => ! empty($index['unique']) && ($index['columns'] ?? []) === ['number']);
        $hasNumberCompanyIndex = $indexes->contains(fn ($index) => ($index['columns'] ?? []) === ['number', 'company_id']);

        Schema::table('products', function (Blueprint $table) use ($hasComposite) {
            if ($hasComposite) {
                $table->dropUnique('products_company_number_unique');
            }
        });

        $hasDuplicateNumbers = DB::table('products')
            ->select('number')
            ->groupBy('number')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        Schema::table('products', function (Blueprint $table) use ($hasGlobalUnique, $hasDuplicateNumbers, $hasNumberCompanyIndex) {
            if (! $hasGlobalUnique && ! $hasDuplicateNumbers) {
                $table->unique('number');
            }
            if (! $hasNumberCompanyIndex) {
                $table->index(['number', 'company_id']);
            }
        });
    }
};
