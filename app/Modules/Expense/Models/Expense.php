<?php

namespace App\Modules\Expense\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Expense\Models\ExpenseCategory;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'company_id',
        'user_id',
        'category_id',
        'title',
        'description',
        'amount',
        'vat_rate',
        'vat_amount',
        'net_amount',
        'expense_date',
        'payment_method',
        'reference',
        'receipt_path',
        'receipt_original_filename',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'vat_rate' => 'decimal:4',
        'vat_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($expense) {
            // Calculate VAT and net amount
            // amount is the total amount (including VAT)
            // vat_amount = amount * vat_rate (VAT portion)
            // net_amount = amount - vat_amount (amount without VAT)
            $expense->vat_amount = round($expense->amount * $expense->vat_rate, 2);
            $expense->net_amount = round($expense->amount - $expense->vat_amount, 2);
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('expense_date', [$startDate, $endDate]);
    }
}

