<?php

namespace App\Modules\Company\Models;

use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Models\InvoiceLayout;
use App\Modules\Offer\Models\Offer;
use App\Modules\Offer\Models\OfferLayout;
use App\Modules\User\Models\User;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory, HasUuids;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return CompanyFactory::new();
    }

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'postal_code',
        'city',
        'country',
        'tax_number',
        'vat_number',
        'commercial_register',
        'managing_director',
        'bank_name',
        'bank_iban',
        'bank_bic',
        'website',
        'logo',
        'status',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function invoiceLayouts(): HasMany
    {
        return $this->hasMany(InvoiceLayout::class);
    }

    public function offerLayouts(): HasMany
    {
        return $this->hasMany(OfferLayout::class);
    }

    public function defaultInvoiceLayout()
    {
        return $this->hasOne(InvoiceLayout::class)->where('is_default', true);
    }

    public function defaultOfferLayout()
    {
        return $this->hasOne(OfferLayout::class)->where('is_default', true);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(CompanySetting::class);
    }

    public function getDefaultSettings(): array
    {
        return [
            'currency' => 'EUR',
            'tax_rate' => 0.19,
            'reduced_tax_rate' => 0.07,
            'invoice_prefix' => 'RE-',
            'offer_prefix' => 'AN-',
            'date_format' => 'd.m.Y',
            'payment_terms' => 14,
            'language' => 'de',
            'timezone' => 'Europe/Berlin',
            'decimal_separator' => ',',
            'thousands_separator' => '.',
            'invoice_footer' => 'Vielen Dank für Ihr Vertrauen!',
            'offer_footer' => 'Wir freuen uns auf Ihre Rückmeldung!',
            'payment_methods' => json_encode(['Überweisung', 'SEPA-Lastschrift', 'PayPal']),
            'offer_validity_days' => 30,
        ];
    }

    public function getSetting(string $key, $default = null)
    {
        $setting = $this->settings()->where('key', $key)->first();

        if ($setting) {
            return match($setting->type) {
                'integer' => (int) $setting->value,
                'decimal' => (float) $setting->value,
                'boolean' => (bool) $setting->value,
                'json' => json_decode($setting->value, true),
                default => $setting->value,
            };
        }

        $defaults = $this->getDefaultSettings();
        return $defaults[$key] ?? $default;
    }

    public function setSetting(string $key, $value, string $type = 'string', string $description = null): void
    {
        $this->settings()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'description' => $description,
            ]
        );
    }
}
