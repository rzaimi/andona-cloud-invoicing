<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Service Date / Period
            $table->date('service_date')->nullable()->after('issue_date');
            $table->date('service_period_start')->nullable()->after('service_date');
            $table->date('service_period_end')->nullable()->after('service_period_start');
            
            // VAT Regime
            // standard: Standard German VAT (19% / 7%)
            // small_business: Kleinunternehmerregelung (§ 19 UStG)
            // reverse_charge: Steuerschuldnerschaft des Leistungsempfängers (§ 13b UStG)
            // intra_community: Innergemeinschaftliche Lieferung (§ 4 Nr. 1b UStG)
            // export: Ausfuhrlieferung (§ 4 Nr. 1a UStG)
            $table->string('vat_regime')->default('standard')->after('tax_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'service_date',
                'service_period_start',
                'service_period_end',
                'vat_regime',
            ]);
        });
    }
};
