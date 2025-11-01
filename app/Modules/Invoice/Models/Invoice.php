<?php

namespace App\Modules\Invoice\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'number',
        'company_id',
        'customer_id',
        'user_id',
        'status',
        'issue_date',
        'due_date',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'notes',
        'payment_method',
        'payment_terms',
        'layout_id',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
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
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function layout(): BelongsTo
    {
        return $this->belongsTo(InvoiceLayout::class);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
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
        $prefix = $company->getSetting('invoice_prefix', 'RE-');

        $year = now()->year;
        $lastInvoice = static::where('company_id', $this->company_id)
            ->whereYear('created_at', $year)
            ->orderBy('created_at', 'desc')
            ->first();

        $lastNumber = $lastInvoice ? (int) substr($lastInvoice->number, -4) : 0;

        return $prefix . $year . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }
}
