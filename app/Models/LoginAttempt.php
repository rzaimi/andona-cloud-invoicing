<?php

namespace App\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginAttempt extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'email',
        'user_id',
        'ip_address',
        'user_agent',
        'status',
        'failure_reason',
        'attempted_at',
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get recent failed attempts for an email
     */
    public function scopeRecentFailedForEmail($query, string $email, int $minutes = 15)
    {
        return $query->where('email', $email)
            ->where('status', 'failed')
            ->where('attempted_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Scope to get recent failed attempts for an IP
     */
    public function scopeRecentFailedForIp($query, string $ipAddress, int $minutes = 15)
    {
        return $query->where('ip_address', $ipAddress)
            ->where('status', 'failed')
            ->where('attempted_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Get count of failed attempts for email in the last X minutes
     */
    public static function getFailedAttemptsCount(string $email, int $minutes = 15): int
    {
        return static::recentFailedForEmail($email, $minutes)->count();
    }

    /**
     * Get count of failed attempts for IP in the last X minutes
     */
    public static function getFailedAttemptsCountForIp(string $ipAddress, int $minutes = 15): int
    {
        return static::recentFailedForIp($ipAddress, $minutes)->count();
    }
}
