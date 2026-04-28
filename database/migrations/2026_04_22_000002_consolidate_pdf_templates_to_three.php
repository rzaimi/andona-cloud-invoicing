<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Template consolidation: invoice- and offer-templates previously had 6
 * cosmetic variants each; we're down to three unified themes
 * (clean, classic, modern). Remap any layout rows still pointing at a
 * retired template.
 */
return new class extends Migration
{
    private array $aliasMap = [
        // Three themes survive: minimal, professional, modern.
        // `clean` → minimal (brief rename during dev).
        // `classic` and `elegant` → professional (closest navy/formal match).
        // `creative` → modern.
        'clean'    => 'minimal',
        'classic'  => 'professional',
        'elegant'  => 'professional',
        'creative' => 'modern',
    ];

    public function up(): void
    {
        foreach (['invoice_layouts', 'offer_layouts'] as $table) {
            foreach ($this->aliasMap as $from => $to) {
                DB::table($table)
                    ->where('template', $from)
                    ->update(['template' => $to]);
            }
        }
    }

    /**
     * Rollback: best-effort. We can't recover which rows were originally
     * "minimal" vs "clean"; anyone rolling back should treat this as
     * non-reversible on data. Leaves existing template values unchanged.
     */
    public function down(): void
    {
        // No-op — the mapping is one-way.
    }
};
