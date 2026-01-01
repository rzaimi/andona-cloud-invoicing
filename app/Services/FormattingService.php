<?php

namespace App\Services;

use App\Modules\Company\Models\Company;
use Carbon\Carbon;

class FormattingService
{
    protected $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Format currency amount using company settings
     */
    public function formatCurrency(float $amount, ?string $companyId = null, ?string $currency = null): string
    {
        if (!$companyId) {
            return number_format($amount, 2, ',', '.') . ' ' . ($currency ?? 'EUR');
        }

        $settings = $this->settingsService->getAll($companyId);
        $currency = $currency ?? $settings['currency'] ?? 'EUR';
        $decimalSeparator = $settings['decimal_separator'] ?? ',';
        $thousandsSeparator = $settings['thousands_separator'] ?? '.';

        return number_format($amount, 2, $decimalSeparator, $thousandsSeparator) . ' ' . $currency;
    }

    /**
     * Format number using company settings (without currency symbol)
     */
    public function formatNumber(float $amount, ?string $companyId = null, int $decimals = 2): string
    {
        if (!$companyId) {
            return number_format($amount, $decimals, ',', '.');
        }

        $settings = $this->settingsService->getAll($companyId);
        $decimalSeparator = $settings['decimal_separator'] ?? ',';
        $thousandsSeparator = $settings['thousands_separator'] ?? '.';

        return number_format($amount, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    /**
     * Format date using company settings
     */
    public function formatDate($date, ?string $companyId = null, ?string $format = null): string
    {
        if (!$date) {
            return '';
        }

        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        if (!$companyId) {
            return $carbon->format($format ?? 'd.m.Y');
        }

        $settings = $this->settingsService->getAll($companyId);
        $dateFormat = $format ?? $settings['date_format'] ?? 'd.m.Y';

        return $carbon->format($dateFormat);
    }

    /**
     * Format date and time using company settings
     */
    public function formatDateTime($dateTime, ?string $companyId = null, ?string $format = null): string
    {
        if (!$dateTime) {
            return '';
        }

        $carbon = $dateTime instanceof Carbon ? $dateTime : Carbon::parse($dateTime);

        if (!$companyId) {
            return $carbon->format($format ?? 'd.m.Y H:i');
        }

        $settings = $this->settingsService->getAll($companyId);
        $dateFormat = $settings['date_format'] ?? 'd.m.Y';
        
        // Append time format
        $dateTimeFormat = $format ?? $dateFormat . ' H:i';

        return $carbon->format($dateTimeFormat);
    }

    /**
     * Get currency symbol or code
     */
    public function getCurrency(?string $companyId = null): string
    {
        if (!$companyId) {
            return 'EUR';
        }

        $settings = $this->settingsService->getAll($companyId);
        return $settings['currency'] ?? 'EUR';
    }

    /**
     * Get date format
     */
    public function getDateFormat(?string $companyId = null): string
    {
        if (!$companyId) {
            return 'd.m.Y';
        }

        $settings = $this->settingsService->getAll($companyId);
        return $settings['date_format'] ?? 'd.m.Y';
    }

    /**
     * Get decimal separator
     */
    public function getDecimalSeparator(?string $companyId = null): string
    {
        if (!$companyId) {
            return ',';
        }

        $settings = $this->settingsService->getAll($companyId);
        return $settings['decimal_separator'] ?? ',';
    }

    /**
     * Get thousands separator
     */
    public function getThousandsSeparator(?string $companyId = null): string
    {
        if (!$companyId) {
            return '.';
        }

        $settings = $this->settingsService->getAll($companyId);
        return $settings['thousands_separator'] ?? '.';
    }
}

