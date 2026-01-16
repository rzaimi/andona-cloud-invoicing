<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * Deprecated migration (kept for historical reasons).
         *
         * `company_settings` is a key/value table and must NOT be extended with dedicated columns.
         * E‑Rechnung settings are stored as key/value rows instead:
         * - keys: erechnung_enabled, xrechnung_enabled, zugferd_enabled, zugferd_profile,
         *         business_process_id, electronic_address_scheme, electronic_address
         *
         * A follow-up cleanup migration removes any legacy columns if they exist.
         */
        return;
    }

    public function down(): void
    {
        // No-op (this migration no longer changes schema).
        return;
    }
};
