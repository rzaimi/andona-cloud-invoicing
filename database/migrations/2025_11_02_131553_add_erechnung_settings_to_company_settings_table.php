<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            // E-Rechnung settings
            $table->boolean('erechnung_enabled')->default(false)->after('currency');
            $table->boolean('xrechnung_enabled')->default(true)->after('erechnung_enabled');
            $table->boolean('zugferd_enabled')->default(true)->after('xrechnung_enabled');
            $table->string('zugferd_profile')->default('EN16931')->after('zugferd_enabled'); // MINIMUM, BASIC, EN16931, EXTENDED
            $table->string('business_process_id')->nullable()->after('zugferd_profile');
            $table->string('electronic_address_scheme')->nullable()->after('business_process_id'); // e.g., EM (Email)
            $table->string('electronic_address')->nullable()->after('electronic_address_scheme');
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn([
                'erechnung_enabled',
                'xrechnung_enabled',
                'zugferd_enabled',
                'zugferd_profile',
                'business_process_id',
                'electronic_address_scheme',
                'electronic_address',
            ]);
        });
    }
};
