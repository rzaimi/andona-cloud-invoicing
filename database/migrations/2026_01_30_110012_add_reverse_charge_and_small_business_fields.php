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
            $table->boolean('is_reverse_charge')->default(false)->after('total');
            $table->string('buyer_vat_id')->nullable()->after('is_reverse_charge');
            $table->enum('vat_exemption_type', ['none', 'eu_intracommunity', 'export', 'other'])->default('none')->after('buyer_vat_id');
            $table->text('vat_exemption_reason')->nullable()->after('vat_exemption_type');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('is_small_business')->default(false)->after('vat_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['is_reverse_charge', 'buyer_vat_id', 'vat_exemption_type', 'vat_exemption_reason']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('is_small_business');
        });
    }
};
