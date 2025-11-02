<?php

namespace App\Modules\Offer\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Offer extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'number',
        'company_id',
        'customer_id',
        'user_id',
        'status',
        'issue_date',
        'valid_until',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'notes',
        'terms_conditions',
        'validity_days',
        'layout_id',
        'converted_to_invoice_id',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OfferItem::class)->orderBy('sort_order');
    }

    public function layout(): BelongsTo
    {
        return $this->belongsTo(OfferLayout::class);
    }

    public function convertedToInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'converted_to_invoice_id');
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['draft', 'sent']);
    }

    public function scopeExpired($query)
    {
        return $query->where('valid_until', '<', now()->toDateString())
                    ->where('status', '!=', 'accepted');
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum('total');
        $this->tax_amount = $this->subtotal * $this->tax_rate;
        $this->total = $this->subtotal + $this->tax_amount;
    }

    public function generateNumber(): string
    {
        $company = $this->company;
        $prefix = $company->getSetting('offer_prefix', 'AN-');

        $year = now()->year;
        $lastOffer = static::where('company_id', $this->company_id)
            ->whereYear('created_at', $year)
            ->orderBy('created_at', 'desc')
            ->first();

        $lastNumber = $lastOffer ? (int) substr($lastOffer->number, -4) : 0;

        return $prefix . $year . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    public function isExpired(): bool
    {
        return $this->valid_until < now()->toDateString() && $this->status !== 'accepted';
    }

    public function canBeConverted(): bool
    {
        return $this->status === 'accepted' && !$this->converted_to_invoice_id;
    }
}
