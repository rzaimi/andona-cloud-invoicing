<?php

namespace App\Modules\Customer\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customer\Models\Customer;
use App\Services\ContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $query = Customer::forCompany($companyId)->with(['invoices', 'offers']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('number', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Sort functionality
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $customers = $query->paginate(15)->withQueryString();

        // Add calculated fields
        $customers->getCollection()->transform(function ($customer) {
            $customer->total_invoices = $customer->invoices->count();
            $customer->total_revenue = $customer->invoices->where('status', 'paid')->sum('total');
            $customer->outstanding_amount = $customer->invoices->whereIn('status', ['sent', 'overdue'])->sum('total');
            return $customer;
        });

        return Inertia::render('customers/index', [
            'customers' => $customers,
            'filters' => $request->only(['search', 'status', 'sort_by', 'sort_order']),
            'breadcrumbs' => $this->getBreadcrumbs(),
        ]);
    }

    /**
     * Show the form for creating a new customer
     */
    public function create()
    {
        return Inertia::render('customers/create', [
            'breadcrumbs' => $this->getBreadcrumbs(),
        ]);
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

        // Generate customer number
        $companyId = $this->getEffectiveCompanyId();
        $company = \App\Modules\Company\Models\Company::find($companyId);
        $prefix = $company->getSetting('customer_prefix', 'KU');
        $year = date('Y');

        $lastCustomer = Customer::where('company_id', $companyId)
            ->where('number', 'like', "{$prefix}-{$year}-%")
            ->orderBy('number', 'desc')
            ->first();

        if ($lastCustomer) {
            $lastNumber = (int) substr($lastCustomer->number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $validated['number'] = sprintf('%s-%s-%04d', $prefix, $year, $newNumber);
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
                $query->orderBy('created_at', 'desc');
            },
            'offers' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
            'documents'
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
            'stats' => $stats,
            'breadcrumbs' => $this->getBreadcrumbs(),
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
            'breadcrumbs' => $this->getBreadcrumbs(),
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
     * Remove the specified customer
     */
    public function destroy(Customer $customer)
    {
        $this->authorize('delete', $customer);

        // Check if customer has invoices or offers
        if ($customer->invoices()->count() > 0 || $customer->offers()->count() > 0) {
            return redirect()->route('customers.index')
                ->with('error', 'Kunde kann nicht gelöscht werden, da Rechnungen oder Angebote vorhanden sind.');
        }

        $customer->delete();

        // Clear cache after deletion
        $this->clearUserCache();

        return redirect()->route('customers.index')
            ->with('success', 'Kunde erfolgreich gelöscht.');
    }

    protected function getCompanyContext()
    {
        // Placeholder for company context retrieval logic
        return [];
    }

    protected function clearUserCache(): void
    {
        // Placeholder for cache clearing logic
    }

    protected function inertia($view, $props = [])
    {
        return Inertia::render($view, $props);
    }

    protected function redirectWithSuccess($route, $message, array $parameters = [])
    {
        return redirect()->route($route)->with('success', $message);
    }

    protected function redirectWithError($route, $message, array $parameters = [])
    {
        return redirect()->route($route)->with('error', $message);
    }

    private function getBreadcrumbs()
    {
        // Placeholder for breadcrumbs logic
        return [];
    }
}
