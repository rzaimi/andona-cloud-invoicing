<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // JSON snapshot of linked Abschlagsrechnungen — only populated on Schlussrechnung rows.
            // Stored as a snapshot so the PDF stays correct even if the Abschlag is later edited.
            // Format: [{"invoice_id":"…","number":"RE-…","amount":10000,"date":"2026-01-15"}]
            $table->json('abschlag_refs')->nullable()->after('auftragsnummer');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('abschlag_refs');
        });
    }
};
