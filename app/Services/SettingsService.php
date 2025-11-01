<?php

namespace App\Services;

use App\Modules\Company\Models\Company;
use App\Modules\Company\Models\CompanySetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SettingsService
{
    /**
     * Get a setting value for a specific company
     * Falls back to global setting if company setting doesn't exist
     */
    public function get(string $key, ?string $companyId = null, $default = null)
    {
        $cacheKey = $companyId 
            ? "setting.{$companyId}.{$key}" 
            : "setting.global.{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($key, $companyId, $default) {
            // If company ID is provided, try company setting first
            if ($companyId) {
                $company = Company::find($companyId);
                if ($company) {
                    $companySetting = $company->settings()->where('key', $key)->first();
                    if ($companySetting) {
                        // Use getRawOriginal to get the raw value from database, not the accessor
                        $rawValue = $companySetting->getRawOriginal('value');
                        return $this->castValue($rawValue, $companySetting->type);
                    }
                }
            }

            // Fall back to global setting (check if table exists first)
            if (Schema::hasTable('global_settings')) {
                $globalSetting = DB::table('global_settings')
                    ->where('key', $key)
                    ->first();

                if ($globalSetting) {
                    return $this->castValue($globalSetting->value, $globalSetting->type);
                }
            }

            // Fall back to default settings
            $defaults = $this->getDefaultSettings();
            return $defaults[$key] ?? $default;
        });
    }

    /**
     * Set a company-specific setting
     */
    public function setCompany(string $key, $value, ?string $companyId, string $type = 'string', ?string $description = null): void
    {
        if (!$companyId) {
            throw new \InvalidArgumentException('Company ID is required for company settings');
        }

        $company = Company::find($companyId);
        if (!$company) {
            throw new \InvalidArgumentException("Company with ID {$companyId} not found");
        }

        $company->setSetting($key, $value, $type, $description);
        
        // Clear cache
        Cache::forget("setting.{$companyId}.{$key}");
    }

    /**
     * Set a global setting (applies to all companies)
     */
    public function setGlobal(string $key, $value, string $type = 'string', ?string $description = null): void
    {
        if (!Schema::hasTable('global_settings')) {
            throw new \RuntimeException('Global settings table does not exist. Please run migrations.');
        }

        $castedValue = $this->castValueForStorage($value, $type);

        DB::table('global_settings')->updateOrInsert(
            ['key' => $key],
            [
                'value' => $castedValue,
                'type' => $type,
                'description' => $description,
                'updated_at' => now(),
            ]
        );

        // Clear cache for all companies that might use this global setting
        Cache::forget("setting.global.{$key}");
    }

    /**
     * Get all settings for a company (company settings + global settings merged)
     */
    public function getAll(?string $companyId = null): array
    {
        $defaults = $this->getDefaultSettings();
        $settings = [];

        // Get all global settings (check if table exists first)
        if (Schema::hasTable('global_settings')) {
            $globalSettings = DB::table('global_settings')->get();
            foreach ($globalSettings as $setting) {
                $settings[$setting->key] = $this->castValue($setting->value, $setting->type);
            }
        }

        // Override with company-specific settings if company ID provided
        if ($companyId) {
            $company = Company::find($companyId);
            if ($company) {
                $companySettings = $company->settings()->get();
                if ($companySettings && $companySettings->isNotEmpty()) {
                    foreach ($companySettings as $setting) {
                        // Use getRawOriginal to get the raw value from database, not the accessor
                        $rawValue = $setting->getRawOriginal('value');
                        $settings[$setting->key] = $this->castValue($rawValue, $setting->type);
                    }
                }
            }
        }

        // Merge with defaults (defaults have lowest priority)
        return array_merge($defaults, $settings);
    }

    /**
     * Get default settings
     */
    public function getDefaultSettings(): array
    {
        return [
            'currency' => 'EUR',
            'tax_rate' => 0.19,
            'reduced_tax_rate' => 0.07,
            'invoice_prefix' => 'RE-',
            'offer_prefix' => 'AN-',
            'customer_prefix' => 'KU-',
            'date_format' => 'd.m.Y',
            'payment_terms' => 14,
            'language' => 'de',
            'timezone' => 'Europe/Berlin',
            'decimal_separator' => ',',
            'thousands_separator' => '.',
            'invoice_footer' => 'Vielen Dank für Ihr Vertrauen!',
            'offer_footer' => 'Wir freuen uns auf Ihre Rückmeldung!',
            'payment_methods' => ['Überweisung', 'SEPA-Lastschrift', 'PayPal'],
            'offer_validity_days' => 30,
        ];
    }

    /**
     * Clear all settings cache for a company
     */
    public function clearCompanyCache(?string $companyId = null): void
    {
        if ($companyId) {
            $company = Company::find($companyId);
            if ($company) {
                $companySettings = $company->settings()->get();
                if ($companySettings && $companySettings->isNotEmpty()) {
                    foreach ($companySettings as $setting) {
                        Cache::forget("setting.{$companyId}.{$setting->key}");
                    }
                }
            }
        }
    }

    /**
     * Clear global settings cache
     */
    public function clearGlobalCache(): void
    {
        if (Schema::hasTable('global_settings')) {
            $globalSettings = DB::table('global_settings')->get();
            foreach ($globalSettings as $setting) {
                Cache::forget("setting.global.{$setting->key}");
            }
        }
    }

    /**
     * Cast value based on type
     */
    protected function castValue($value, string $type)
    {
        // Handle null values
        if ($value === null) {
            return null;
        }

        return match($type) {
            'integer' => (int) $value,
            'decimal', 'float' => (float) $value,
            'boolean' => (bool) $value,
            'json', 'array' => is_string($value) 
                ? json_decode($value, true) 
                : (is_array($value) ? $value : json_decode((string) $value, true)),
            default => $value,
        };
    }

    /**
     * Cast value for storage
     */
    protected function castValueForStorage($value, string $type): string
    {
        return match($type) {
            'json', 'array' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };
    }
}

