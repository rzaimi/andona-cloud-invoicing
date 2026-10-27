<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('company_settings')
            ->where('key', 'default_units')
            ->get();

        foreach ($rows as $row) {
            $current = is_array($row->value) ? $row->value : json_decode($row->value, true);
            if (! is_array($current) || in_array('lfm', $current, true)) {
                continue;
            }

            $updated = [];
            $inserted = false;
            foreach ($current as $unit) {
                $updated[] = $unit;
                if ($unit === 'm') {
                    $updated[] = 'lfm';
                    $inserted = true;
                }
            }
            if (! $inserted) {
                $updated[] = 'lfm';
            }

            DB::table('company_settings')
                ->where('id', $row->id)
                ->update(['value' => json_encode($updated, JSON_UNESCAPED_UNICODE)]);
        }
    }

    public function down(): void
    {
        $rows = DB::table('company_settings')
            ->where('key', 'default_units')
            ->get();

        foreach ($rows as $row) {
            $current = is_array($row->value) ? $row->value : json_decode($row->value, true);
            if (! is_array($current)) {
                continue;
            }

            $updated = array_values(array_filter($current, fn ($unit) => $unit !== 'lfm'));

            DB::table('company_settings')
                ->where('id', $row->id)
                ->update(['value' => json_encode($updated, JSON_UNESCAPED_UNICODE)]);
        }
    }
};
