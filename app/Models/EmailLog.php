<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    use HasFactory, HasUuids;

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

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Company\Models\Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Customer\Models\Customer::class);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Get the related model (Invoice, Offer, etc.)
     */
    public function related()
    {
        if ($this->related_type && $this->related_id) {
            $class = "App\\Modules\\" . $this->related_type . "\\Models\\" . $this->related_type;
            if (class_exists($class)) {
                return $class::find($this->related_id);
            }
        }
        return null;
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
            'welcome' => 'Willkommen',
            default => ucfirst($this->type),
        };
    }
}
