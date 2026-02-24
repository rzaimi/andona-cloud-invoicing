<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Rechnungstyp (Bauvertragsrecht / BV context)
            $table->string('invoice_type')->default('standard')->after('vat_regime');
            $table->unsignedTinyInteger('sequence_number')->nullable()->after('invoice_type');

            // Skonto configuration
            $table->decimal('skonto_percent', 5, 2)->nullable()->after('due_date');
            $table->unsignedSmallInteger('skonto_days')->nullable()->after('skonto_percent');
            $table->decimal('skonto_amount', 12, 2)->nullable()->after('skonto_days');
            $table->date('skonto_due_date')->nullable()->after('skonto_amount');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_type',
                'sequence_number',
                'skonto_percent',
                'skonto_days',
                'skonto_amount',
                'skonto_due_date',
            ]);
        });
    }
};
