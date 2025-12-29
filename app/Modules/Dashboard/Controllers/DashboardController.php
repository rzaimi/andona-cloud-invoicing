<?php

namespace App\Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Offer\Models\Offer;
use App\Modules\Product\Models\Product;
use App\Modules\Expense\Models\Expense;
use App\Services\ContextService;
use App\Services\ExpenseReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(ContextService $contextService)
    {
        parent::__construct($contextService);
        $this->middleware('auth');
    }

    /**
     * Display the dashboard
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $this->getEffectiveCompanyId();
        $stats = $this->getDashboardStats();

        // Get recent activities
        $recentInvoices = Invoice::where('company_id', $companyId)
            ->with(['customer'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentOffers = Offer::where('company_id', $companyId)
            ->with(['customer'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentCustomers = Customer::where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Calculate growth percentages
        $growthData = [
            'revenue_growth' => $this->calculateGrowthPercentage(
                $stats['revenue']['this_month'],
                $stats['revenue']['last_month']
            ),
            'invoice_growth' => $this->calculateGrowthPercentage(
                $stats['invoices']['total'],
                $stats['invoices']['total'] // This would need historical data
            ),
            'customer_growth' => $this->calculateGrowthPercentage(
                $stats['customers']['new_this_month'],
                $stats['customers']['new_this_month'] // This would need historical data
            ),
        ];

        // Get overdue invoices for alerts
        $overdueInvoices = Invoice::where('company_id', $companyId)
            ->where('status', 'overdue')
            ->with(['customer'])
            ->orderBy('due_date', 'asc')
            ->limit(10)
            ->get();

        // Get low stock products for alerts
        $lowStockProducts = Product::where('company_id', $companyId)
            ->where('track_stock', true)
            ->whereColumn('stock_quantity', '<=', 'min_stock_level')
            ->orderBy('stock_quantity', 'asc')
            ->limit(10)
            ->get();

        // Get expenses data
        $expenseReportService = app(ExpenseReportService::class);
        $currentMonthExpenses = $expenseReportService->getMonthlyTotals(
            $companyId,
            now()->startOfMonth()->format('Y-m-d'),
            now()->endOfMonth()->format('Y-m-d')
        );
        
        // Calculate profit (income from payments - expenses)
        $currentMonthIncome = $expenseReportService->getIncome(
            $companyId,
            now()->startOfMonth()->format('Y-m-d'),
            now()->endOfMonth()->format('Y-m-d')
        );
        $netProfit = $currentMonthIncome - $currentMonthExpenses['total_amount'];

        return $this->inertia('dashboard', [
            'stats' => $stats,
            'growth' => $growthData,
            'recent' => [
                'invoices' => $recentInvoices,
                'offers' => $recentOffers,
                'customers' => $recentCustomers,
            ],
            'alerts' => [
                'overdue_invoices' => $overdueInvoices,
                'low_stock_products' => $lowStockProducts,
            ],
            'expenses' => [
                'current_month' => $currentMonthExpenses['total_amount'],
                'net_profit' => $netProfit,
            ],
        ]);
    }

    /**
     * Calculate growth percentage between two values
     */
    private function calculateGrowthPercentage(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }
}
