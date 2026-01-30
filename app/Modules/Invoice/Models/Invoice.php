<?php

namespace App\Modules\Invoice\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\User\Models\User;
use App\Modules\Invoice\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory, HasUuids;

    // Reminder Level Constants
    const REMINDER_NONE = 0;
    const REMINDER_FRIENDLY = 1;
    const REMINDER_MAHNUNG_1 = 2;
    const REMINDER_MAHNUNG_2 = 3;
    const REMINDER_MAHNUNG_3 = 4;
    const REMINDER_INKASSO = 5;

    protected $fillable = [
        'number',
        'company_id',
        'customer_id',
        'user_id',
        'status',
        'issue_date',
        'service_date',
        'service_period_start',
        'service_period_end',
        'due_date',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'vat_regime',
        'total',
        'notes',
        'payment_method',
        'payment_terms',
        'layout_id',
        'company_snapshot',
        'reminder_level',
        'last_reminder_sent_at',
        'reminder_fee',
        'reminder_history',
        'is_correction',
        'corrects_invoice_id',
        'corrected_by_invoice_id',
        'correction_reason',
        'corrected_at',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'service_date' => 'date',
        'service_period_start' => 'date',
        'service_period_end' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'reminder_fee' => 'decimal:2',
        'last_reminder_sent_at' => 'datetime',
        'reminder_history' => 'array',
        'company_snapshot' => 'array',
        'is_correction' => 'boolean',
        'corrected_at' => 'datetime',
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

    /**
     * The original invoice that this correction corrects
     */
    public function correctsInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'corrects_invoice_id');
    }

    /**
     * The correction invoice that corrects this invoice
     */
    public function correctedByInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'corrected_by_invoice_id');
    }

    /**
     * Documents linked to this invoice
     */
    public function documents(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\App\Modules\Document\Models\Document::class, 'linkable');
    }

    /**
     * Payments for this invoice
     */
    public function payments(): HasMany
    {
        return $this->hasMany(\App\Modules\Payment\Models\Payment::class);
    }

    /**
     * Create a snapshot of company information for this invoice
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
        
        // Fallback: create snapshot from current company (for old invoices)
        return $this->createCompanySnapshot();
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Resolve route model binding with company security check
     * This ensures that even if a UUID is guessed, the invoice must belong to the user's company
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $invoice = parent::resolveRouteBinding($value, $field);
        
        // If invoice not found, return null (Laravel will handle 404)
        if (!$invoice) {
            return null;
        }
        
        // Additional security: Check if user is authenticated and has access
        if (auth()->check()) {
            $user = auth()->user();
            // Allow access if user belongs to same company OR has manage_companies permission
            if ($user->company_id !== $invoice->company_id && !$user->hasPermissionTo('manage_companies')) {
                abort(403, 'Unauthorized access to invoice');
            }
        }
        
        return $invoice;
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
        
        // Calculate tax amount
        // If VAT regime is not standard, tax is always 0
        if (($this->vat_regime ?? 'standard') !== 'standard') {
            $this->tax_amount = 0;
            $this->total = $this->subtotal;
            return;
        }

        // Calculate tax amount
        // If items have individual tax_rate, calculate per item
        // Otherwise, use invoice-level tax_rate for all items
        $taxAmount = 0;
        foreach ($this->items as $item) {
            $itemTaxRate = $item->tax_rate ?? $this->tax_rate;
            // Tax is calculated on the item total (which already includes discount)
            $taxAmount += $item->total * $itemTaxRate;
        }
        
        $this->tax_amount = $taxAmount;
        $this->total = $this->subtotal + $this->tax_amount;
    }

    /**
     * Get VAT breakdown by rate
     */
    public function getVatBreakdown(): array
    {
        if (($this->vat_regime ?? 'standard') !== 'standard') {
            return [];
        }

        $breakdown = [];
        foreach ($this->items as $item) {
            $rate = (float) ($item->tax_rate ?? $this->tax_rate);
            $rateKey = number_format($rate * 100, 2);
            
            if (!isset($breakdown[$rateKey])) {
                $breakdown[$rateKey] = [
                    'rate' => $rate,
                    'net_amount' => 0,
                    'tax_amount' => 0,
                ];
            }
            
            $breakdown[$rateKey]['net_amount'] += $item->total;
            $breakdown[$rateKey]['tax_amount'] += $item->total * $rate;
        }
        
        return $breakdown;
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

    /**
     * Get human-readable reminder level name
     */
    public function getReminderLevelNameAttribute(): string
    {
        return match($this->reminder_level) {
            self::REMINDER_NONE => 'Keine',
            self::REMINDER_FRIENDLY => 'Freundliche Erinnerung',
            self::REMINDER_MAHNUNG_1 => '1. Mahnung',
            self::REMINDER_MAHNUNG_2 => '2. Mahnung',
            self::REMINDER_MAHNUNG_3 => '3. Mahnung',
            self::REMINDER_INKASSO => 'Inkasso',
            default => 'Unbekannt',
        };
    }

    /**
     * Check if invoice is overdue
     */
    public function isOverdue(): bool
    {
        return $this->status !== 'paid' 
            && $this->status !== 'cancelled'
            && $this->due_date 
            && $this->due_date->isPast();
    }

    /**
     * Get days overdue
     */
    public function getDaysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }
        return abs($this->due_date->diffInDays(now()));
    }

    /**
     * Check if invoice can receive next reminder
     */
    public function canSendNextReminder(): bool
    {
        if ($this->status === 'paid' || $this->status === 'cancelled') {
            return false;
        }
        
        if ($this->reminder_level >= self::REMINDER_INKASSO) {
            return false; // Already at max level
        }

        return true;
    }

    /**
     * Get the next reminder level
     */
    public function getNextReminderLevel(): int
    {
        return min($this->reminder_level + 1, self::REMINDER_INKASSO);
    }

    /**
     * Add reminder to history and update invoice
     * Also creates invoice item for fee if applicable
     */
    public function addReminderToHistory(int $level, float $fee = 0): void
    {
        $history = $this->reminder_history ?? [];
        $history[] = [
            'level' => $level,
            'level_name' => $this->getReminderLevelNameForLevel($level),
            'sent_at' => now()->toDateTimeString(),
            'days_overdue' => $this->getDaysOverdue(),
            'fee' => $fee,
        ];
        
        $this->reminder_history = $history;
        $this->last_reminder_sent_at = now();
        $this->reminder_level = $level;
        
        if ($fee > 0) {
            // Update cumulative fee tracker
            $this->reminder_fee = ($this->reminder_fee ?? 0) + $fee;
            
            // Create invoice item for the fee
            $this->addReminderFeeItem($level, $fee);
            
            // Reload items to include the newly created fee item
            $this->load('items');
            
            // Recalculate totals (fees are net, so they're added to subtotal)
            $this->calculateTotals();
        }
    }

    /**
     * Add a reminder fee as an invoice item
     * Mahngebühren are VAT-exempt (0% tax) per German law
     */
    private function addReminderFeeItem(int $level, float $fee): void
    {
        // Get the highest sort_order to add fee at the end
        $maxSortOrder = $this->items()->max('sort_order') ?? -1;
        $nextSortOrder = $maxSortOrder + 1;
        
        // Get level name for description
        $levelName = $this->getReminderLevelNameForLevel($level);
        
        // Create fee item (fees are VAT-exempt, 0% tax)
        InvoiceItem::create([
            'invoice_id' => $this->id,
            'product_id' => null, // Fees are not products
            'description' => "Mahngebühr ({$levelName})",
            'quantity' => 1,
            'unit_price' => $fee,
            'unit' => 'Stk.',
            'tax_rate' => 0, // Mahngebühren are VAT-exempt
            'total' => $fee,
            'sort_order' => $nextSortOrder,
        ]);
    }

    /**
     * Get reminder level name for a specific level (public for use in commands)
     */
    public function getReminderLevelNameForLevel(int $level): string
    {
        return match($level) {
            self::REMINDER_NONE => 'Keine',
            self::REMINDER_FRIENDLY => 'Freundliche Erinnerung',
            self::REMINDER_MAHNUNG_1 => '1. Mahnung',
            self::REMINDER_MAHNUNG_2 => '2. Mahnung',
            self::REMINDER_MAHNUNG_3 => '3. Mahnung',
            self::REMINDER_INKASSO => 'Inkasso',
            default => 'Unbekannt',
        };
    }

    /**
     * Get total amount including reminder fees
     */
    public function getTotalWithFeesAttribute(): float
    {
        return $this->total + ($this->reminder_fee ?? 0);
    }

    /**
     * Check if invoice can be corrected
     */
    public function canBeCorrect(): bool
    {
        // Can only correct sent or paid invoices
        // Cannot correct if already corrected
        // Cannot correct if it's already a correction invoice
        return in_array($this->status, ['sent', 'paid', 'overdue']) 
            && !$this->corrected_by_invoice_id 
            && !$this->is_correction;
    }

    /**
     * Check if invoice is corrected
     */
    public function isCorrected(): bool
    {
        return $this->corrected_by_invoice_id !== null;
    }

    /**
     * Generate correction number based on original invoice
     */
    public function generateCorrectionNumber(): string
    {
        $company = $this->company;
        $prefix = $company->getSetting('invoice_prefix', 'RE-');
        
        // Extract the number from the original invoice
        if ($this->corrects_invoice_id && $this->correctsInvoice) {
            $originalNumber = $this->correctsInvoice->number;
            return str_replace($prefix, $prefix . 'STORNO-', $originalNumber);
        }
        
        // Fallback to regular numbering with STORNO prefix
        $year = now()->year;
        $lastInvoice = static::where('company_id', $this->company_id)
            ->whereYear('created_at', $year)
            ->orderBy('created_at', 'desc')
            ->first();

        $lastNumber = $lastInvoice ? (int) substr($lastInvoice->number, -4) : 0;

        return $prefix . 'STORNO-' . $year . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get total amount paid for this invoice
     */
    public function getPaidAmount(): float
    {
        return $this->payments()
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Get remaining balance for this invoice
     */
    public function getRemainingBalance(): float
    {
        return max(0, $this->total - $this->getPaidAmount());
    }

    /**
     * Check if invoice is fully paid
     */
    public function isFullyPaid(): bool
    {
        return $this->getPaidAmount() >= $this->total;
    }

    /**
     * Check if invoice is partially paid
     */
    public function isPartiallyPaid(): bool
    {
        $paidAmount = $this->getPaidAmount();
        return $paidAmount > 0 && $paidAmount < $this->total;
    }
}
