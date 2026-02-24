<?php

namespace App\Modules\Expense\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Expense\Models\Expense;
use App\Modules\Expense\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();
        
        $query = Expense::forCompany($companyId)
            ->with(['category', 'user:id,name']);
        
        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('expense_date', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->where('expense_date', '<=', $request->end_date);
        }
        
        // Filter by category
        if ($request->filled('category_id') && $request->category_id !== 'all') {
            $query->where('category_id', $request->category_id);
        }
        
        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                    ->orWhere('description', 'like', "%{$request->search}%")
                    ->orWhere('reference', 'like', "%{$request->search}%");
            });
        }
        
        $expenses = $query->orderBy('expense_date', 'desc')
            ->paginate(20)
            ->withQueryString();
        
        // Calculate totals (apply same filters as main query)
        $totalQuery = Expense::forCompany($companyId);
        
        if ($request->filled('start_date')) {
            $totalQuery->where('expense_date', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $totalQuery->where('expense_date', '<=', $request->end_date);
        }
        
        if ($request->filled('category_id') && $request->category_id !== 'all') {
            $totalQuery->where('category_id', $request->category_id);
        }
        
        // Apply search filter to totals query
        if ($request->filled('search')) {
            $totalQuery->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                    ->orWhere('description', 'like', "%{$request->search}%")
                    ->orWhere('reference', 'like', "%{$request->search}%");
            });
        }
        
        // Sum amounts directly - amount is the total (including VAT)
        $totalNetAmount = (float) ($totalQuery->sum('net_amount') ?? 0);
        $totalVatAmount = (float) ($totalQuery->sum('vat_amount') ?? 0);
        $totalAmount = (float) ($totalQuery->sum('amount') ?? 0);
        
        // Ensure no NaN values
        $totalNetAmount = is_nan($totalNetAmount) ? 0 : $totalNetAmount;
        $totalVatAmount = is_nan($totalVatAmount) ? 0 : $totalVatAmount;
        $totalAmount = is_nan($totalAmount) ? 0 : $totalAmount;
        
        $categories = ExpenseCategory::forCompany($companyId)->orderBy('name')->get();
        
        return Inertia::render('expenses/index', [
            'expenses' => $expenses,
            'categories' => $categories,
            'filters' => $request->only(['start_date', 'end_date', 'category_id', 'search']),
            'totals' => [
                'net_amount' => $totalNetAmount,
                'vat_amount' => $totalVatAmount,
                'total_amount' => $totalAmount, // Sum of amount field directly (includes VAT)
            ],
        ]);
    }
    
    public function create()
    {
        $this->authorize('create', Expense::class);
        
        $companyId = $this->getEffectiveCompanyId();
        
        $categories = ExpenseCategory::forCompany($companyId)->orderBy('name')->get();
        
        return Inertia::render('expenses/create', [
            'categories' => $categories,
        ]);
    }
    
    public function store(Request $request)
    {
        $this->authorize('create', Expense::class);
        
        // Prepare data - convert empty strings and "none" to null
        $data = $request->all();
        if (isset($data['category_id']) && ($data['category_id'] === '' || $data['category_id'] === 'none')) {
            $data['category_id'] = null;
        }
        
        $validated = validator($data, [
            'category_id' => 'nullable|exists:expense_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01',
            'vat_rate' => 'nullable|numeric|min:0|max:1',
            'expense_date' => 'required|date',
            'payment_method' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
            'receipt' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png',
        ])->validate();
        
        return DB::transaction(function () use ($validated, $request) {
            $companyId = $this->getEffectiveCompanyId();
            $user = $request->user();
            
            // Verify category belongs to company if provided
            if (isset($validated['category_id']) && $validated['category_id']) {
                $category = ExpenseCategory::forCompany($companyId)
                    ->findOrFail($validated['category_id']);
            }
            
            // Handle receipt upload
            $receiptPath             = null;
            $receiptOriginalFilename = null;
            if ($request->hasFile('receipt')) {
                $file      = $request->file('receipt');
                $ext       = $file->getClientOriginalExtension();
                $filename  = Str::uuid() . ($ext ? ".{$ext}" : '');
                $directory = "{$companyId}/expenses/" . now()->year . '/' . now()->format('m');
                $receiptPath             = $file->storeAs($directory, $filename, 'private');
                $receiptOriginalFilename = $file->getClientOriginalName();
            }
            
            // Calculate VAT and net amount
            // amount is the total amount (including VAT)
            $vatRate = $validated['vat_rate'] ?? 0.19;
            $amount = $validated['amount'];
            $vatAmount = round($amount * $vatRate, 2);
            $netAmount = round($amount - $vatAmount, 2);
            
            $expense = Expense::create([
                'company_id' => $companyId,
                'user_id' => $user->id,
                'category_id' => $validated['category_id'] ?? null,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'amount' => $amount,
                'vat_rate' => $vatRate,
                'vat_amount' => $vatAmount,
                'net_amount' => $netAmount,
                'expense_date' => $validated['expense_date'],
                'payment_method' => $validated['payment_method'] ?? null,
                'reference' => $validated['reference'] ?? null,
                'receipt_path' => $receiptPath,
                'receipt_original_filename' => $receiptOriginalFilename,
            ]);
            
            return redirect()->route('expenses.index')
                ->with('success', 'Ausgabe wurde erfolgreich erstellt.');
        });
    }
    
    public function show(Expense $expense)
    {
        $this->authorize('view', $expense);
        
        $expense->load(['category', 'user', 'company']);
        
        return Inertia::render('expenses/show', [
            'expense' => $expense,
        ]);
    }
    
    public function edit(Expense $expense)
    {
        $this->authorize('update', $expense);
        
        $companyId = $this->getEffectiveCompanyId();
        $categories = ExpenseCategory::forCompany($companyId)->orderBy('name')->get();
        
        return Inertia::render('expenses/edit', [
            'expense' => $expense,
            'categories' => $categories,
        ]);
    }
    
    public function update(Request $request, Expense $expense)
    {
        $this->authorize('update', $expense);
        
        // Prepare data - convert empty strings and "none" to null
        $data = $request->all();
        if (isset($data['category_id']) && ($data['category_id'] === '' || $data['category_id'] === 'none')) {
            $data['category_id'] = null;
        }
        
        $validated = validator($data, [
            'category_id' => 'nullable|exists:expense_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01',
            'vat_rate' => 'nullable|numeric|min:0|max:1',
            'expense_date' => 'required|date',
            'payment_method' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
            'receipt' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png',
        ])->validate();
        
        return DB::transaction(function () use ($validated, $request, $expense) {
            $companyId = $this->getEffectiveCompanyId();
            
            // Verify category belongs to company if provided
            if (isset($validated['category_id']) && $validated['category_id']) {
                ExpenseCategory::forCompany($companyId)
                    ->findOrFail($validated['category_id']);
            }
            
            // Handle receipt upload
            if ($request->hasFile('receipt')) {
                // Delete old receipt (check both disks for backward compat)
                if ($expense->receipt_path) {
                    if (Storage::disk('private')->exists($expense->receipt_path)) {
                        Storage::disk('private')->delete($expense->receipt_path);
                    } elseif (Storage::disk('expenses')->exists($expense->receipt_path)) {
                        Storage::disk('expenses')->delete($expense->receipt_path);
                    }
                }

                $file      = $request->file('receipt');
                $ext       = $file->getClientOriginalExtension();
                $filename  = Str::uuid() . ($ext ? ".{$ext}" : '');
                $directory = "{$companyId}/expenses/" . now()->year . '/' . now()->format('m');

                $validated['receipt_path']             = $file->storeAs($directory, $filename, 'private');
                $validated['receipt_original_filename'] = $file->getClientOriginalName();
            }
            
            // Calculate VAT and net amount
            // amount is the total amount (including VAT)
            $vatRate = $validated['vat_rate'] ?? 0.19;
            $amount = $validated['amount'];
            $vatAmount = round($amount * $vatRate, 2);
            $netAmount = round($amount - $vatAmount, 2);
            
            $expense->update([
                'category_id' => $validated['category_id'] ?? null,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'amount' => $amount,
                'vat_rate' => $vatRate,
                'vat_amount' => $vatAmount,
                'net_amount' => $netAmount,
                'expense_date' => $validated['expense_date'],
                'payment_method' => $validated['payment_method'] ?? null,
                'reference' => $validated['reference'] ?? null,
                'receipt_path' => $validated['receipt_path'] ?? $expense->receipt_path,
            ]);
            
            return redirect()->route('expenses.index')
                ->with('success', 'Ausgabe wurde erfolgreich aktualisiert.');
        });
    }
    
    public function destroy(Expense $expense)
    {
        $this->authorize('delete', $expense);
        
        DB::transaction(function () use ($expense) {
            if ($expense->receipt_path) {
                if (Storage::disk('private')->exists($expense->receipt_path)) {
                    Storage::disk('private')->delete($expense->receipt_path);
                } elseif (Storage::disk('expenses')->exists($expense->receipt_path)) {
                    Storage::disk('expenses')->delete($expense->receipt_path);
                }
            }
            $expense->delete();
        });
        
        return redirect()->route('expenses.index')
            ->with('success', 'Ausgabe wurde erfolgreich gelöscht.');
    }
    
    public function downloadReceipt(Expense $expense)
    {
        $this->authorize('view', $expense);
        
        if (!$expense->receipt_path) {
            abort(404, 'Beleg nicht gefunden.');
        }

        $disk = Storage::disk('private')->exists($expense->receipt_path)
            ? 'private'
            : 'expenses'; // legacy fallback

        if (!Storage::disk($disk)->exists($expense->receipt_path)) {
            abort(404, 'Beleg nicht gefunden.');
        }

        $downloadName = $expense->receipt_original_filename
            ?? basename($expense->receipt_path);

        return Storage::disk($disk)->download($expense->receipt_path, $downloadName);
    }
}

