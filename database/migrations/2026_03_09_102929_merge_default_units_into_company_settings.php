<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Additive merge: adds any units from the canonical config list that are missing
 * from each tenant's stored default_units setting.
 *
 * Existing tenant units (including custom ones) are preserved and their order
 * is kept. New canonical units are appended at the end.
 */
return new class extends Migration
{
    public function up(): void
    {
        $canonical = config('units.default');

        $rows = DB::table('company_settings')
            ->where('key', 'default_units')
            ->get();

        foreach ($rows as $row) {
            $current = is_array($row->value)
                ? $row->value
                : json_decode($row->value, true);

            if (!is_array($current)) {
                $current = $canonical;
            }

            // Append canonical units that are not yet in the tenant's list
            $missing = array_values(array_diff($canonical, $current));
            if (empty($missing)) {
                continue;
            }

            $merged = array_merge($current, $missing);

            DB::table('company_settings')
                ->where('id', $row->id)
                ->update(['value' => json_encode($merged, JSON_UNESCAPED_UNICODE)]);
        }
    }

    public function down(): void
    {
        // Not reversible — we cannot know which units were added vs pre-existing
    }
};
