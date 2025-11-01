<?php

namespace App\Modules\Product\Models;

use App\Modules\Company\Models\Company;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseStock extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'warehouse_stocks';

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'product_id',
        'quantity',
        'reserved_quantity',
        'average_cost',
        'last_movement_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'reserved_quantity' => 'decimal:2',
        'average_cost' => 'decimal:2',
        'last_movement_at' => 'datetime',
    ];

    /**
     * Get the company that owns the warehouse stock.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the warehouse for this stock.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the product for this stock.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the available quantity (total - reserved).
     */
    public function getAvailableQuantityAttribute(): float
    {
        return $this->quantity - $this->reserved_quantity;
    }

    /**
     * Get the total value of this stock.
     */
    public function getTotalValueAttribute(): float
    {
        return $this->quantity * $this->average_cost;
    }

    /**
     * Check if stock is low based on product's minimum level.
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->product &&
            $this->product->track_stock &&
            $this->quantity <= $this->product->min_stock_level;
    }

    /**
     * Check if stock is out.
     */
    public function getIsOutOfStockAttribute(): bool
    {
        return $this->quantity <= 0;
    }
}
