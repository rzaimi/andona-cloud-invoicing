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
            if (!is_array($current) || in_array('Rolle', $current)) {
                continue;
            }
            $current[] = 'Rolle';
            DB::table('company_settings')
                ->where('id', $row->id)
                ->update(['value' => json_encode($current, JSON_UNESCAPED_UNICODE)]);
        }
    }

    public function down(): void {}
};
