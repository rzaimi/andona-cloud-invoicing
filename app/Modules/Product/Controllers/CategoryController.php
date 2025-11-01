<?php

namespace App\Modules\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Models\Category;
use App\Services\ContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function __construct(ContextService $contextService)
    {
        parent::__construct($contextService);
        $this->middleware('auth');
    }

    /**
     * Display a listing of categories.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $this->getEffectiveCompanyId();

        if (!$companyId) {
            return redirect()->route('dashboard')->with('error', 'Keine Firma zugeordnet.');
        }

        $query = Category::where('company_id', $companyId)
            ->with(['parent', 'children'])
            ->withCount('products');

        // Search
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Filter by parent
        if ($request->filled('parent')) {
            if ($request->parent === 'root') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent);
            }
        }

        $categories = $query->orderBy('sort_order')->orderBy('name')->paginate(20);

        // Get statistics
        $stats = [
            'total_categories' => Category::where('company_id', $companyId)->count(),
            'active_categories' => Category::where('company_id', $companyId)->where('is_active', true)->count(),
            'root_categories' => Category::where('company_id', $companyId)->whereNull('parent_id')->count(),
            'categories_with_products' => Category::where('company_id', $companyId)->has('products')->count(),
        ];

        // Get parent categories for filter
        $parentCategories = Category::where('company_id', $companyId)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return Inertia::render('categories/index', [
            'categories' => $categories,
            'stats' => $stats,
            'parentCategories' => $parentCategories,
            'filters' => $request->only(['search', 'status', 'parent']),
        ]);
    }

    /**
     * Show the form for creating a new category.
     */
    public function create()
    {
        $user = Auth::user();
        $companyId = $this->getEffectiveCompanyId();

        if (!$company) {
            return redirect()->route('dashboard')->with('error', 'Keine Firma zugeordnet.');
        }

        // Get parent categories
        $parentCategories = Category::where('company_id', $company->id)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return Inertia::render('categories/create', [
            'parentCategories' => $parentCategories,
        ]);
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $companyId = $this->getEffectiveCompanyId();

        if (!$company) {
            return redirect()->route('dashboard')->with('error', 'Keine Firma zugeordnet.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
            'parent_id' => 'nullable|exists:categories,id',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Validate parent belongs to same company
        if ($validated['parent_id']) {
            $parent = Category::find($validated['parent_id']);
            if (!$parent || $parent->company_id !== $companyId) {
                return back()->withErrors(['parent_id' => 'Ungültige übergeordnete Kategorie.']);
            }
        }

        $validated['company_id'] = $companyId;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $validated['is_active'] ?? true;

        Category::create($validated);

        return redirect()->route('categories.index')->with('success', 'Kategorie erfolgreich erstellt.');
    }

    /**
     * Display the specified category.
     */
    public function show(Category $category)
    {
        $user = Auth::user();

        $companyId = $this->getEffectiveCompanyId();
        if ($category->company_id !== $companyId) {
            abort(403);
        }

        $category->load(['parent', 'children.products', 'products' => function ($query) {
            $query->with(['category'])->orderBy('name');
        }]);

        // Get category statistics
        $stats = [
            'total_products' => $category->products()->count(),
            'active_products' => $category->products()->where('status', 'active')->count(),
            'total_value' => $category->products()->sum('price'),
            'low_stock_products' => $category->products()
                ->where('track_stock', true)
                ->whereColumn('stock_quantity', '<=', 'min_stock_level')
                ->count(),
        ];

        return Inertia::render('categories/show', [
            'category' => $category,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(Category $category)
    {
        $user = Auth::user();

        $companyId = $this->getEffectiveCompanyId();
        if ($category->company_id !== $companyId) {
            abort(403);
        }

        // Get parent categories (excluding self and children to prevent circular references)
        $parentCategories = Category::where('company_id', $companyId)
            ->where('id', '!=', $category->id)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return Inertia::render('categories/edit', [
            'category' => $category,
            'parentCategories' => $parentCategories,
        ]);
    }

    /**
     * Update the specified category in storage.
     */
    public function update(Request $request, Category $category)
    {
        $user = Auth::user();

        $companyId = $this->getEffectiveCompanyId();
        if ($category->company_id !== $companyId) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
            'parent_id' => 'nullable|exists:categories,id',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Validate parent belongs to same company and prevent circular references
        if ($validated['parent_id']) {
            $parent = Category::find($validated['parent_id']);
            $companyId = $this->getEffectiveCompanyId();
            if (!$parent || $parent->company_id !== $companyId) {
                return back()->withErrors(['parent_id' => 'Ungültige übergeordnete Kategorie.']);
            }

            // Check for circular reference
            if ($this->wouldCreateCircularReference($category, $validated['parent_id'])) {
                return back()->withErrors(['parent_id' => 'Zirkuläre Referenz nicht erlaubt.']);
            }
        }

        $validated['sort_order'] = $validated['sort_order'] ?? $category->sort_order;
        $validated['is_active'] = $validated['is_active'] ?? $category->is_active;

        $category->update($validated);

        return redirect()->route('categories.index')->with('success', 'Kategorie erfolgreich aktualisiert.');
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(Category $category)
    {
        $user = Auth::user();

        $companyId = $this->getEffectiveCompanyId();
        if ($category->company_id !== $companyId) {
            abort(403);
        }

        // Check if category has products
        if ($category->products()->count() > 0) {
            return back()->withErrors(['category' => 'Kategorie kann nicht gelöscht werden, da sie Produkte enthält.']);
        }

        // Check if category has children
        if ($category->children()->count() > 0) {
            return back()->withErrors(['category' => 'Kategorie kann nicht gelöscht werden, da sie Unterkategorien enthält.']);
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Kategorie erfolgreich gelöscht.');
    }

    /**
     * Check if setting a parent would create a circular reference.
     */
    private function wouldCreateCircularReference(Category $category, string $parentId): bool
    {
        $parent = Category::find($parentId);

        while ($parent) {
            if ($parent->id === $category->id) {
                return true;
            }
            $parent = $parent->parent;
        }

        return false;
    }
}

