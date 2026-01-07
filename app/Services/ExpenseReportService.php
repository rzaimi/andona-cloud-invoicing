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
            ->select('category_id', DB::raw('SUM(net_amount) as total_net'), DB::raw('SUM(vat_amount) as total_vat'))
            ->groupBy('category_id')
            ->get();
        
        $result = [];
        foreach ($expenses as $expense) {
            $categoryName = $expense->category ? $expense->category->name : 'Ohne Kategorie';
            $result[] = [
                'category_id' => $expense->category_id,
                'category_name' => $categoryName,
                'net_amount' => (float) $expense->total_net,
                'vat_amount' => (float) $expense->total_vat,
                'total_amount' => (float) $expense->total_net + (float) $expense->total_vat,
            ];
        }
        
        return $result;
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



