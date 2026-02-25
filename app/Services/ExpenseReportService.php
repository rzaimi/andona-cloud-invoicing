<?php

namespace App\Services;

use App\Modules\Expense\Models\Expense;
use App\Modules\Expense\Models\ExpenseCategory;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Payment\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExpenseReportService
{
    /**
     * Calculate monthly expenses grouped by category
     */
    public function getMonthlyExpensesByCategory($companyId, $startDate = null, $endDate = null): array
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfMonth();
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now()->endOfMonth();

        $expenses = Expense::forCompany($companyId)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->leftJoin('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->select(
                'expenses.category_id',
                DB::raw("COALESCE(expense_categories.name, 'Ohne Kategorie') as category_name"),
                DB::raw('SUM(expenses.net_amount) as total_net'),
                DB::raw('SUM(expenses.vat_amount) as total_vat')
            )
            ->groupBy('expenses.category_id', 'expense_categories.name')
            ->get();

        return $expenses->map(fn ($row) => [
            'category_id'   => $row->category_id,
            'category_name' => $row->category_name,
            'net_amount'    => (float) $row->total_net,
            'vat_amount'    => (float) $row->total_vat,
            'total_amount'  => (float) $row->total_net + (float) $row->total_vat,
        ])->values()->all();
    }
    
    /**
     * Calculate monthly totals
     */
    public function getMonthlyTotals($companyId, $startDate = null, $endDate = null): array
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfMonth();
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now()->endOfMonth();
        
        $expenses = Expense::forCompany($companyId)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->select(
                DB::raw('SUM(net_amount) as total_net'),
                DB::raw('SUM(vat_amount) as total_vat')
            )
            ->first();
        
        return [
            'net_amount' => (float) ($expenses->total_net ?? 0),
            'vat_amount' => (float) ($expenses->total_vat ?? 0),
            'total_amount' => (float) (($expenses->total_net ?? 0) + ($expenses->total_vat ?? 0)),
        ];
    }
    
    /**
     * Calculate income from paid invoices
     */
    public function getIncome($companyId, $startDate = null, $endDate = null): float
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfMonth();
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now()->endOfMonth();
        
        // Get income from completed payments
        $income = Payment::forCompany($companyId)
            ->where('status', 'completed')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->sum('amount');
        
        return (float) $income;
    }
    
    /**
     * Calculate profit (income - expenses)
     */
    public function getProfit($companyId, $startDate = null, $endDate = null): array
    {
        $income = $this->getIncome($companyId, $startDate, $endDate);
        $expenses = $this->getMonthlyTotals($companyId, $startDate, $endDate);
        
        return [
            'income' => $income,
            'expenses' => $expenses['total_amount'],
            'profit' => $income - $expenses['total_amount'],
        ];
    }
    
    /**
     * Calculate VAT: output VAT (from invoices) vs input VAT (from expenses)
     */
    public function getVatReport($companyId, $startDate = null, $endDate = null): array
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfMonth();
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now()->endOfMonth();
        
        // Output VAT: VAT from paid invoices
        $outputVat = Invoice::where('company_id', $companyId)
            ->where('status', 'paid')
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->sum('tax_amount');
        
        // Input VAT: VAT from expenses
        $inputVat = Expense::forCompany($companyId)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->sum('vat_amount');
        
        return [
            'output_vat' => (float) $outputVat,
            'input_vat' => (float) $inputVat,
            'vat_payable' => (float) ($outputVat - $inputVat),
        ];
    }
}



