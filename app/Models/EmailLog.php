<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EmailLog extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'email_logs';

    protected $fillable = [
        'company_id',
        'customer_id',
        'recipient_email',
        'recipient_name',
        'subject',
        'body',
        'type',
        'related_type',
        'related_id',
        'status',
        'error_message',
        'metadata',
        'sent_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
    ];

    /**
     * Get the company that owns this email log
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Company\Models\Company::class);
    }

    /**
     * Get the customer this email was sent to
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Customer\Models\Customer::class);
    }

    /**
     * Get the related model (Invoice, Offer, etc.)
     */
    public function related(): MorphTo
    {
        return $this->morphTo('related', 'related_type', 'related_id');
    }

    /**
     * Scope to filter by company
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Get human-readable type name
     */
    public function getTypeNameAttribute(): string
    {
        return match($this->type) {
            'invoice' => 'Rechnung',
            'offer' => 'Angebot',
            'mahnung' => 'Mahnung',
            'reminder' => 'Erinnerung',
            'payment_received' => 'Zahlungsbestätigung',
            'welcome' => 'Willkommens-E-Mail',
            default => ucfirst($this->type),
        };
    }

    /**
     * Check if email was sent successfully
     */
    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    /**
     * Check if email failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}



