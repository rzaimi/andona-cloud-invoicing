<?php

namespace App\Modules\Offer\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\User\Models\User;
use App\Services\NumberFormatService;
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
        'company_snapshot',
        'converted_to_invoice_id',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'company_snapshot' => 'array',
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
        // Load items if not already loaded
        if (!$this->relationLoaded('items')) {
            $this->load('items');
        }
        
        // If no items, set totals to zero
        if ($this->items->isEmpty()) {
            $this->subtotal = 0;
            $this->tax_amount = 0;
            $this->total = 0;
            return;
        }
        
        // Calculate subtotal (sum of all items - items already have discount applied)
        $this->subtotal = $this->items->sum('total');
        
        // Calculate tax amount per item (supports mixed tax rates)
        // If items have individual tax_rate, calculate per item
        // Otherwise, use offer-level tax_rate for all items
        $taxAmount = 0;
        foreach ($this->items as $item) {
            $itemTaxRate = $item->tax_rate ?? $this->tax_rate;
            // Tax is calculated on the item total (which already includes discount)
            $taxAmount += $item->total * $itemTaxRate;
        }
        
        $this->tax_amount = $taxAmount;
        $this->total = $this->subtotal + $this->tax_amount;
    }

    public function generateNumber(): string
    {
        $company = $this->company;
        $svc     = new NumberFormatService();
        $format  = $svc->normaliseToFormat(
            $company->getSetting('offer_number_format')
                ?? $company->getSetting('offer_prefix', 'AN-')
        );

        $numbers = static::where('company_id', $this->company_id)->pluck('number');

        return $svc->next($format, $numbers);
    }

    public function isExpired(): bool
    {
        return $this->valid_until < now()->toDateString() && $this->status !== 'accepted';
    }

    public function canBeConverted(): bool
    {
        return $this->status === 'accepted' && !$this->converted_to_invoice_id;
    }

    /**
     * Create a snapshot of company information for this offer
     */
    public function createCompanySnapshot(): array
    {
        $company = $this->company;
        
        return [
            'name' => $company->name,
            'email' => $company->email,
            'phone' => $company->phone,
            'address' => $company->address,
            'postal_code' => $company->postal_code,
            'city' => $company->city,
            'country' => $company->country ?? 'Deutschland',
            'tax_number' => $company->tax_number,
            'vat_number' => $company->vat_number,
            'commercial_register' => $company->commercial_register,
            'managing_director' => $company->managing_director,
            'website' => $company->website,
            'logo' => $company->logo,
            'bank_name' => $company->bank_name,
            'bank_iban' => $company->bank_iban,
            'bank_bic' => $company->bank_bic,
            'snapshot_date' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get company snapshot or fallback to current company data
     */
    public function getCompanySnapshot(): array
    {
        if ($this->company_snapshot) {
            return $this->company_snapshot;
        }
        
        // Fallback: create snapshot from current company (for old offers)
        return $this->createCompanySnapshot();
    }
}
