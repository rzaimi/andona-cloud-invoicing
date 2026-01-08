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
        try {
            $companyId = $this->getEffectiveCompanyId();
            
            if (!$companyId) {
                \Log::error('SettingsController::index - No company ID found for user', [
                    'user_id' => $request->user()?->id,
                    'user_email' => $request->user()?->email,
                ]);
                abort(404, 'Company not found. Please ensure your account is associated with a company.');
            }

            $company = \App\Modules\Company\Models\Company::find($companyId);

            if (!$company) {
                \Log::error('SettingsController::index - Company not found', [
                    'company_id' => $companyId,
                    'user_id' => $request->user()?->id,
                ]);
                abort(404, 'Company not found. Please contact support.');
            }
        } catch (\Exception $e) {
            \Log::error('SettingsController::index - Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        // Get all settings for this company (company-specific + global + defaults)
        $settings = $this->settingsService->getAll($companyId);
        
        // Get active tab from request
        $activeTab = $request->get('tab', 'company');

        // Load all settings data for unified page
        $emailSettings = [
            'smtp_host' => $company->smtp_host ?? '',
            'smtp_port' => $company->smtp_port ?? 587,
            'smtp_username' => $company->smtp_username ?? '',
            'smtp_password' => $company->smtp_password ? '••••••••' : '',
            'smtp_encryption' => $company->smtp_encryption ?? 'tls',
            'smtp_from_address' => $company->smtp_from_address ?? $company->email,
            'smtp_from_name' => $company->smtp_from_name ?? $company->name,
        ];

        $reminderSettings = [
            'reminder_friendly_days' => $settings['reminder_friendly_days'] ?? 7,
            'reminder_mahnung1_days' => $settings['reminder_mahnung1_days'] ?? 14,
            'reminder_mahnung2_days' => $settings['reminder_mahnung2_days'] ?? 21,
            'reminder_mahnung3_days' => $settings['reminder_mahnung3_days'] ?? 30,
            'reminder_inkasso_days' => $settings['reminder_inkasso_days'] ?? 45,
            'reminder_mahnung1_fee' => $settings['reminder_mahnung1_fee'] ?? 5.00,
            'reminder_mahnung2_fee' => $settings['reminder_mahnung2_fee'] ?? 10.00,
            'reminder_mahnung3_fee' => $settings['reminder_mahnung3_fee'] ?? 15.00,
            'reminder_interest_rate' => $settings['reminder_interest_rate'] ?? 9.00,
            'reminder_auto_send' => $settings['reminder_auto_send'] ?? true,
        ];

        $erechnungSettings = [
            'erechnung_enabled' => $this->settingsService->get('erechnung_enabled', false, $companyId),
            'xrechnung_enabled' => $this->settingsService->get('xrechnung_enabled', true, $companyId),
            'zugferd_enabled' => $this->settingsService->get('zugferd_enabled', true, $companyId),
            'zugferd_profile' => $this->settingsService->get('zugferd_profile', 'EN16931', $companyId),
            'business_process_id' => $this->settingsService->get('business_process_id', null, $companyId),
            'electronic_address_scheme' => $this->settingsService->get('electronic_address_scheme', 'EM', $companyId),
            'electronic_address' => $this->settingsService->get('electronic_address', null, $companyId),
        ];

        $notificationSettings = [
            'notify_on_invoice_created' => $settings['notify_on_invoice_created'] ?? false,
            'notify_on_invoice_sent' => $settings['notify_on_invoice_sent'] ?? true,
            'notify_on_payment_received' => $settings['notify_on_payment_received'] ?? true,
            'notify_on_offer_created' => $settings['notify_on_offer_created'] ?? false,
            'notify_on_offer_accepted' => $settings['notify_on_offer_accepted'] ?? true,
            'notify_on_offer_rejected' => $settings['notify_on_offer_rejected'] ?? false,
            'email_notifications_enabled' => $settings['email_notifications_enabled'] ?? true,
        ];

        $paymentMethodSettings = [
            'payment_methods' => $settings['payment_methods'] ?? ['Überweisung', 'SEPA-Lastschrift', 'PayPal'],
            'default_payment_method' => $settings['default_payment_method'] ?? 'Überweisung',
        ];

        $datevSettings = [
            'datev_revenue_account' => $this->settingsService->get('datev_revenue_account', '8400', $companyId),
            'datev_receivables_account' => $this->settingsService->get('datev_receivables_account', '1200', $companyId),
            'datev_bank_account' => $this->settingsService->get('datev_bank_account', '1800', $companyId),
            'datev_expenses_account' => $this->settingsService->get('datev_expenses_account', '6000', $companyId),
            'datev_vat_account' => $this->settingsService->get('datev_vat_account', '1776', $companyId),
            'datev_customer_account_prefix' => $this->settingsService->get('datev_customer_account_prefix', '1000', $companyId),
        ];

        // Load email logs if on email-logs tab
        $emailLogs = null;
        $emailLogsStats = null;
        $emailLogsFilters = null;
        if ($activeTab === 'email-logs') {
            $query = \App\Models\EmailLog::forCompany($companyId)
                ->with(['customer:id,name,email'])
                ->orderBy('sent_at', 'desc');

            if ($request->type && $request->type !== 'all') {
                $query->where('type', $request->type);
            }

            if ($request->status && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('recipient_email', 'like', "%{$request->search}%")
                        ->orWhere('recipient_name', 'like', "%{$request->search}%")
                        ->orWhere('subject', 'like', "%{$request->search}%");
                });
            }

            $emailLogs = $query->paginate(20)->withQueryString();
            
            // Calculate statistics
            $emailLogsStats = [
                'total' => \App\Models\EmailLog::forCompany($companyId)->count(),
                'invoice' => \App\Models\EmailLog::forCompany($companyId)->where('type', 'invoice')->count(),
                'offer' => \App\Models\EmailLog::forCompany($companyId)->where('type', 'offer')->count(),
                'mahnung' => \App\Models\EmailLog::forCompany($companyId)->where('type', 'mahnung')->count(),
                'failed' => \App\Models\EmailLog::forCompany($companyId)->where('status', 'failed')->count(),
            ];
            
            $emailLogsFilters = $request->only(['type', 'status', 'search']);
        }

        return Inertia::render('settings/index', [
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
            'emailSettings' => $emailSettings,
            'reminderSettings' => $reminderSettings,
            'erechnungSettings' => $erechnungSettings,
            'notificationSettings' => $notificationSettings,
            'paymentMethodSettings' => $paymentMethodSettings,
            'datevSettings' => $datevSettings,
            'emailLogs' => $emailLogs,
            'emailLogsStats' => $emailLogsStats,
            'emailLogsFilters' => $emailLogsFilters,
            'user' => $request->user(),
            'activeTab' => $activeTab,
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

    public function email(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();
        $company = \App\Modules\Company\Models\Company::find($companyId);
        
        return Inertia::render('settings/email', [
            'settings' => [
                'smtp_host' => $company->smtp_host ?? '',
                'smtp_port' => $company->smtp_port ?? 587,
                'smtp_username' => $company->smtp_username ?? '',
                'smtp_password' => $company->smtp_password ? '••••••••' : '', // Mask password
                'smtp_encryption' => $company->smtp_encryption ?? 'tls',
                'smtp_from_address' => $company->smtp_from_address ?? $company->email,
                'smtp_from_name' => $company->smtp_from_name ?? $company->name,
            ],
        ]);
    }

    public function updateEmail(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();
        $company = \App\Modules\Company\Models\Company::find($companyId);
        
        $validated = $request->validate([
            'smtp_host' => 'required|string|max:255',
            'smtp_port' => 'required|integer|min:1|max:65535',
            'smtp_username' => 'required|string|max:255',
            'smtp_password' => 'nullable|string|max:255', // nullable if not changing
            'smtp_encryption' => 'required|string|in:tls,ssl,none',
            'smtp_from_address' => 'required|email|max:255',
            'smtp_from_name' => 'required|string|max:255',
        ]);

        // Don't update password if it's the masked value
        if ($validated['smtp_password'] === '••••••••') {
            unset($validated['smtp_password']);
        }

        // Convert 'none' to null for encryption
        if ($validated['smtp_encryption'] === 'none') {
            $validated['smtp_encryption'] = null;
        }

        // Use helper method to save SMTP settings (normalized to company_settings)
        $company->setSmtpSettings($validated);

        return redirect()->route('settings.email')
            ->with('success', 'E-Mail Einstellungen wurden erfolgreich aktualisiert.');
    }

    public function reminders(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();
        $settings = $this->settingsService->getAll($companyId);
        
        return Inertia::render('settings/reminders', [
            'settings' => [
                'reminder_friendly_days' => $settings['reminder_friendly_days'] ?? 7,
                'reminder_mahnung1_days' => $settings['reminder_mahnung1_days'] ?? 14,
                'reminder_mahnung2_days' => $settings['reminder_mahnung2_days'] ?? 21,
                'reminder_mahnung3_days' => $settings['reminder_mahnung3_days'] ?? 30,
                'reminder_inkasso_days' => $settings['reminder_inkasso_days'] ?? 45,
                'reminder_mahnung1_fee' => $settings['reminder_mahnung1_fee'] ?? 5.00,
                'reminder_mahnung2_fee' => $settings['reminder_mahnung2_fee'] ?? 10.00,
                'reminder_mahnung3_fee' => $settings['reminder_mahnung3_fee'] ?? 15.00,
                'reminder_interest_rate' => $settings['reminder_interest_rate'] ?? 9.00,
                'reminder_auto_send' => $settings['reminder_auto_send'] ?? true,
            ],
        ]);
    }

    public function updateReminders(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();
        
        $validated = $request->validate([
            'reminder_friendly_days' => 'required|integer|min:1|max:90',
            'reminder_mahnung1_days' => 'required|integer|min:1|max:90',
            'reminder_mahnung2_days' => 'required|integer|min:1|max:90',
            'reminder_mahnung3_days' => 'required|integer|min:1|max:90',
            'reminder_inkasso_days' => 'required|integer|min:1|max:365',
            'reminder_mahnung1_fee' => 'required|numeric|min:0|max:100',
            'reminder_mahnung2_fee' => 'required|numeric|min:0|max:100',
            'reminder_mahnung3_fee' => 'required|numeric|min:0|max:100',
            'reminder_interest_rate' => 'required|numeric|min:0|max:20',
            'reminder_auto_send' => 'required|boolean',
        ]);

        // Update each setting using the service
        foreach ($validated as $key => $value) {
            $type = $this->getSettingType($key, $value);
            $this->settingsService->setCompany($key, $value, $companyId, $type);
        }

        // Clear cache
        $this->settingsService->clearCompanyCache($companyId);

        return redirect()->route('settings.reminders')
            ->with('success', 'Mahnungseinstellungen wurden erfolgreich aktualisiert.');
    }

    public function emailLogs(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();
        
        $query = \App\Models\EmailLog::forCompany($companyId)
            ->with(['customer:id,name,email'])
            ->orderBy('sent_at', 'desc');

        // Filter by type
        if ($request->type && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Search by recipient or subject
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('recipient_email', 'like', "%{$request->search}%")
                    ->orWhere('recipient_name', 'like', "%{$request->search}%")
                    ->orWhere('subject', 'like', "%{$request->search}%");
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        // Calculate statistics
        $stats = [
            'total' => \App\Models\EmailLog::forCompany($companyId)->count(),
            'invoice' => \App\Models\EmailLog::forCompany($companyId)->where('type', 'invoice')->count(),
            'offer' => \App\Models\EmailLog::forCompany($companyId)->where('type', 'offer')->count(),
            'mahnung' => \App\Models\EmailLog::forCompany($companyId)->where('type', 'mahnung')->count(),
            'failed' => \App\Models\EmailLog::forCompany($companyId)->where('status', 'failed')->count(),
        ];

        return Inertia::render('settings/email-logs', [
            'logs' => $logs,
            'filters' => $request->only(['type', 'status', 'search']),
            'stats' => $stats,
        ]);
    }

    /**
     * Show E-Rechnung settings page
     */
    public function erechnung(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();
        
        $settings = [
            'erechnung_enabled' => $this->settingsService->get('erechnung_enabled', false, $companyId),
            'xrechnung_enabled' => $this->settingsService->get('xrechnung_enabled', true, $companyId),
            'zugferd_enabled' => $this->settingsService->get('zugferd_enabled', true, $companyId),
            'zugferd_profile' => $this->settingsService->get('zugferd_profile', 'EN16931', $companyId),
            'business_process_id' => $this->settingsService->get('business_process_id', null, $companyId),
            'electronic_address_scheme' => $this->settingsService->get('electronic_address_scheme', 'EM', $companyId),
            'electronic_address' => $this->settingsService->get('electronic_address', null, $companyId),
        ];

        return Inertia::render('settings/erechnung', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update E-Rechnung settings
     */
    public function updateErechnung(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();

        $validated = $request->validate([
            'erechnung_enabled' => 'required|boolean',
            'xrechnung_enabled' => 'required|boolean',
            'zugferd_enabled' => 'required|boolean',
            'zugferd_profile' => 'required|string|in:MINIMUM,BASIC,EN16931,EXTENDED,XRECHNUNG',
            'business_process_id' => 'nullable|string|max:255',
            'electronic_address_scheme' => 'nullable|string|max:50',
            'electronic_address' => 'nullable|string|max:255',
        ]);

        foreach ($validated as $key => $value) {
            $type = is_bool($value) ? 'boolean' : 'string';
            $this->settingsService->setCompany($key, $value, $companyId, $type);
        }

        $this->settingsService->clearCompanyCache($companyId);

        return redirect()->route('settings.erechnung')
            ->with('success', 'E-Rechnung Einstellungen wurden erfolgreich aktualisiert.');
    }

    /**
     * Update Notifications settings
     */
    public function updateNotifications(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();
        
        $validated = $request->validate([
            'notify_on_invoice_created' => 'boolean',
            'notify_on_invoice_sent' => 'boolean',
            'notify_on_payment_received' => 'boolean',
            'notify_on_offer_created' => 'boolean',
            'notify_on_offer_accepted' => 'boolean',
            'notify_on_offer_rejected' => 'boolean',
            'email_notifications_enabled' => 'boolean',
        ]);

        // Update each setting using the service
        foreach ($validated as $key => $value) {
            $this->settingsService->setCompany($key, $value, $companyId, 'boolean');
        }

        // Clear cache
        $this->settingsService->clearCompanyCache($companyId);

        return redirect()->route('settings.index', ['tab' => 'notifications'])
            ->with('success', 'Benachrichtigungseinstellungen wurden erfolgreich aktualisiert.');
    }

    /**
     * Update Payment Methods settings
     */
    public function updatePaymentMethods(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();
        
        $validated = $request->validate([
            'payment_methods' => 'required|array|min:1',
            'payment_methods.*' => 'required|string|max:255',
            'default_payment_method' => 'required|string|max:255',
        ]);

        // Ensure default_payment_method is in the payment_methods array
        if (!in_array($validated['default_payment_method'], $validated['payment_methods'])) {
            return redirect()->back()
                ->withErrors(['default_payment_method' => 'Die Standard-Zahlungsmethode muss in der Liste der verfügbaren Zahlungsmethoden enthalten sein.'])
                ->withInput();
        }

        // Update each setting using the service
        $this->settingsService->setCompany('payment_methods', $validated['payment_methods'], $companyId, 'json');
        $this->settingsService->setCompany('default_payment_method', $validated['default_payment_method'], $companyId, 'string');

        // Clear cache
        $this->settingsService->clearCompanyCache($companyId);

        return redirect()->route('settings.index', ['tab' => 'payment-methods'])
            ->with('success', 'Zahlungsmethoden wurden erfolgreich aktualisiert.');
    }
}
