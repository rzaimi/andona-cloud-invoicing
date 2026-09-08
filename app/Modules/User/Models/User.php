<?php

namespace App\Modules\User\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Document\Models\Document;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Offer\Models\Offer;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, HasUuids, Notifiable;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return UserFactory::new();
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
        'role',
        'status',
        'staff_number',
        'department',
        'job_title',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
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

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Platform super-admin (Spatie role). Distinct from company admins, who
     * use the `admin` role and the legacy users.role = 'admin' column.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(['super_admin', 'super-admin']);
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    public function canManageCompany(): bool
    {
        return $this->isAdmin();
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'linkable');
    }
}
