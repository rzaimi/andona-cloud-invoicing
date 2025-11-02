<?php

namespace App\Http\Controllers;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CompanyWizardController extends Controller
{
    protected $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Start the wizard (Step 1)
     */
    public function start()
    {
        // Clear any existing wizard data
        session()->forget('company_wizard');

        // Initialize wizard with defaults
        $wizardData = [
            'step' => 1,
            'company_info' => [
                'name' => '',
                'email' => '',
                'phone' => '',
                'address' => '',
                'postal_code' => '',
                'city' => '',
                'country' => 'Deutschland',
                'tax_number' => '',
                'vat_number' => '',
                'website' => '',
            ],
            'email_settings' => [
                'smtp_host' => '',
                'smtp_port' => 587,
                'smtp_username' => '',
                'smtp_password' => '',
                'smtp_encryption' => 'tls',
                'smtp_from_address' => '',
                'smtp_from_name' => '',
            ],
            'invoice_settings' => [
                'invoice_prefix' => 'RE-',
                'offer_prefix' => 'AN-',
                'customer_prefix' => 'KD-',
                'currency' => 'EUR',
                'tax_rate' => 0.19,
                'reduced_tax_rate' => 0.07,
                'payment_terms' => 14,
                'offer_validity_days' => 30,
                'date_format' => 'd.m.Y',
                'decimal_separator' => ',',
                'thousands_separator' => '.',
            ],
            'mahnung_settings' => [
                'reminder_friendly_days' => 7,
                'reminder_mahnung1_days' => 14,
                'reminder_mahnung2_days' => 21,
                'reminder_mahnung3_days' => 30,
                'reminder_inkasso_days' => 45,
                'reminder_mahnung1_fee' => 5.00,
                'reminder_mahnung2_fee' => 10.00,
                'reminder_mahnung3_fee' => 15.00,
                'reminder_interest_rate' => 9.00,
                'reminder_auto_send' => true,
            ],
            'banking_info' => [
                'bank_name' => '',
                'iban' => '',
                'bic' => '',
                'account_holder' => '',
            ],
            'first_user' => [
                'create_user' => false,
                'name' => '',
                'email' => '',
                'password' => '',
                'send_welcome_email' => true,
            ],
        ];

        session()->put('company_wizard', $wizardData);

        return Inertia::render('companies/wizard', [
            'wizardData' => $wizardData,
        ]);
    }

    /**
     * Update wizard data and navigate to next/previous step
     */
    public function updateStep(Request $request)
    {
        $wizardData = session('company_wizard', []);
        $currentStep = $request->input('step', 1);
        $action = $request->input('action', 'next'); // next, back, or save

        // Update wizard data with current form data first
        $stepKey = $this->getStepKey($currentStep);
        if ($stepKey) {
            $wizardData[$stepKey] = $request->input($stepKey, $wizardData[$stepKey] ?? []);
        }

        // Validate current step data (only if going forward)
        if ($action === 'next') {
            $validator = \Illuminate\Support\Facades\Validator::make(
                $request->all(),
                $this->getValidationRules($currentStep)
            );

            if ($validator->fails()) {
                // On validation error, stay on current step
                $wizardData['step'] = $currentStep;
                session()->put('company_wizard', $wizardData);
                
                // Return Inertia response with errors
                return Inertia::render('companies/wizard', [
                    'wizardData' => $wizardData,
                    'errors' => $validator->errors()->toArray(),
                ]);
            }

            // If validation passes, update wizard data
            $validated = $validator->validated();
            if ($stepKey && isset($validated[$stepKey])) {
                $wizardData[$stepKey] = array_merge(
                    $wizardData[$stepKey] ?? [],
                    $validated[$stepKey]
                );
            }
        }

        // Determine next step
        if ($action === 'next' && $currentStep < 7) {
            $wizardData['step'] = $currentStep + 1;
        } elseif ($action === 'back' && $currentStep > 1) {
            $wizardData['step'] = $currentStep - 1;
        } else {
            $wizardData['step'] = $currentStep;
        }

        session()->put('company_wizard', $wizardData);

        return Inertia::render('companies/wizard', [
            'wizardData' => $wizardData,
        ]);
    }

    /**
     * Complete the wizard and create company
     */
    public function complete(Request $request)
    {
        $wizardData = session('company_wizard', []);

        if (empty($wizardData)) {
            return Inertia::location(route('companies.wizard.start'));
        }

        try {
            DB::beginTransaction();

            // Create company
            $company = Company::create([
                'id' => Str::uuid(),
                'name' => $wizardData['company_info']['name'] ?? '',
                'email' => $wizardData['company_info']['email'] ?? '',
                'phone' => $wizardData['company_info']['phone'] ?? null,
                'address' => $wizardData['company_info']['address'] ?? null,
                'postal_code' => $wizardData['company_info']['postal_code'] ?? null,
                'city' => $wizardData['company_info']['city'] ?? null,
                'country' => $wizardData['company_info']['country'] ?? 'Deutschland',
                'tax_number' => $wizardData['company_info']['tax_number'] ?? null,
                'vat_number' => $wizardData['company_info']['vat_number'] ?? null,
                'website' => $wizardData['company_info']['website'] ?? null,
                
                // SMTP settings
                'smtp_host' => $wizardData['email_settings']['smtp_host'] ?? null,
                'smtp_port' => $wizardData['email_settings']['smtp_port'] ?? 587,
                'smtp_username' => $wizardData['email_settings']['smtp_username'] ?? null,
                'smtp_password' => $wizardData['email_settings']['smtp_password'] ?? null,
                'smtp_encryption' => $wizardData['email_settings']['smtp_encryption'] ?? 'tls',
                'smtp_from_address' => $wizardData['email_settings']['smtp_from_address'] ?? null,
                'smtp_from_name' => $wizardData['email_settings']['smtp_from_name'] ?? null,
                
                // Banking
                'iban' => $wizardData['banking_info']['iban'] ?? null,
                'bic' => $wizardData['banking_info']['bic'] ?? null,
            ]);

            // Save invoice settings
            if (isset($wizardData['invoice_settings']) && is_array($wizardData['invoice_settings'])) {
                foreach ($wizardData['invoice_settings'] as $key => $value) {
                    $type = $this->getSettingType($key, $value);
                    $this->settingsService->setCompany($key, $value, $company->id, $type);
                }
            }

            // Save Mahnung settings
            if (isset($wizardData['mahnung_settings']) && is_array($wizardData['mahnung_settings'])) {
                foreach ($wizardData['mahnung_settings'] as $key => $value) {
                    $type = $this->getSettingType($key, $value);
                    $this->settingsService->setCompany($key, $value, $company->id, $type);
                }
            }

            // Create first user if requested
            if (isset($wizardData['first_user']['create_user']) && 
                $wizardData['first_user']['create_user'] && 
                !empty($wizardData['first_user']['email'])) {
                $user = User::create([
                    'id' => Str::uuid(),
                    'name' => $wizardData['first_user']['name'],
                    'email' => $wizardData['first_user']['email'],
                    'password' => Hash::make($wizardData['first_user']['password']),
                    'company_id' => $company->id,
                    'role' => 'admin',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]);

                // Assign admin role (Spatie)
                $user->assignRole('admin');

                // TODO: Send welcome email if requested
            }

            DB::commit();

            // Clear wizard data
            session()->forget('company_wizard');

            return Inertia::location(route('companies.index'));

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Company wizard failed: ' . $e->getMessage());
            
            // Return to wizard with error
            $wizardData['step'] = 7;
            session()->put('company_wizard', $wizardData);
            
            return Inertia::render('companies/wizard', [
                'wizardData' => $wizardData,
                'errors' => ['general' => $e->getMessage()],
            ]);
        }
    }

    /**
     * Cancel wizard and clear session
     */
    public function cancel()
    {
        session()->forget('company_wizard');
        return redirect()->route('companies.index')
            ->with('info', 'Wizard abgebrochen.');
    }

    /**
     * Get validation rules for a specific step
     */
    protected function getValidationRules(int $step)
    {
        $rules = [];

        switch ($step) {
            case 1: // Company Info
                $rules = [
                    'company_info.name' => 'required|string|max:255',
                    'company_info.email' => 'required|email|max:255|unique:companies,email',
                    'company_info.phone' => 'nullable|string|max:50',
                    'company_info.address' => 'nullable|string|max:255',
                    'company_info.postal_code' => 'nullable|string|max:20',
                    'company_info.city' => 'nullable|string|max:100',
                    'company_info.country' => 'nullable|string|max:100',
                    'company_info.tax_number' => 'nullable|string|max:100',
                    'company_info.vat_number' => 'nullable|string|max:100',
                    'company_info.website' => 'nullable|url|max:255',
                ];
                break;

            case 2: // Email Settings
                $rules = [
                    'email_settings.smtp_host' => 'required|string|max:255',
                    'email_settings.smtp_port' => 'required|integer|min:1|max:65535',
                    'email_settings.smtp_username' => 'required|string|max:255',
                    'email_settings.smtp_password' => 'required|string|max:255',
                    'email_settings.smtp_encryption' => 'required|in:tls,ssl,none',
                    'email_settings.smtp_from_address' => 'required|email|max:255',
                    'email_settings.smtp_from_name' => 'required|string|max:255',
                ];
                break;

            case 3: // Invoice Settings
                $rules = [
                    'invoice_settings.invoice_prefix' => 'required|string|max:10',
                    'invoice_settings.offer_prefix' => 'required|string|max:10',
                    'invoice_settings.customer_prefix' => 'nullable|string|max:10',
                    'invoice_settings.currency' => 'required|in:EUR,USD,GBP,CHF',
                    'invoice_settings.tax_rate' => 'required|numeric|min:0|max:1',
                    'invoice_settings.reduced_tax_rate' => 'nullable|numeric|min:0|max:1',
                    'invoice_settings.payment_terms' => 'required|integer|min:1|max:365',
                    'invoice_settings.offer_validity_days' => 'required|integer|min:1|max:365',
                    'invoice_settings.date_format' => 'required|in:d.m.Y,Y-m-d,m/d/Y',
                    'invoice_settings.decimal_separator' => 'required|string|max:1',
                    'invoice_settings.thousands_separator' => 'required|string|max:1',
                ];
                break;

            case 4: // Mahnung Settings
                $rules = [
                    'mahnung_settings.reminder_friendly_days' => 'required|integer|min:1|max:90',
                    'mahnung_settings.reminder_mahnung1_days' => 'required|integer|min:1|max:90',
                    'mahnung_settings.reminder_mahnung2_days' => 'required|integer|min:1|max:90',
                    'mahnung_settings.reminder_mahnung3_days' => 'required|integer|min:1|max:90',
                    'mahnung_settings.reminder_inkasso_days' => 'required|integer|min:1|max:365',
                    'mahnung_settings.reminder_mahnung1_fee' => 'required|numeric|min:0|max:100',
                    'mahnung_settings.reminder_mahnung2_fee' => 'required|numeric|min:0|max:100',
                    'mahnung_settings.reminder_mahnung3_fee' => 'required|numeric|min:0|max:100',
                    'mahnung_settings.reminder_interest_rate' => 'required|numeric|min:0|max:20',
                    'mahnung_settings.reminder_auto_send' => 'required|boolean',
                ];
                break;

            case 5: // Banking Info
                $rules = [
                    'banking_info.bank_name' => 'nullable|string|max:255',
                    'banking_info.iban' => 'required|string|max:34',
                    'banking_info.bic' => 'required|string|max:11',
                    'banking_info.account_holder' => 'nullable|string|max:255',
                ];
                break;

            case 6: // First User
                $rules = [
                    'first_user.create_user' => 'required|boolean',
                    'first_user.name' => 'required_if:first_user.create_user,true|string|max:255',
                    'first_user.email' => 'required_if:first_user.create_user,true|email|max:255|unique:users,email',
                    'first_user.password' => 'required_if:first_user.create_user,true|string|min:8',
                    'first_user.send_welcome_email' => 'nullable|boolean',
                ];
                break;
        }

        return $rules;
    }

    /**
     * Get step key for wizard data
     */
    protected function getStepKey(int $step): ?string
    {
        return match($step) {
            1 => 'company_info',
            2 => 'email_settings',
            3 => 'invoice_settings',
            4 => 'mahnung_settings',
            5 => 'banking_info',
            6 => 'first_user',
            default => null,
        };
    }

    /**
     * Determine setting type
     */
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
}
