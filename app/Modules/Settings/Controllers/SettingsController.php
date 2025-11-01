<?php

namespace App\Modules\Settings\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use App\Modules\Invoice\Models\InvoiceLayout;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    protected $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    public function index(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();
        $company = \App\Modules\Company\Models\Company::find($companyId);

        // Get all settings for this company (company-specific + global + defaults)
        $settings = $this->settingsService->getAll($companyId);

        return Inertia::render('settings/company', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'address' => $company->address,
                'postal_code' => $company->postal_code,
                'city' => $company->city,
                'country' => $company->country,
                'tax_number' => $company->tax_number,
                'vat_number' => $company->vat_number,
            ],
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();
        
        $validated = $request->validate([
            'currency' => 'required|string|in:USD,EUR,GBP,JPY,CHF',
            'tax_rate' => 'required|numeric|min:0|max:1',
            'reduced_tax_rate' => 'nullable|numeric|min:0|max:1',
            'invoice_prefix' => 'required|string|max:10',
            'offer_prefix' => 'required|string|max:10',
            'customer_prefix' => 'nullable|string|max:10',
            'date_format' => 'required|string|in:Y-m-d,d.m.Y,d/m/Y,m/d/Y',
            'payment_terms' => 'required|integer|min:1|max:365',
            'language' => 'nullable|string|in:de,en,fr,it,es',
            'timezone' => 'nullable|string',
            'decimal_separator' => 'required|string|in:.,,',
            'thousands_separator' => 'required|string|in:.,,',
            'invoice_footer' => 'nullable|string|max:500',
            'offer_footer' => 'nullable|string|max:500',
            'payment_methods' => 'nullable|array',
            'offer_validity_days' => 'required|integer|min:1|max:365',
        ]);

        // Update each setting using the service
        foreach ($validated as $key => $value) {
            $type = $this->getSettingType($key, $value);
            $this->settingsService->setCompany($key, $value, $companyId, $type);
        }

        // Clear cache
        $this->settingsService->clearCompanyCache($companyId);

        return redirect()->route('settings.index')
            ->with('success', 'Firmeneinstellungen wurden erfolgreich aktualisiert.');
    }

    protected function getSettingType(string $key, $value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_int($value)) {
            return 'integer';
        }
        if (is_float($value)) {
            return 'decimal';
        }
        if (is_array($value)) {
            return 'json';
        }
        return 'string';
    }

    public function layouts(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();
        $layouts = InvoiceLayout::forCompany($companyId)
            ->latest()
            ->get();

        return Inertia::render('settings/layouts', [
            'layouts' => $layouts,
        ]);
    }

    public function notifications(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();
        
        // Get notification settings (if any exist)
        $settings = $this->settingsService->getAll($companyId);
        
        return Inertia::render('settings/notifications', [
            'settings' => [
                'notify_on_invoice_created' => $settings['notify_on_invoice_created'] ?? false,
                'notify_on_invoice_sent' => $settings['notify_on_invoice_sent'] ?? true,
                'notify_on_payment_received' => $settings['notify_on_payment_received'] ?? true,
                'notify_on_offer_created' => $settings['notify_on_offer_created'] ?? false,
                'notify_on_offer_accepted' => $settings['notify_on_offer_accepted'] ?? true,
                'notify_on_offer_rejected' => $settings['notify_on_offer_rejected'] ?? false,
                'email_notifications_enabled' => $settings['email_notifications_enabled'] ?? true,
            ],
        ]);
    }

    public function paymentMethods(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();
        
        // Get payment methods settings
        $settings = $this->settingsService->getAll($companyId);
        
        return Inertia::render('settings/payment-methods', [
            'payment_methods' => $settings['payment_methods'] ?? ['Überweisung', 'SEPA-Lastschrift', 'PayPal'],
            'settings' => [
                'default_payment_method' => $settings['default_payment_method'] ?? 'Überweisung',
                'payment_terms' => $settings['payment_terms'] ?? 14,
            ],
        ]);
    }
}
