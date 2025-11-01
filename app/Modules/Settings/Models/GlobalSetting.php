<?php

namespace App\Modules\Settings\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GlobalSetting extends Model
{
    use HasFactory;

    protected $table = 'global_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    public function getValueAttribute($value)
    {
        return match($this->type) {
            'integer' => (int) $value,
            'decimal', 'float' => (float) $value,
            'boolean' => (bool) $value,
            'json', 'array' => json_decode($value, true),
            default => $value,
        };
    }

    public function setValueAttribute($value): void
    {
        $this->attributes['value'] = match($this->type) {
            'json', 'array' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };
    }
}

