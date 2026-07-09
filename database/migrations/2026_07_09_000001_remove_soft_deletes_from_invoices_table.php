<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Invoices are no longer soft-deletable: deletes are permanent (drafts
     * only, enforced in the controller per GoBD). Purge any rows still in
     * the trash — they were deleted drafts — then drop the column so their
     * numbers leave the (company_id, number) unique index for good.
     */
    public function up(): void
    {
        DB::table('invoices')->whereNotNull('deleted_at')->delete();

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->softDeletes();
        });
    }
};
