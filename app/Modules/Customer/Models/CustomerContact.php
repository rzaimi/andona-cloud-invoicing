<?php

namespace App\Modules\Customer\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A contact person (Ansprechpartner) of a customer.
 *
 * Invariants kept by the model events below:
 *  - a customer with contacts has exactly one primary contact;
 *  - customers.contact_person mirrors the primary contact's name, so PDFs,
 *    the E-Rechnung and exports keep reading the single column.
 */
class CustomerContact extends Model
{
    use HasUuids;

    public const PURPOSE_INVOICE = 'invoice';

    public const PURPOSE_REMINDER = 'reminder';

    public const PURPOSE_OFFER = 'offer';

    /** Purpose → flag column. */
    public const PURPOSE_COLUMNS = [
        self::PURPOSE_INVOICE => 'receives_invoices',
        self::PURPOSE_REMINDER => 'receives_reminders',
        self::PURPOSE_OFFER => 'receives_offers',
    ];

    protected $fillable = [
        'company_id',
        'customer_id',
        'name',
        'email',
        'title',
        'phone',
        'is_primary',
        'receives_invoices',
        'receives_reminders',
        'receives_offers',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'receives_invoices' => 'boolean',
        'receives_reminders' => 'boolean',
        'receives_offers' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (CustomerContact $contact) {
            $siblings = static::where('customer_id', $contact->customer_id);

            if (! (clone $siblings)->exists()) {
                $contact->is_primary = true;
            }

            if (! $contact->sort_order) {
                $contact->sort_order = ((int) (clone $siblings)->max('sort_order')) + 1;
            }
        });

        static::saved(function (CustomerContact $contact) {
            $siblings = static::where('customer_id', $contact->customer_id);

            if ($contact->is_primary) {
                (clone $siblings)
                    ->where('id', '!=', $contact->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            } elseif (! (clone $siblings)->where('is_primary', true)->exists()) {
                static::promoteNextPrimary($contact->customer_id);
            }

            static::syncCustomerContactPerson($contact->customer_id);
        });

        static::deleted(function (CustomerContact $contact) {
            if ($contact->is_primary) {
                static::promoteNextPrimary($contact->customer_id);
            }

            static::syncCustomerContactPerson($contact->customer_id);
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    private static function promoteNextPrimary(string $customerId): void
    {
        $next = static::where('customer_id', $customerId)
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->first();

        if ($next) {
            static::whereKey($next->id)->update(['is_primary' => true]);
        }
    }

    private static function syncCustomerContactPerson(string $customerId): void
    {
        $primaryName = static::where('customer_id', $customerId)
            ->where('is_primary', true)
            ->value('name');

        Customer::whereKey($customerId)->update(['contact_person' => $primaryName]);
    }
}
