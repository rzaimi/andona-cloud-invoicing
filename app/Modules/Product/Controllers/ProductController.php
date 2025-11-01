<?php

namespace App\Modules\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();
        $products = Product::forCompany($companyId)
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('number', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
            })
            ->when($request->category, function ($query, $category) {
                $query->byCategory($category);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->type, function ($query, $type) {
                if ($type === 'service') {
                    $query->services();
                } elseif ($type === 'product') {
                    $query->products();
                }
            })
            ->when($request->low_stock, function ($query) {
                $query->lowStock();
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $categories = Product::forCompany($companyId)
            ->whereNotNull('category_id')
            ->distinct()
            ->pluck('category_id');

        $stats = [
            'total' => Product::forCompany($companyId)->count(),
            'active' => Product::forCompany($companyId)->active()->count(),
            'services' => Product::forCompany($companyId)->services()->count(),
            'low_stock' => Product::forCompany($companyId)->lowStock()->count(),
        ];

        return Inertia::render('products/index', [
            'products' => $products,
            'categories' => $categories,
            'stats' => $stats,
            'filters' => $request->only(['search', 'category', 'status', 'type', 'low_stock']),
        ]);
    }

    public function create()
    {
        $companyId = $this->getEffectiveCompanyId();
        $categories = Product::forCompany($companyId)
            ->whereNotNull('category_id')
            ->distinct()
            ->pluck('category_id');

        return Inertia::render('products/create', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'tax_rate' => 'required|numeric|min:0|max:1',
            'stock_quantity' => 'required|integer|min:0',
            'min_stock_level' => 'required|integer|min:0',
            'track_stock' => 'boolean',
            'is_service' => 'boolean',
            'status' => 'required|in:active,inactive,discontinued',
        ]);

        Product::create([
            ...$validated,
            'company_id' => $this->getEffectiveCompanyId(),
        ]);

        return redirect()->route('products.index')
            ->with('success', 'Produkt wurde erfolgreich erstellt.');
    }

    public function show(Product $product)
    {
        $this->authorize('view', $product);

        $product->load(['invoiceItems.invoice', 'offerItems.offer']);

        return Inertia::render('products/show', [
            'product' => $product,
        ]);
    }

    public function edit(Product $product)
    {
        $this->authorize('update', $product);

        $companyId = $this->getEffectiveCompanyId();
        $categories = Product::forCompany($companyId)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category');

        return Inertia::render('products/edit', [
            'product' => $product,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'category' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'tax_rate' => 'required|numeric|min:0|max:1',
            'stock_quantity' => 'required|integer|min:0',
            'min_stock_level' => 'required|integer|min:0',
            'track_stock' => 'boolean',
            'is_service' => 'boolean',
            'status' => 'required|in:active,inactive,discontinued',
        ]);

        $product->update($validated);

        return redirect()->route('products.index')
            ->with('success', 'Produkt wurde erfolgreich aktualisiert.');
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        // Check if product is used in any invoices or offers
        if ($product->invoiceItems()->exists() || $product->offerItems()->exists()) {
            return redirect()->route('products.index')
                ->with('error', 'Produkt kann nicht gelöscht werden, da es in Rechnungen oder Angeboten verwendet wird.');
        }

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Produkt wurde erfolgreich gelöscht.');
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');

        $companyId = $this->getEffectiveCompanyId();
        $products = Product::forCompany($companyId)
            ->active()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('number', 'like', "%{$query}%")
                  ->orWhere('sku', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get(['id', 'number', 'name', 'description', 'unit', 'price', 'tax_rate']);

        return response()->json($products);
    }
}
