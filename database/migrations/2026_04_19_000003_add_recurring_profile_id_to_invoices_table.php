<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->uuid('recurring_profile_id')->nullable()->after('layout_id');
            $table->foreign('recurring_profile_id', 'invoices_recurring_profile_fk')
                ->references('id')->on('recurring_invoice_profiles')
                ->onDelete('set null');
            $table->index(['recurring_profile_id'], 'invoices_recurring_profile_idx');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign('invoices_recurring_profile_fk');
            $table->dropIndex('invoices_recurring_profile_idx');
            $table->dropColumn('recurring_profile_id');
        });
    }
};
