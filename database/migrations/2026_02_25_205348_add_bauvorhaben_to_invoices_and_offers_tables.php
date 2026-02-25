<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('bauvorhaben')->nullable()->after('notes');
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->string('bauvorhaben')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('bauvorhaben');
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn('bauvorhaben');
        });
    }
};
