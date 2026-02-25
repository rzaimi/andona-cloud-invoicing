<?php

namespace App\Modules\Expense\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Expense\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', ExpenseCategory::class);
        
        $companyId = $this->getEffectiveCompanyId();
        
        $categories = ExpenseCategory::forCompany($companyId)
            ->withCount('expenses')
            ->orderBy('name')
            ->get();
        
        return Inertia::render('expenses/categories', [
            'categories' => $categories,
        ]);
    }
    
    public function store(Request $request)
    {
        $this->authorize('create', ExpenseCategory::class);
        
        $companyId = $this->getEffectiveCompanyId();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        
        // Check for duplicate name within company
        $exists = ExpenseCategory::forCompany($companyId)
            ->where('name', $validated['name'])
            ->exists();
        
        if ($exists) {
            return redirect()->back()
                ->withErrors(['name' => 'Eine Kategorie mit diesem Namen existiert bereits.']);
        }
        
        ExpenseCategory::create([
            'company_id' => $companyId,
            'name' => $validated['name'],
        ]);
        
        return redirect()->route('expenses.categories.index')
            ->with('success', 'Kategorie wurde erfolgreich erstellt.');
    }
    
    public function update(Request $request, ExpenseCategory $category)
    {
        $this->authorize('update', $category);
        $companyId = $this->getEffectiveCompanyId();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        
        // Check for duplicate name within company (excluding current category)
        $exists = ExpenseCategory::forCompany($companyId)
            ->where('name', $validated['name'])
            ->where('id', '!=', $category->id)
            ->exists();
        
        if ($exists) {
            return redirect()->back()
                ->withErrors(['name' => 'Eine Kategorie mit diesem Namen existiert bereits.']);
        }
        
        $category->update([
            'name' => $validated['name'],
        ]);
        
        return redirect()->route('expenses.categories.index')
            ->with('success', 'Kategorie wurde erfolgreich aktualisiert.');
    }
    
    public function destroy(ExpenseCategory $category)
    {
        $this->authorize('delete', $category);

        // Check if category has expenses
        if ($category->expenses()->count() > 0) {
            return redirect()->back()
                ->withErrors(['error' => 'Kategorie kann nicht gelöscht werden, da sie noch Ausgaben enthält.']);
        }
        
        $category->delete();
        
        return redirect()->route('expenses.categories.index')
            ->with('success', 'Kategorie wurde erfolgreich gelöscht.');
    }
}

