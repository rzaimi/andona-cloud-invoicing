<?php

namespace App\Modules\Company\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Company\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CompanyController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Super admin with manage_companies can see all companies
        $companies = Company::withCount(['users', 'customers', 'invoices', 'offers'])
            ->with(['users' => function ($query) {
                $query->whereHas('roles', function ($q) {
                    $q->where('name', 'admin');
                })->limit(3);
            }])
            ->paginate(15);

        return Inertia::render('companies/index', [
            'companies' => $companies,
            'can_create' => $user->hasPermissionTo('manage_companies'),
        ]);
    }

    public function create()
    {
        $user = Auth::user();

        // Permission check is handled by route middleware, but double-check here
        if (!$user->hasPermissionTo('manage_companies')) {
            abort(403);
        }

        // Redirect to wizard
        return redirect()->route('companies.wizard');
    }

    public function createWizard(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasPermissionTo('manage_companies')) {
            abort(403);
        }

        // Get wizard data from session if exists
        $wizardData = $request->session()->get('company_wizard', [
            'step' => 1,
            'data' => [
                'company_info' => [],
                'email_settings' => [],
                'invoice_settings' => [
                    'invoice_prefix' => 'RE-',
                    'offer_prefix' => 'AN-',
                    'currency' => 'EUR',
                    'tax_rate' => 0.19,
                    'reduced_tax_rate' => 0.07,
                    'payment_terms' => 14,
                    'offer_validity_days' => 30,
                    'date_format' => 'd.m.Y',
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
                'banking_info' => [],
                'first_user' => [
                    'create_user' => false,
                ],
            ],
        ]);

        return Inertia::render('companies/wizard', [
            'wizardData' => $wizardData,
        ]);
    }

    public function storeWizardStep(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasPermissionTo('manage_companies')) {
            abort(403);
        }

        $step = $request->input('step');
        $data = $request->input('data');

        // Get current wizard data
        $wizardData = $request->session()->get('company_wizard', [
            'step' => 1,
            'data' => [],
        ]);

        // Update the specific step data
        $wizardData['data'][$step] = $data;
        $wizardData['step'] = $request->input('current_step', 1);

        // Save to session
        $request->session()->put('company_wizard', $wizardData);

        return response()->json([
            'success' => true,
            'message' => 'Schritt gespeichert',
        ]);
    }

    public function completeWizard(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasPermissionTo('manage_companies')) {
            abort(403);
        }

        // Get wizard data from session
        $wizardData = $request->session()->get('company_wizard');

        if (!$wizardData) {
            return redirect()->route('companies.wizard')
                ->with('error', 'Wizard-Daten nicht gefunden. Bitte starten Sie erneut.');
        }

        $data = $wizardData['data'];

        try {
            \DB::beginTransaction();

            // 1. Create Company
            $companyInfo = $data['company_info'];
            $bankingInfo = $data['banking_info'] ?? [];
            
            $company = Company::create([
                'name' => $companyInfo['name'],
                'email' => $companyInfo['email'],
                'phone' => $companyInfo['phone'] ?? null,
                'address' => $companyInfo['address'] ?? null,
                'postal_code' => $companyInfo['postal_code'] ?? null,
                'city' => $companyInfo['city'] ?? null,
                'country' => $companyInfo['country'] ?? 'Deutschland',
                'tax_number' => $companyInfo['tax_number'] ?? null,
                'vat_number' => $companyInfo['vat_number'] ?? null,
                'website' => $companyInfo['website'] ?? null,
                'iban' => $bankingInfo['iban'] ?? null,
                'bic' => $bankingInfo['bic'] ?? null,
                'bank_name' => $bankingInfo['bank_name'] ?? null,
                'status' => 'active',
            ]);

            // 2. Set up SMTP Settings
            if (!empty($data['email_settings'])) {
                $emailSettings = $data['email_settings'];
                $company->update([
                    'smtp_host' => $emailSettings['smtp_host'],
                    'smtp_port' => $emailSettings['smtp_port'],
                    'smtp_username' => $emailSettings['smtp_username'],
                    'smtp_password' => $emailSettings['smtp_password'],
                    'smtp_encryption' => $emailSettings['smtp_encryption'] ?? 'tls',
                    'smtp_from_address' => $emailSettings['smtp_from_address'] ?? $company->email,
                    'smtp_from_name' => $emailSettings['smtp_from_name'] ?? $company->name,
                ]);
            }

            // 3. Set up Invoice/Offer Settings
            $settingsService = app(\App\Services\SettingsService::class);
            
            if (!empty($data['invoice_settings'])) {
                foreach ($data['invoice_settings'] as $key => $value) {
                    $type = is_bool($value) ? 'boolean' : (is_numeric($value) ? 'number' : 'string');
                    $settingsService->setCompany($key, $value, $company->id, $type);
                }
            }

            // 4. Set up Mahnung Settings
            if (!empty($data['mahnung_settings'])) {
                foreach ($data['mahnung_settings'] as $key => $value) {
                    $type = is_bool($value) ? 'boolean' : (is_numeric($value) ? 'number' : 'string');
                    $settingsService->setCompany($key, $value, $company->id, $type);
                }
            }

            // 5. Create First User (if requested)
            if (!empty($data['first_user']['create_user']) && $data['first_user']['create_user']) {
                $userData = $data['first_user'];
                
                $firstUser = \App\Modules\User\Models\User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => \Hash::make($userData['password']),
                    'company_id' => $company->id,
                ]);

                // Assign admin role
                $firstUser->assignRole('admin');

                // TODO: Send welcome email if requested
            }

            \DB::commit();

            // Clear wizard session
            $request->session()->forget('company_wizard');

            return redirect()->route('companies.index')
                ->with('success', 'Firma "' . $company->name . '" wurde erfolgreich erstellt und konfiguriert!');

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Company wizard failed: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Fehler beim Erstellen der Firma: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Permission check is handled by route middleware, but double-check here
        if (!$user->hasPermissionTo('manage_companies')) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'postal_code' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'country' => 'required|string|max:100',
            'tax_number' => 'nullable|string|max:50',
            'vat_number' => 'nullable|string|max:50',
            'commercial_register' => 'nullable|string|max:100',
            'managing_director' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'bank_iban' => 'nullable|string|max:50',
            'bank_bic' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('company-logos', 'public');
        }

        $validated['status'] = 'active';

        Company::create($validated);

        return redirect()->route('companies.index')
            ->with('success', 'Firma wurde erfolgreich erstellt.');
    }

    public function show(Company $company)
    {
        $user = Auth::user();

        // Permission check is handled by route middleware, but double-check here
        if (!$user->hasPermissionTo('manage_companies')) {
            abort(403);
        }

        $company->load([
            'users' => function ($query) {
                $query->orderBy('name');
            },
            'settings'
        ]);

        $stats = [
            'users_count' => $company->users()->count(),
            'customers_count' => $company->customers()->count(),
            'invoices_count' => $company->invoices()->count(),
            'offers_count' => $company->offers()->count(),
            'total_revenue' => $company->invoices()->where('status', 'paid')->sum('total'),
            'pending_invoices' => $company->invoices()->whereIn('status', ['sent', 'overdue'])->count(),
        ];

        return Inertia::render('companies/show', [
            'company' => $company,
            'stats' => $stats,
        ]);
    }

    public function edit(Company $company)
    {
        $user = Auth::user();

        // Permission check is handled by route middleware, but double-check here
        if (!$user->hasPermissionTo('manage_companies')) {
            abort(403);
        }

        return Inertia::render('companies/edit', [
            'company' => $company,
        ]);
    }

    public function update(Request $request, Company $company)
    {
        $user = Auth::user();

        // Permission check is handled by route middleware, but double-check here
        if (!$user->hasPermissionTo('manage_companies')) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'postal_code' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'country' => 'required|string|max:100',
            'tax_number' => 'nullable|string|max:50',
            'vat_number' => 'nullable|string|max:50',
            'commercial_register' => 'nullable|string|max:100',
            'managing_director' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'bank_iban' => 'nullable|string|max:50',
            'bank_bic' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'status' => 'required|in:active,inactive',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $validated['logo'] = $request->file('logo')->store('company-logos', 'public');
        }

        $company->update($validated);

        return redirect()->route('companies.show', $company->id)
            ->with('success', 'Firma wurde erfolgreich aktualisiert.');
    }

    public function destroy(Company $company)
    {
        $user = Auth::user();

        // Permission check is handled by route middleware, but double-check here
        if (!$user->hasPermissionTo('manage_companies')) {
            abort(403);
        }

        // Check if company has associated data
        $hasUsers = $company->users()->exists();
        $hasCustomers = $company->customers()->exists();
        $hasInvoices = $company->invoices()->exists();
        $hasOffers = $company->offers()->exists();

        if ($hasUsers || $hasCustomers || $hasInvoices || $hasOffers) {
            return back()->with('error', 'Firma kann nicht gelöscht werden, da sie mit Benutzern, Kunden, Rechnungen oder Angeboten verknüpft ist.');
        }

        // Delete logo if exists
        if ($company->logo) {
            Storage::disk('public')->delete($company->logo);
        }

        $company->delete();

        return redirect()->route('companies.index')
            ->with('success', 'Firma wurde erfolgreich gelöscht.');
    }
}
