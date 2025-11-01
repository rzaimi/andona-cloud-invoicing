<?php

namespace App\Modules\Company\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySetting extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'company_id',
        'key',
        'value',
        'type',
        'description',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getValueAttribute($value)
    {
        return match($this->type) {
            'integer' => (int) $value,
            'decimal' => (float) $value,
            'boolean' => (bool) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    public function setValueAttribute($value): void
    {
        $this->attributes['value'] = match($this->type) {
            'json' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };
    }
}
