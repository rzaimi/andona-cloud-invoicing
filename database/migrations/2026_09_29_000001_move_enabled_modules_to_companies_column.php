<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The module list moves out of the companies.settings JSON blob into its
     * own column. The `settings` key collided with the settings() relation
     * when serializing, and a dedicated column keeps one source of truth.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->json('enabled_modules')->nullable()->after('settings');
        });

        foreach (DB::table('companies')->whereNotNull('settings')->get(['id', 'settings']) as $company) {
            $settings = json_decode($company->settings, true);

            if (! is_array($settings) || ! array_key_exists('enabled_modules', $settings)) {
                continue;
            }

            $modules = $settings['enabled_modules'];
            unset($settings['enabled_modules']);

            DB::table('companies')->where('id', $company->id)->update([
                'enabled_modules' => is_array($modules) && $modules !== [] ? json_encode(array_values($modules)) : null,
                'settings' => $settings === [] ? null : json_encode($settings),
            ]);
        }
    }

    public function down(): void
    {
        foreach (DB::table('companies')->whereNotNull('enabled_modules')->get(['id', 'settings', 'enabled_modules']) as $company) {
            $settings = json_decode($company->settings ?? '[]', true);
            $settings = is_array($settings) ? $settings : [];
            $settings['enabled_modules'] = json_decode($company->enabled_modules, true);

            DB::table('companies')->where('id', $company->id)->update([
                'settings' => json_encode($settings),
            ]);
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('enabled_modules');
        });
    }
};
