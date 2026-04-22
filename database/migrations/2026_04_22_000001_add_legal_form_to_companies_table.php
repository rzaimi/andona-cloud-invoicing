<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // German legal form (Rechtsform). Stored as a short key — the
            // human-readable label and the role of the managing person
            // ("Inhaber" vs "Geschäftsführer" vs "Vorstand") are derived
            // from this in the model.
            $table->string('legal_form', 32)->nullable()->after('managing_director');

            // Optional free-text override for the role label. Used for edge
            // cases like "Prokurist" where the auto-derived role doesn't fit.
            $table->string('manager_title_override', 64)->nullable()->after('legal_form');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['legal_form', 'manager_title_override']);
        });
    }
};
