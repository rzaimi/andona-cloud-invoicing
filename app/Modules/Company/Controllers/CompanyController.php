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

        return Inertia::render('companies/create');
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

        return redirect()->route('companies.index')
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
