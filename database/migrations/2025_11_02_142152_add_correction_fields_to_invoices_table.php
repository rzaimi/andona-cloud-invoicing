<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Correction tracking fields
            $table->boolean('is_correction')->default(false)->after('status');
            $table->uuid('corrects_invoice_id')->nullable()->after('is_correction');
            $table->uuid('corrected_by_invoice_id')->nullable()->after('corrects_invoice_id');
            $table->text('correction_reason')->nullable()->after('corrected_by_invoice_id');
            $table->timestamp('corrected_at')->nullable()->after('correction_reason');
            
            // Foreign keys
            $table->foreign('corrects_invoice_id')->references('id')->on('invoices')->onDelete('set null');
            $table->foreign('corrected_by_invoice_id')->references('id')->on('invoices')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['corrects_invoice_id']);
            $table->dropForeign(['corrected_by_invoice_id']);
            $table->dropColumn([
                'is_correction',
                'corrects_invoice_id',
                'corrected_by_invoice_id',
                'correction_reason',
                'corrected_at',
            ]);
        });
    }
};
