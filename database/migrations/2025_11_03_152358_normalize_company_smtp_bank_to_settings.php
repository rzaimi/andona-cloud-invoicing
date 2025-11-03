<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Modules\Company\Models\Company;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Migrate existing SMTP and bank data to company_settings
        $companies = Company::all();
        
        foreach ($companies as $company) {
            // Migrate SMTP settings
            $smtpSettings = [
                'smtp_host' => $company->smtp_host,
                'smtp_port' => $company->smtp_port,
                'smtp_username' => $company->smtp_username,
                'smtp_password' => $company->smtp_password,
                'smtp_encryption' => $company->smtp_encryption,
                'smtp_from_address' => $company->smtp_from_address,
                'smtp_from_name' => $company->smtp_from_name,
            ];
            
            foreach ($smtpSettings as $key => $value) {
                if ($value !== null) {
                    $type = $key === 'smtp_port' ? 'integer' : 'string';
                    $company->setSetting($key, $value, $type);
                }
            }
            
            // Migrate bank settings
            $bankSettings = [
                'bank_name' => $company->bank_name,
                'bank_iban' => $company->bank_iban,
                'bank_bic' => $company->bank_bic,
            ];
            
            foreach ($bankSettings as $key => $value) {
                if ($value !== null) {
                    $company->setSetting($key, $value, 'string');
                }
            }
        }
        
        // Step 2: Drop the columns from companies table
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'smtp_host',
                'smtp_port',
                'smtp_username',
                'smtp_password',
                'smtp_encryption',
                'smtp_from_address',
                'smtp_from_name',
                'bank_name',
                'bank_iban',
                'bank_bic',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the columns
        Schema::table('companies', function (Blueprint $table) {
            $table->string('smtp_host')->nullable()->after('logo');
            $table->integer('smtp_port')->nullable()->after('smtp_host');
            $table->string('smtp_username')->nullable()->after('smtp_port');
            $table->string('smtp_password')->nullable()->after('smtp_username');
            $table->string('smtp_encryption')->nullable()->after('smtp_password');
            $table->string('smtp_from_address')->nullable()->after('smtp_encryption');
            $table->string('smtp_from_name')->nullable()->after('smtp_from_address');
            $table->string('bank_name')->nullable()->after('managing_director');
            $table->string('bank_iban')->nullable()->after('bank_name');
            $table->string('bank_bic')->nullable()->after('bank_iban');
        });
        
        // Migrate data back from settings to columns (if needed)
        // Note: This would require querying company_settings and restoring to columns
    }
};
