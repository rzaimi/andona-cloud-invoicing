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
        'website',
        'logo',
        'status',
        'is_default',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    /**
     * The accessors to append to the model's array form.
     * These ensure SMTP and bank settings are included in JSON serialization.
     */
    protected $appends = [
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'smtp_from_address',
        'smtp_from_name',
        'bank_name',
        'bank_iban',
        'bank_bic',
    ];

    protected static function boot()
    {
        parent::boot();

        // Ensure only one default company can exist
        static::saving(function ($company) {
            if ($company->is_default && $company->isDirty('is_default')) {
                static::where('is_default', true)
                    ->where('id', '!=', $company->id)
                    ->update(['is_default' => false]);
            }
        });
    }

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

    /**
     * Get the default company (the one super admins should start with)
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Set this company as default (and unset others)
     */
    public function setAsDefault(): void
    {
        // Unset all other default companies
        static::where('is_default', true)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);
        
        // Set this one as default
        $this->update(['is_default' => true]);
    }

    /**
     * Scope to get default company
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Accessor methods for SMTP settings (normalized to company_settings)
     */
    public function getSmtpHostAttribute(): ?string
    {
        return $this->getSetting('smtp_host');
    }

    public function getSmtpPortAttribute(): ?int
    {
        return $this->getSetting('smtp_port');
    }

    public function getSmtpUsernameAttribute(): ?string
    {
        return $this->getSetting('smtp_username');
    }

    public function getSmtpPasswordAttribute(): ?string
    {
        return $this->getSetting('smtp_password');
    }

    public function getSmtpEncryptionAttribute(): ?string
    {
        return $this->getSetting('smtp_encryption');
    }

    public function getSmtpFromAddressAttribute(): ?string
    {
        return $this->getSetting('smtp_from_address');
    }

    public function getSmtpFromNameAttribute(): ?string
    {
        return $this->getSetting('smtp_from_name');
    }

    /**
     * Accessor methods for bank settings (normalized to company_settings)
     */
    public function getBankNameAttribute(): ?string
    {
        return $this->getSetting('bank_name');
    }

    public function getBankIbanAttribute(): ?string
    {
        return $this->getSetting('bank_iban');
    }

    public function getBankBicAttribute(): ?string
    {
        return $this->getSetting('bank_bic');
    }

    /**
     * Mutator methods to save SMTP settings to company_settings
     */
    public function setSmtpHostAttribute($value): void
    {
        $this->setSetting('smtp_host', $value, 'string');
    }

    public function setSmtpPortAttribute($value): void
    {
        $this->setSetting('smtp_port', $value, 'integer');
    }

    public function setSmtpUsernameAttribute($value): void
    {
        $this->setSetting('smtp_username', $value, 'string');
    }

    public function setSmtpPasswordAttribute($value): void
    {
        $this->setSetting('smtp_password', $value, 'string');
    }

    public function setSmtpEncryptionAttribute($value): void
    {
        $this->setSetting('smtp_encryption', $value, 'string');
    }

    public function setSmtpFromAddressAttribute($value): void
    {
        $this->setSetting('smtp_from_address', $value, 'string');
    }

    public function setSmtpFromNameAttribute($value): void
    {
        $this->setSetting('smtp_from_name', $value, 'string');
    }

    /**
     * Mutator methods to save bank settings to company_settings
     */
    public function setBankNameAttribute($value): void
    {
        $this->setSetting('bank_name', $value, 'string');
    }

    public function setBankIbanAttribute($value): void
    {
        $this->setSetting('bank_iban', $value, 'string');
    }

    public function setBankBicAttribute($value): void
    {
        $this->setSetting('bank_bic', $value, 'string');
    }

    /**
     * Set SMTP settings from array
     */
    public function setSmtpSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            if (in_array($key, ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption', 'smtp_from_address', 'smtp_from_name'])) {
                $type = $key === 'smtp_port' ? 'integer' : 'string';
                $this->setSetting($key, $value, $type);
            }
        }
    }

    /**
     * Set bank settings from array
     */
    public function setBankSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            if (in_array($key, ['bank_name', 'bank_iban', 'bank_bic'])) {
                $this->setSetting($key, $value, 'string');
            }
        }
    }
}
