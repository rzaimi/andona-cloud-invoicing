<?php

namespace App\Modules\Customer\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Offer\Models\Offer;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'company_id',
        'number',
        'name',
        'email',
        'phone',
        'address',
        'postal_code',
        'city',
        'country',
        'tax_number',
        'vat_number',
        'contact_person',
        'customer_type',
        'status',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->number)) {
                $customer->number = $customer->generateCustomerNumber();
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeBusiness($query)
    {
        return $query->where('customer_type', 'business');
    }

    public function scopePrivate($query)
    {
        return $query->where('customer_type', 'private');
    }

    public function generateCustomerNumber(): string
    {
        $company = $this->company ?? Company::find($this->company_id);
        $prefix = $company->getSetting('customer_prefix', 'KU');
        $year = date('Y');

        $lastCustomer = static::where('company_id', $this->company_id)
            ->where('number', 'like', "{$prefix}-{$year}-%")
            ->orderBy('number', 'desc')
            ->first();

        if ($lastCustomer) {
            $lastNumber = (int) substr($lastCustomer->number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $nextNumber);
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->postal_code . ' ' . $this->city,
            $this->country
        ]);

        return implode(', ', $parts);
    }

    public function requiresVat(): bool
    {
        return $this->customer_type === 'business' &&
               $this->country === 'Deutschland' &&
               empty($this->vat_number);
    }

    public function getTotalInvoicesAttribute(): int
    {
        return $this->invoices()->count();
    }

    public function getTotalRevenueAttribute(): float
    {
        return $this->invoices()->where('status', 'paid')->sum('total');
    }

    public function getOutstandingAmountAttribute(): float
    {
        return $this->invoices()->whereIn('status', ['sent', 'overdue'])->sum('total');
    }
}
