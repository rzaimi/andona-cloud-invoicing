<?php

namespace App\Modules\Offer\Models;

use App\Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'offer_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'total',
        'unit',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function calculateTotal(): void
    {
        $this->total = $this->quantity * $this->unit_price;
    }

    public function loadFromProduct(Product $product): void
    {
        $this->product_id = $product->id;
        $this->description = $product->name . ($product->description ? "\n" . $product->description : '');
        $this->unit_price = $product->price;
        $this->unit = $product->unit;
        $this->calculateTotal();
    }
}
