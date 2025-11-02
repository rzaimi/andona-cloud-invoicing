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
            // Note: company_settings uses key-value structure, so these columns don't fit the schema
            // These settings should be stored as key-value pairs instead
            // Keeping migration for backward compatibility but these columns may not be used
            $table->boolean('erechnung_enabled')->default(false);
            $table->boolean('xrechnung_enabled')->default(true);
            $table->boolean('zugferd_enabled')->default(true);
            $table->string('zugferd_profile')->default('EN16931'); // MINIMUM, BASIC, EN16931, EXTENDED
            $table->string('business_process_id')->nullable();
            $table->string('electronic_address_scheme')->nullable(); // e.g., EM (Email)
            $table->string('electronic_address')->nullable();
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
