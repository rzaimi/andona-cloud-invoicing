<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recurring_invoice_profiles', function (Blueprint $table) {
            $table->boolean('service_period_full_month')
                ->default(false)
                ->after('auto_send')
                ->comment('When true, generated invoices get service_period_start = first day and service_period_end = last day of the invoice month.');
        });
    }

    public function down(): void
    {
        Schema::table('recurring_invoice_profiles', function (Blueprint $table) {
            $table->dropColumn('service_period_full_month');
        });
    }
};
