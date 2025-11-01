<?php

namespace App\Modules\Product\Models;

use App\Modules\Company\Models\Company;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'address',
        'postal_code',
        'city',
        'country',
        'contact_person',
        'phone',
        'email',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the company that owns the warehouse.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the stock movements for this warehouse.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get the warehouse stocks.
     */
    public function warehouseStocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    /**
     * Scope a query to only include active warehouses.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the full address as a single string.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->postal_code . ' ' . $this->city,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get the total value of stock in this warehouse.
     */
    public function getTotalStockValueAttribute(): float
    {
        return $this->warehouseStocks()
            ->join('products', 'warehouse_stocks.product_id', '=', 'products.id')
            ->sum(\DB::raw('warehouse_stocks.quantity * products.cost_price'));
    }

    /**
     * Get the number of different products in this warehouse.
     */
    public function getProductCountAttribute(): int
    {
        return $this->warehouseStocks()->where('quantity', '>', 0)->count();
    }
}

