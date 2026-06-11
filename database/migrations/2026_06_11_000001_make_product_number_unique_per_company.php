<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Product numbers are tenant-scoped: two companies may legitimately use the same
 * product number (e.g. "PR-2026-0001"). The original table made `number`
 * globally unique, which both leaked across tenants and broke inserts whenever
 * two companies generated the same number.
 *
 * Replace the global unique index with a composite unique on (company_id, number).
 *
 * Note: on MySQL the original inline unique survives as `products_number_unique`;
 * on SQLite it was already dropped by an earlier table rebuild, so we only drop
 * it when it actually exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        $hasGlobalUnique = $this->hasIndexOnColumns(['number'], true);
        $hasCompositeUnique = $this->hasIndexOnColumns(['company_id', 'number'], true);

        Schema::table('products', function (Blueprint $table) use ($hasGlobalUnique, $hasCompositeUnique) {
            if ($hasGlobalUnique) {
                $table->dropUnique(['number']);
            }
            if (! $hasCompositeUnique) {
                $table->unique(['company_id', 'number'], 'products_company_number_unique');
            }
        });
    }

    public function down(): void
    {
        $hasCompositeUnique = $this->hasIndexOnColumns(['company_id', 'number'], true);
        $hasGlobalUnique = $this->hasIndexOnColumns(['number'], true);

        Schema::table('products', function (Blueprint $table) use ($hasCompositeUnique, $hasGlobalUnique) {
            if ($hasCompositeUnique) {
                $table->dropUnique('products_company_number_unique');
            }
            if (! $hasGlobalUnique) {
                $table->unique('number');
            }
        });
    }

    /**
     * @param  list<string>  $columns
     */
    private function hasIndexOnColumns(array $columns, bool $uniqueOnly): bool
    {
        foreach (Schema::getIndexes('products') as $index) {
            if ($uniqueOnly && empty($index['unique'])) {
                continue;
            }

            $indexColumns = $index['columns'] ?? [];
            sort($indexColumns);
            $wanted = $columns;
            sort($wanted);

            if ($indexColumns === $wanted) {
                return true;
            }
        }

        return false;
    }
};
