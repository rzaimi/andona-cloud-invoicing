<?php

namespace App\Modules\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Models\Category;
use App\Services\ContextService;
use Illuminate\Http\Request;
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

        // Get statistics in a single query pass
        $allCategoryStats = Category::where('company_id', $companyId)
            ->withCount('products')
            ->get(['id', 'is_active', 'parent_id']);

        $stats = [
            'total_categories'        => $allCategoryStats->count(),
            'active_categories'       => $allCategoryStats->where('is_active', true)->count(),
            'root_categories'         => $allCategoryStats->whereNull('parent_id')->count(),
            'categories_with_products'=> $allCategoryStats->where('products_count', '>', 0)->count(),
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
        $companyId = $this->getEffectiveCompanyId();

        if (!$companyId) {
            return redirect()->route('dashboard')->with('error', 'Keine Firma zugeordnet.');
        }

        // Get parent categories
        $parentCategories = Category::where('company_id', $companyId)
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
        $companyId = $this->getEffectiveCompanyId();

        if (!$companyId) {
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
        $this->authorize('view', $category);

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
        $this->authorize('update', $category);
        $companyId = $this->getEffectiveCompanyId();

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
        $this->authorize('update', $category);
        $companyId = $this->getEffectiveCompanyId();

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
        $this->authorize('delete', $category);

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

