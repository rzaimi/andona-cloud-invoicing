<?php

namespace App\Modules\Reports\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Offer\Models\Offer;
use App\Modules\Customer\Models\Customer;
use App\Services\ContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class ReportsController extends Controller
{
    public function __construct(ContextService $contextService)
    {
        parent::__construct($contextService);
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();

        // Get overview statistics
        $stats = $this->getOverviewStats($companyId);

        return Inertia::render('reports/index', [
            'stats' => $stats,
        ]);
    }

    public function revenue(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();
        $period = $request->get('period', 'month'); // month, quarter, year

        // Get revenue data
        $revenueData = $this->getRevenueData($companyId, $period);

        return Inertia::render('reports/revenue', [
            'period' => $period,
            'revenueData' => $revenueData,
        ]);
    }

    public function customers(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();

        // Get customer statistics
        $customerStats = $this->getCustomerStats($companyId);

        return Inertia::render('reports/customers', [
            'customerStats' => $customerStats,
        ]);
    }

    public function tax(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();
        $period = $request->get('period', 'month');

        // Get tax data
        $taxData = $this->getTaxData($companyId, $period);

        return Inertia::render('reports/tax', [
            'period' => $period,
            'taxData' => $taxData,
        ]);
    }

    private function getOverviewStats($companyId)
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfYear = $now->copy()->startOfYear();

        // Invoice stats
        $totalInvoices = Invoice::where('company_id', $companyId)->count();
        $paidInvoices = Invoice::where('company_id', $companyId)->where('status', 'paid')->count();
        $monthlyRevenue = Invoice::where('company_id', $companyId)
            ->where('status', 'paid')
            ->where('issue_date', '>=', $startOfMonth)
            ->sum('total');
        $yearlyRevenue = Invoice::where('company_id', $companyId)
            ->where('status', 'paid')
            ->where('issue_date', '>=', $startOfYear)
            ->sum('total');

        // Offer stats
        $totalOffers = Offer::where('company_id', $companyId)->count();
        $acceptedOffers = Offer::where('company_id', $companyId)->where('status', 'accepted')->count();
        $monthlyOffers = Offer::where('company_id', $companyId)
            ->where('issue_date', '>=', $startOfMonth)
            ->sum('total');

        // Customer stats
        $totalCustomers = Customer::where('company_id', $companyId)->count();
        $activeCustomers = Customer::where('company_id', $companyId)
            ->where('status', 'active')
            ->count();

        return [
            'invoices' => [
                'total' => $totalInvoices,
                'paid' => $paidInvoices,
                'monthly_revenue' => $monthlyRevenue,
                'yearly_revenue' => $yearlyRevenue,
            ],
            'offers' => [
                'total' => $totalOffers,
                'accepted' => $acceptedOffers,
                'monthly_total' => $monthlyOffers,
            ],
            'customers' => [
                'total' => $totalCustomers,
                'active' => $activeCustomers,
            ],
        ];
    }

    private function getRevenueData($companyId, $period)
    {
        $now = Carbon::now();
        $data = [];

        switch ($period) {
            case 'year':
                // Last 12 months
                for ($i = 11; $i >= 0; $i--) {
                    $month = $now->copy()->subMonths($i);
                    $startOfMonth = $month->copy()->startOfMonth();
                    $endOfMonth = $month->copy()->endOfMonth();

                    $revenue = Invoice::where('company_id', $companyId)
                        ->where('status', 'paid')
                        ->whereBetween('issue_date', [$startOfMonth, $endOfMonth])
                        ->sum('total');

                    $data[] = [
                        'period' => $month->format('M Y'),
                        'revenue' => $revenue,
                        'invoices' => Invoice::where('company_id', $companyId)
                            ->whereBetween('issue_date', [$startOfMonth, $endOfMonth])
                            ->count(),
                    ];
                }
                break;

            case 'quarter':
                // Last 4 quarters
                for ($i = 3; $i >= 0; $i--) {
                    $quarter = $now->copy()->subQuarters($i);
                    $startOfQuarter = $quarter->copy()->startOfQuarter();
                    $endOfQuarter = $quarter->copy()->endOfQuarter();

                    $revenue = Invoice::where('company_id', $companyId)
                        ->where('status', 'paid')
                        ->whereBetween('issue_date', [$startOfQuarter, $endOfQuarter])
                        ->sum('total');

                    $data[] = [
                        'period' => 'Q' . $quarter->quarter . ' ' . $quarter->year,
                        'revenue' => $revenue,
                        'invoices' => Invoice::where('company_id', $companyId)
                            ->whereBetween('issue_date', [$startOfQuarter, $endOfQuarter])
                            ->count(),
                    ];
                }
                break;

            case 'month':
            default:
                // Last 6 months
                for ($i = 5; $i >= 0; $i--) {
                    $month = $now->copy()->subMonths($i);
                    $startOfMonth = $month->copy()->startOfMonth();
                    $endOfMonth = $month->copy()->endOfMonth();

                    $revenue = Invoice::where('company_id', $companyId)
                        ->where('status', 'paid')
                        ->whereBetween('issue_date', [$startOfMonth, $endOfMonth])
                        ->sum('total');

                    $data[] = [
                        'period' => $month->format('M Y'),
                        'revenue' => $revenue,
                        'invoices' => Invoice::where('company_id', $companyId)
                            ->whereBetween('issue_date', [$startOfMonth, $endOfMonth])
                            ->count(),
                    ];
                }
                break;
        }

        return $data;
    }

    private function getCustomerStats($companyId)
    {
        // Top customers by revenue
        $topCustomers = Invoice::where('company_id', $companyId)
            ->where('status', 'paid')
            ->with('customer')
            ->select('customer_id', DB::raw('SUM(total) as total_revenue'), DB::raw('COUNT(*) as invoice_count'))
            ->groupBy('customer_id')
            ->orderBy('total_revenue', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($invoice) {
                return [
                    'customer_id' => $invoice->customer_id,
                    'customer_name' => $invoice->customer->name ?? 'Unbekannt',
                    'total_revenue' => $invoice->total_revenue,
                    'invoice_count' => $invoice->invoice_count,
                ];
            });

        // Customer growth
        $now = Carbon::now();
        $lastMonth = $now->copy()->subMonth();
        $customersLastMonth = Customer::where('company_id', $companyId)
            ->where('created_at', '<=', $lastMonth->endOfMonth())
            ->count();
        $customersThisMonth = Customer::where('company_id', $companyId)
            ->where('created_at', '<=', $now->endOfMonth())
            ->count();

        // Customers by status
        $customersByStatus = Customer::where('company_id', $companyId)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        return [
            'top_customers' => $topCustomers,
            'growth' => [
                'last_month' => $customersLastMonth,
                'this_month' => $customersThisMonth,
                'change' => $customersThisMonth - $customersLastMonth,
            ],
            'by_status' => $customersByStatus,
        ];
    }

    private function getTaxData($companyId, $period)
    {
        $now = Carbon::now();
        $data = [];

        switch ($period) {
            case 'year':
                // Last 12 months
                for ($i = 11; $i >= 0; $i--) {
                    $month = $now->copy()->subMonths($i);
                    $startOfMonth = $month->copy()->startOfMonth();
                    $endOfMonth = $month->copy()->endOfMonth();

                    $invoices = Invoice::where('company_id', $companyId)
                        ->where('status', 'paid')
                        ->whereBetween('issue_date', [$startOfMonth, $endOfMonth])
                        ->get();

                    // Use actual subtotal and tax_amount from invoices
                    $subtotal = $invoices->sum('subtotal');
                    $tax = $invoices->sum('tax_amount');
                    $total = $invoices->sum('total');

                    $data[] = [
                        'period' => $month->format('M Y'),
                        'subtotal' => $subtotal,
                        'tax' => $tax,
                        'total' => $total,
                    ];
                }
                break;

            case 'month':
            default:
                // Last 6 months
                for ($i = 5; $i >= 0; $i--) {
                    $month = $now->copy()->subMonths($i);
                    $startOfMonth = $month->copy()->startOfMonth();
                    $endOfMonth = $month->copy()->endOfMonth();

                    $invoices = Invoice::where('company_id', $companyId)
                        ->where('status', 'paid')
                        ->whereBetween('issue_date', [$startOfMonth, $endOfMonth])
                        ->get();

                    // Use actual subtotal and tax_amount from invoices
                    $subtotal = $invoices->sum('subtotal');
                    $tax = $invoices->sum('tax_amount');
                    $total = $invoices->sum('total');

                    $data[] = [
                        'period' => $month->format('M Y'),
                        'subtotal' => $subtotal,
                        'tax' => $tax,
                        'total' => $total,
                    ];
                }
                break;
        }

        return $data;
    }
}

