<?php

namespace Database\Seeders;

use App\Modules\Company\Models\Company;
use App\Services\SettingsService;
use Illuminate\Database\Seeder;

class MahnungSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settingsService = app(SettingsService::class);

        $companies = Company::all();

        $defaultSettings = [
            // Reminder intervals (days after due date)
            'reminder_friendly_days' => 7,   // Friendly reminder after 7 days
            'reminder_mahnung1_days' => 14,  // 1. Mahnung after 14 days
            'reminder_mahnung2_days' => 21,  // 2. Mahnung after 21 days
            'reminder_mahnung3_days' => 30,  // 3. Mahnung after 30 days
            'reminder_inkasso_days' => 45,   // Inkasso after 45 days

            // Reminder fees
            'reminder_mahnung1_fee' => 5.00,  // €5 fee for 1. Mahnung
            'reminder_mahnung2_fee' => 10.00, // €10 fee for 2. Mahnung
            'reminder_mahnung3_fee' => 15.00, // €15 fee for 3. Mahnung

            // Interest rate (percentage per year)
            'reminder_interest_rate' => 9.00, // 9% annual interest

            // Automatic sending enabled/disabled
            'reminder_auto_send' => true,
        ];

        foreach ($companies as $company) {
            foreach ($defaultSettings as $key => $value) {
                $existing = $settingsService->get($key, $company->id);
                
                // Only set if not already exists
                if ($existing === null) {
                    $type = is_bool($value) ? 'boolean' : (is_numeric($value) ? 'number' : 'string');
                    $settingsService->setCompany($key, $value, $company->id, $type);
                }
            }
        }

        $this->command->info('✅ Mahnung settings seeded for ' . $companies->count() . ' companies');
    }
}
