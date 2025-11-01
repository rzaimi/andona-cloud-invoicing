<?php

namespace App\Modules\Product\Controllers;

use App\Http\Controllers\Controller;

use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\StockMovement;
use App\Modules\Product\Models\Warehouse;
use App\Modules\Product\Models\WarehouseStock;
use App\Services\ContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WarehouseController extends Controller
{
    public function __construct(ContextService $contextService)
    {
        parent::__construct($contextService);
        $this->middleware('auth');
    }

    /**
     * Display a listing of warehouses.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $this->getEffectiveCompanyId();

        if (!$companyId) {
            return redirect()->route('dashboard')->with('error', 'Keine Firma zugeordnet.');
        }

        $query = Warehouse::where('company_id', $companyId)
            ->withCount(['warehouseStocks', 'stockMovements']);

        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('city', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $warehouses = $query->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->paginate(20);

        // Get statistics
        $stats = [
            'total_warehouses' => Warehouse::where('company_id', $companyId)->count(),
            'active_warehouses' => Warehouse::where('company_id', $companyId)->where('is_active', true)->count(),
            'total_stock_value' => WarehouseStock::where('company_id', $companyId)
                ->join('products', 'warehouse_stocks.product_id', '=', 'products.id')
                ->sum(DB::raw('warehouse_stocks.quantity * products.cost_price')),
            'low_stock_items' => WarehouseStock::where('company_id', $companyId)
                ->join('products', 'warehouse_stocks.product_id', '=', 'products.id')
                ->where('products.track_stock', true)
                ->whereColumn('warehouse_stocks.quantity', '<=', 'products.min_stock_level')
                ->count(),
        ];

        return Inertia::render('warehouse/index', [
            'warehouses' => $warehouses,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new warehouse.
     */
    public function create()
    {
        return Inertia::render('warehouse/create');
    }

    /**
     * Store a newly created warehouse in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $companyId = $this->getEffectiveCompanyId();

        if (!$companyId) {
            return redirect()->route('dashboard')->with('error', 'Keine Firma zugeordnet.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'address' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['company_id'] = $companyId;
        $validated['country'] = $validated['country'] ?? 'Deutschland';
        $validated['is_active'] = $validated['is_active'] ?? true;

        // If this is set as default, unset other defaults
        if ($validated['is_default'] ?? false) {
            Warehouse::where('company_id', $companyId)->update(['is_default' => false]);
        }

        Warehouse::create($validated);

        return redirect()->route('warehouse.index')->with('success', 'Lager erfolgreich erstellt.');
    }

    /**
     * Display the specified warehouse.
     */
    public function show(Warehouse $warehouse)
    {
        $user = Auth::user();

        $companyId = $this->getEffectiveCompanyId();
        if ($warehouse->company_id !== $companyId) {
            abort(403);
        }

        $warehouse->load(['warehouseStocks.product', 'stockMovements.product', 'stockMovements.user']);

        // Get warehouse statistics
        $stats = [
            'total_products' => $warehouse->warehouseStocks()->where('quantity', '>', 0)->count(),
            'total_stock_value' => $warehouse->total_stock_value,
            'low_stock_items' => $warehouse->warehouseStocks()
                ->join('products', 'warehouse_stocks.product_id', '=', 'products.id')
                ->where('products.track_stock', true)
                ->whereColumn('warehouse_stocks.quantity', '<=', 'products.min_stock_level')
                ->count(),
            'out_of_stock_items' => $warehouse->warehouseStocks()->where('quantity', '<=', 0)->count(),
        ];

        // Get recent stock movements
        $recentMovements = $warehouse->stockMovements()
            ->with(['product', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get low stock items
        $lowStockItems = $warehouse->warehouseStocks()
            ->with('product')
            ->join('products', 'warehouse_stocks.product_id', '=', 'products.id')
            ->where('products.track_stock', true)
            ->whereColumn('warehouse_stocks.quantity', '<=', 'products.min_stock_level')
            ->select('warehouse_stocks.*')
            ->limit(10)
            ->get();

        return Inertia::render('warehouse/show', [
            'warehouse' => $warehouse,
            'stats' => $stats,
            'recentMovements' => $recentMovements,
            'lowStockItems' => $lowStockItems,
        ]);
    }

    /**
     * Show the form for editing the specified warehouse.
     */
    public function edit(Warehouse $warehouse)
    {
        $user = Auth::user();

        $companyId = $this->getEffectiveCompanyId();
        if ($warehouse->company_id !== $companyId) {
            abort(403);
        }

        return Inertia::render('warehouse/edit', [
            'warehouse' => $warehouse,
        ]);
    }

    /**
     * Update the specified warehouse in storage.
     */
    public function update(Request $request, Warehouse $warehouse)
    {
        $user = Auth::user();

        $companyId = $this->getEffectiveCompanyId();
        if ($warehouse->company_id !== $companyId) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'address' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // If this is set as default, unset other defaults
        if ($validated['is_default'] ?? false) {
            $companyId = $this->getEffectiveCompanyId();
            Warehouse::where('company_id', $companyId)
                ->where('id', '!=', $warehouse->id)
                ->update(['is_default' => false]);
        }

        $warehouse->update($validated);

        return redirect()->route('warehouse.index')->with('success', 'Lager erfolgreich aktualisiert.');
    }

    /**
     * Remove the specified warehouse from storage.
     */
    public function destroy(Warehouse $warehouse)
    {
        $user = Auth::user();

        $companyId = $this->getEffectiveCompanyId();
        if ($warehouse->company_id !== $companyId) {
            abort(403);
        }

        // Check if warehouse has stock
        if ($warehouse->warehouseStocks()->where('quantity', '>', 0)->exists()) {
            return back()->withErrors(['warehouse' => 'Lager kann nicht gelöscht werden, da es noch Bestände enthält.']);
        }

        $warehouse->delete();

        return redirect()->route('warehouse.index')->with('success', 'Lager erfolgreich gelöscht.');
    }

    /**
     * Show stock adjustments form.
     */
    public function adjustments(Request $request)
    {
        $user = Auth::user();
        $companyId = $this->getEffectiveCompanyId();

        if (!$companyId) {
            return redirect()->route('dashboard')->with('error', 'Keine Firma zugeordnet.');
        }

        $warehouses = Warehouse::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        $products = Product::where('company_id', $companyId)
            ->where('status', 'active')
            ->where('track_stock', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('warehouse/adjustments', [
            'warehouses' => $warehouses,
            'products' => $products,
            'selectedProduct' => $request->get('product'),
        ]);
    }

    /**
     * Process stock adjustment.
     */
    public function processAdjustment(Request $request)
    {
        $user = Auth::user();
        $companyId = $this->getEffectiveCompanyId();

        if (!$companyId) {
            return redirect()->route('dashboard')->with('error', 'Keine Firma zugeordnet.');
        }

        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'required|exists:products,id',
            'adjustment_type' => 'required|in:set,add,subtract',
            'quantity' => 'required|numeric|min:0',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Verify warehouse and product belong to company
        $warehouse = Warehouse::where('id', $validated['warehouse_id'])
            ->where('company_id', $companyId)
            ->firstOrFail();

        $product = Product::where('id', $validated['product_id'])
            ->where('company_id', $companyId)
            ->where('track_stock', true)
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
                'last_movement_at' => now(),
            ]);

            // Update product total stock
            $product->update([
                'stock_quantity' => $product->stock_quantity + $quantityChange,
            ]);

            // Create stock movement record
            StockMovement::create([
                'company_id' => $companyId,
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'user_id' => $user->id,
                'type' => StockMovement::TYPE_ADJUSTMENT,
                'quantity' => $quantityChange,
                'unit_cost' => $product->cost_price ?? 0,
                'total_cost' => $quantityChange * ($product->cost_price ?? 0),
                'reason' => $validated['reason'],
                'notes' => $validated['notes'],
            ]);
        });

        return redirect()->route('warehouse.index')
            ->with('success', 'Bestandskorrektur erfolgreich durchgeführt.');
    }
}

