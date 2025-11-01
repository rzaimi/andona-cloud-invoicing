<?php

namespace App\Modules\Product\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Invoice\Models\InvoiceItem;
use App\Modules\Offer\Models\OfferItem;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'company_id',
        'number',
        'name',
        'description',
        'unit',
        'price',
        'cost_price',
        'category',
        'sku',
        'barcode',
        'tax_rate',
        'stock_quantity',
        'min_stock_level',
        'track_stock',
        'is_service',
        'status',
        'custom_fields',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'track_stock' => 'boolean',
        'is_service' => 'boolean',
        'custom_fields' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->number)) {
                $product->number = $product->generateProductNumber();
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function offerItems(): HasMany
    {
        return $this->hasMany(OfferItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeServices($query)
    {
        return $query->where('is_service', true);
    }

    public function scopeProducts($query)
    {
        return $query->where('is_service', false);
    }

    public function scopeLowStock($query)
    {
        return $query->where('track_stock', true)
                    ->whereColumn('stock_quantity', '<=', 'min_stock_level');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function generateProductNumber(): string
    {
        $company = $this->company ?? Company::find($this->company_id);
        $prefix = $company->getSetting('product_prefix', 'PR');
        $year = date('Y');

        $lastProduct = static::where('company_id', $this->company_id)
            ->where('number', 'like', "{$prefix}-{$year}-%")
            ->orderBy('number', 'desc')
            ->first();

        if ($lastProduct) {
            $lastNumber = (int) substr($lastProduct->number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $nextNumber);
    }

    public function isLowStock(): bool
    {
        return $this->track_stock && $this->stock_quantity <= $this->min_stock_level;
    }

    public function canSell($quantity = 1): bool
    {
        if ($this->is_service || !$this->track_stock) {
            return true;
        }

        return $this->stock_quantity >= $quantity;
    }

    public function reduceStock($quantity): void
    {
        if ($this->track_stock && !$this->is_service) {
            $this->decrement('stock_quantity', $quantity);
        }
    }

    public function increaseStock($quantity): void
    {
        if ($this->track_stock && !$this->is_service) {
            $this->increment('stock_quantity', $quantity);
        }
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2, ',', '.') . ' €';
    }

    public function getProfitMarginAttribute(): ?float
    {
        if (!$this->cost_price || $this->cost_price == 0) {
            return null;
        }

        return (($this->price - $this->cost_price) / $this->cost_price) * 100;
    }
}
