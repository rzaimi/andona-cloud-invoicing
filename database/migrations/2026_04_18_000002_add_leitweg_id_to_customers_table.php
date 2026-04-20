<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Leitweg-ID (BT-10 Buyer reference) — required for XRechnung to
            // German public-sector customers. Format varies (grob/fein), we
            // store as opaque string and let the customer supply the value.
            $table->string('leitweg_id', 64)->nullable()->after('vat_number');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('leitweg_id');
        });
    }
};
