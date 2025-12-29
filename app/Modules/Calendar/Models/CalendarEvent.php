<?php

namespace App\Modules\Calendar\Models;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CalendarEvent extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'company_id',
        'user_id',
        'title',
        'type',
        'date',
        'time',
        'description',
        'location',
        'related_id',
        'related_type',
        'is_recurring',
        'recurrence_pattern',
        'recurrence_end_date',
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'string',
        'is_recurring' => 'boolean',
        'recurrence_end_date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeAppointments($query)
    {
        return $query->where('type', 'appointment');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', now()->toDateString());
    }
}
