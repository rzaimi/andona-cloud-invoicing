<?php

namespace App\Modules\Expense\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ExpenseReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExpenseReportController extends Controller
{
    protected $reportService;
    
    public function __construct(ExpenseReportService $reportService)
    {
        $this->reportService = $reportService;
    }
    
    public function summary(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();
        
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));
        
        $monthlyExpenses = $this->reportService->getMonthlyExpensesByCategory($companyId, $startDate, $endDate);
        $totals = $this->reportService->getMonthlyTotals($companyId, $startDate, $endDate);
        
        return Inertia::render('reports/expenses', [
            'expenses' => $monthlyExpenses,
            'totals' => $totals,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
    
    public function profit(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();
        
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));
        
        $profit = $this->reportService->getProfit($companyId, $startDate, $endDate);
        
        // Get monthly breakdown for chart
        $months = [];
        $current = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);
        
        while ($current <= $end) {
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();
            
            $monthProfit = $this->reportService->getProfit($companyId, $monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d'));
            
            $months[] = [
                'month' => $monthStart->format('Y-m'),
                'label' => $monthStart->format('M Y'),
                'income' => $monthProfit['income'],
                'expenses' => $monthProfit['expenses'],
                'profit' => $monthProfit['profit'],
            ];
            
            $current->addMonth();
        }
        
        return Inertia::render('reports/profit', [
            'profit' => $profit,
            'months' => $months,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
    
    public function vat(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();
        
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));
        
        $vatReport = $this->reportService->getVatReport($companyId, $startDate, $endDate);
        
        // Get monthly breakdown for chart
        $months = [];
        $current = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);
        
        while ($current <= $end) {
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();
            
            $monthVat = $this->reportService->getVatReport($companyId, $monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d'));
            
            $months[] = [
                'month' => $monthStart->format('Y-m'),
                'label' => $monthStart->format('M Y'),
                'output_vat' => $monthVat['output_vat'],
                'input_vat' => $monthVat['input_vat'],
                'vat_payable' => $monthVat['vat_payable'],
            ];
            
            $current->addMonth();
        }
        
        return Inertia::render('reports/vat', [
            'vat' => $vatReport,
            'months' => $months,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
}



