<?php

namespace App\Modules\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Category;
use App\Modules\Product\Models\Warehouse;
use App\Modules\Product\Models\WarehouseStock;
use App\Modules\Product\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            'total_products' => Product::forCompany($companyId)->count(),
            'active_products' => Product::forCompany($companyId)->active()->count(),
            'low_stock_products' => Product::forCompany($companyId)->lowStock()->count(),
            'out_of_stock_products' => Product::forCompany($companyId)
                ->where('track_stock', true)
                ->where('stock_quantity', '<=', 0)
                ->count(),
            'total_value' => Product::forCompany($companyId)
                ->whereNotNull('cost_price')
                ->where('track_stock', true)
                ->get()
                ->sum(function ($product) {
                    return ($product->cost_price ?? 0) * ($product->stock_quantity ?? 0);
                }),
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
        $categories = Category::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

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
            'category_id' => 'nullable|uuid|exists:categories,id',
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

        $companyId = $this->getEffectiveCompanyId();
        
        $product->load(['invoiceItems.invoice', 'offerItems.offer']);

        // Load warehouses for stock adjustment
        $warehouses = Warehouse::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_default']);

        // Load recent stock movements
        $stockMovements = StockMovement::where('product_id', $product->id)
            ->with(['warehouse:id,name,code', 'user:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($movement) {
                // Map internal type constants to frontend-friendly types
                $typeMap = [
                    StockMovement::TYPE_IN => 'in',
                    StockMovement::TYPE_OUT => 'out',
                    StockMovement::TYPE_ADJUSTMENT => 'adjustment',
                ];
                
                return [
                    'id' => $movement->id,
                    'type' => $typeMap[$movement->type] ?? 'adjustment',
                    'quantity' => (float) $movement->quantity,
                    'reason' => $movement->reason,
                    'notes' => $movement->notes,
                    'created_at' => $movement->created_at->toISOString(),
                    'user_name' => $movement->user->name ?? 'Unbekannt',
                    'warehouse_name' => $movement->warehouse->name ?? 'Unbekannt',
                ];
            });

        return Inertia::render('products/show', [
            'product' => $product,
            'warehouses' => $warehouses,
            'stock_movements' => $stockMovements,
        ]);
    }

    public function edit(Product $product)
    {
        $this->authorize('update', $product);

        $companyId = $this->getEffectiveCompanyId();
        $categories = Category::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

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
            'category_id' => 'nullable|uuid|exists:categories,id',
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

    /**
     * Adjust stock for a product.
     */
    public function adjustStock(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $user = Auth::user();
        $companyId = $this->getEffectiveCompanyId();

        if (!$product->track_stock) {
            return back()->withErrors(['stock' => 'Bestandsverfolgung ist für dieses Produkt nicht aktiviert.']);
        }

        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'adjustment_type' => 'required|in:set,add,subtract',
            'quantity' => 'required|numeric|min:0',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Verify warehouse belongs to company
        $warehouse = Warehouse::where('id', $validated['warehouse_id'])
            ->where('company_id', $companyId)
            ->firstOrFail();

        DB::transaction(function () use ($validated, $warehouse, $product, $user, $companyId) {
            // Get or create warehouse stock record
            $warehouseStock = WarehouseStock::firstOrCreate([
                'company_id' => $companyId,
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
            ], [
                'quantity' => 0,
                'reserved_quantity' => 0,
                'average_cost' => $product->cost_price ?? 0,
            ]);

            $oldQuantity = $warehouseStock->quantity;
            $newQuantity = match ($validated['adjustment_type']) {
                'set' => $validated['quantity'],
                'add' => $oldQuantity + $validated['quantity'],
                'subtract' => max(0, $oldQuantity - $validated['quantity']),
            };

            $quantityChange = $newQuantity - $oldQuantity;

            // Update warehouse stock
            $warehouseStock->update([
                'quantity' => $newQuantity,
            ]);

            // Update product total stock (sum of all warehouses)
            $totalStock = WarehouseStock::where('product_id', $product->id)
                ->where('company_id', $companyId)
                ->sum('quantity');

            $product->update([
                'stock_quantity' => (int) $totalStock,
            ]);

            // Create stock movement record
            StockMovement::create([
                'company_id' => $companyId,
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'created_by' => $user->id,
                'type' => StockMovement::TYPE_ADJUSTMENT,
                'quantity' => $quantityChange,
                'unit_cost' => $product->cost_price ?? 0,
                'total_cost' => abs($quantityChange) * ($product->cost_price ?? 0),
                'reason' => $validated['reason'],
                'notes' => $validated['notes'],
            ]);
        });

        return redirect()->route('products.show', $product)
            ->with('success', 'Bestand erfolgreich angepasst.');
    }
}
