<?php

namespace App\Modules\Customer\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customer\Models\Customer;
use App\Services\ContextService;
use App\Services\NumberFormatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function __construct(ContextService $contextService)
    {
        parent::__construct($contextService);
        $this->middleware('auth');
    }

    /**
     * Display a listing of customers
     */
    public function index(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();

        $allowedSortColumns = ['name', 'email', 'created_at', 'status', 'number'];
        $sortBy    = in_array($request->get('sort_by'), $allowedSortColumns, true) ? $request->get('sort_by') : 'created_at';
        $sortOrder = $request->get('sort_order') === 'asc' ? 'asc' : 'desc';

        $query = Customer::forCompany($companyId);

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $customers = $query->orderBy($sortBy, $sortOrder)
            ->paginate(15)
            ->withQueryString();

        // Aggregate invoice stats per customer in a single query (avoids N+1)
        $customerIds = $customers->pluck('id');
        $invoiceStats = DB::table('invoices')
            ->whereIn('customer_id', $customerIds)
            ->where('company_id', $companyId)
            ->select(
                'customer_id',
                DB::raw('COUNT(*) as total_invoices'),
                DB::raw("SUM(CASE WHEN status = 'paid' THEN total ELSE 0 END) as total_revenue"),
                DB::raw("SUM(CASE WHEN status IN ('sent','overdue') THEN total ELSE 0 END) as outstanding_amount")
            )
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        $customers->getCollection()->transform(function ($customer) use ($invoiceStats) {
            $stats = $invoiceStats->get($customer->id);
            $customer->total_invoices      = $stats?->total_invoices      ?? 0;
            $customer->total_revenue       = $stats?->total_revenue        ?? 0;
            $customer->outstanding_amount  = $stats?->outstanding_amount   ?? 0;
            return $customer;
        });

        return Inertia::render('customers/index', [
            'customers' => $customers,
            'filters'   => $request->only(['search', 'status', 'sort_by', 'sort_order']),
        ]);
    }

    /**
     * Show the form for creating a new customer
     */
    public function create()
    {
        return Inertia::render('customers/create');
    }

    /**
     * Store a newly created customer
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'postal_code' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'tax_number' => 'nullable|string|max:50',
            'vat_number' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:255',
            'customer_type' => 'required|in:business,private',
            'status' => 'nullable|in:active,inactive',
        ]);

        // Set default status if not provided
        if (!isset($validated['status'])) {
            $validated['status'] = 'active';
        }

        // Generate customer number using dynamic format setting
        $companyId = $this->getEffectiveCompanyId();
        $company   = \App\Modules\Company\Models\Company::find($companyId);
        $svc       = new NumberFormatService();
        $format    = $svc->normaliseToFormat(
            $company->getSetting('customer_number_format')
                ?? $company->getSetting('customer_prefix', 'KU-')
        );
        $validated['number'] = $svc->next($format, Customer::where('company_id', $companyId)->pluck('number'));
        $validated['company_id'] = $companyId;
        $validated['user_id'] = $user->id;

        Customer::create($validated);

        return redirect()->route('customers.index')
            ->with('success', 'Kunde erfolgreich erstellt.');
    }

    /**
     * Display the specified customer
     */
    public function show(Customer $customer)
    {
        $this->authorize('view', $customer);

        $customer->load([
            'invoices' => function ($query) {
                $query->orderBy('issue_date', 'desc');
            },
            'offers' => function ($query) {
                $query->orderBy('issue_date', 'desc');
            },
            'documents',
        ]);

        // Calculate customer statistics
        $stats = [
            'total_invoices' => $customer->invoices->count(),
            'total_offers' => $customer->offers->count(),
            'total_revenue' => $customer->invoices->where('status', 'paid')->sum('total'),
            'outstanding_amount' => $customer->invoices->whereIn('status', ['sent', 'overdue'])->sum('total'),
            'average_invoice_amount' => $customer->invoices->avg('total') ?? 0,
            'last_invoice_date' => $customer->invoices->max('created_at'),
            'last_offer_date' => $customer->offers->max('created_at'),
        ];

        return Inertia::render('customers/show', [
            'customer' => $customer,
            'stats'    => $stats,
        ]);
    }

    /**
     * Show the form for editing the specified customer
     */
    public function edit(Customer $customer)
    {
        $this->authorize('update', $customer);

        $customer->load('documents');

        return Inertia::render('customers/edit', [
            'customer' => $customer,
        ]);
    }

    /**
     * Update the specified customer
     */
    public function update(Request $request, Customer $customer)
    {
        $this->authorize('update', $customer);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'postal_code' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'tax_number' => 'nullable|string|max:50',
            'vat_number' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:255',
            'customer_type' => 'required|in:business,private',
            'status' => 'required|in:active,inactive',
        ]);

        $customer->update($validated);

        // Clear cache after update
        $this->clearUserCache();

        return redirect()->route('customers.index')
            ->with('success', 'Kunde erfolgreich aktualisiert.');
    }

    /**
     * Duplicate the specified customer
     */
    public function duplicate(Customer $customer)
    {
        $this->authorize('view', $customer);

        $companyId = $this->getEffectiveCompanyId();
        $company   = \App\Modules\Company\Models\Company::find($companyId);
        $svc       = new NumberFormatService();
        $format    = $svc->normaliseToFormat(
            $company->getSetting('customer_number_format')
                ?? $company->getSetting('customer_prefix', 'KU-')
        );

        $newCustomer = $customer->replicate(['number']);
        $newCustomer->number = $svc->next($format, Customer::where('company_id', $companyId)->pluck('number'));
        $newCustomer->name   = $customer->name . ' (Kopie)';
        $newCustomer->save();

        return redirect()->route('customers.edit', $newCustomer)
            ->with('success', 'Kunde erfolgreich dupliziert.');
    }

    /**
     * Remove the specified customer
     */
    public function destroy(Customer $customer)
    {
        $this->authorize('delete', $customer);

        if ($customer->invoices()->count() > 0 || $customer->offers()->count() > 0) {
            return redirect()->route('customers.index')
                ->with('error', 'Kunde kann nicht gelöscht werden, da Rechnungen oder Angebote vorhanden sind.');
        }

        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Kunde erfolgreich gelöscht.');
    }
}
