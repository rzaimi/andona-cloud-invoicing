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
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('status');
        });
        
        // Set the first company as default if it exists
        $firstCompany = \App\Modules\Company\Models\Company::first();
        if ($firstCompany) {
            $firstCompany->update(['is_default' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
};
