<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mahnsperre: while dunning_paused_until is today or later, the automatic
     * escalation skips the invoice ("Kunde zahlt Freitag"). Manual sends from
     * the Mahnwesen workspace remain possible.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->date('dunning_paused_until')->nullable()->after('reminder_history');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('dunning_paused_until');
        });
    }
};
