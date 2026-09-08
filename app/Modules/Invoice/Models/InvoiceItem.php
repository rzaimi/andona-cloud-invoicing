<?php

namespace App\Modules\Invoice\Models;

use App\Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'invoice_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'total',
        'unit',
        'tax_rate',
        'discount_type',
        'discount_value',
        'discount_amount',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isAbzug(): bool
    {
        return (float) $this->unit_price < 0;
    }

    public function calculateTotal(): void
    {
        $baseTotal = $this->quantity * $this->unit_price;

        // Abzug lines are already negative; a line discount on top would
        // invert the math (fixed min() against a negative base).
        if ($this->isAbzug()) {
            $this->discount_type = null;
            $this->discount_value = null;
            $this->discount_amount = 0;
            $this->total = $baseTotal;

            return;
        }

        $this->discount_amount = 0;
        if ($this->discount_type && $this->discount_value) {
            if ($this->discount_type === 'percentage') {
                $this->discount_amount = $baseTotal * ($this->discount_value / 100);
            } else {
                $this->discount_amount = min($this->discount_value, $baseTotal);
            }
        }

        $this->total = $baseTotal - $this->discount_amount;
    }

    public function loadFromProduct(Product $product): void
    {
        $this->product_id = $product->id;
        $this->description = $product->name.($product->description ? "\n".$product->description : '');
        $this->unit_price = $product->price;
        $this->unit = $product->unit;
        $this->tax_rate = $product->tax_rate ?? null; // Use product's tax rate if available
        $this->calculateTotal();
    }
}
