<?php

namespace App\Http\Requests\Auth;

use App\Models\LoginAttempt;
use App\Modules\User\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $email = $this->string('email');
        
        // Check account status before attempting authentication
        $user = User::where('email', $email)->first();
        
        if ($user) {
            // Check if account is active
            if ($user->status !== 'active') {
                $this->logLoginAttempt($email, $user->id, 'account_inactive');
                throw ValidationException::withMessages([
                    'email' => 'Ihr Konto ist nicht aktiv. Bitte kontaktieren Sie den Administrator.',
                ]);
            }

            // Check if company is active (if user belongs to a company)
            if ($user->company_id) {
                // Load company relationship if not already loaded
                if (!$user->relationLoaded('company')) {
                    $user->load('company');
                }
                
                if ($user->company && $user->company->status !== 'active') {
                    $this->logLoginAttempt($email, $user->id, 'company_inactive');
                    throw ValidationException::withMessages([
                        'email' => 'Ihre Firma ist nicht aktiv. Bitte kontaktieren Sie den Administrator.',
                    ]);
                }
            }
        }

        // Attempt authentication
        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            
            // Log failed attempt
            $this->logLoginAttempt($email, $user?->id, 'invalid_credentials');
            
            // Progressive lockout: increase lockout time based on failed attempts
            $failedAttempts = LoginAttempt::getFailedAttemptsCount($email, 15);
            $lockoutMinutes = $this->getLockoutMinutes($failedAttempts);
            
            if ($lockoutMinutes > 0) {
                RateLimiter::hit($this->throttleKey(), $lockoutMinutes * 60);
            }

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Log successful attempt
        $this->logLoginAttempt($email, Auth::user()->id, 'success');
        
        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Log login attempt to database
     */
    protected function logLoginAttempt(string $email, ?string $userId, string $status, ?string $failureReason = null): void
    {
        LoginAttempt::create([
            'email' => $email,
            'user_id' => $userId,
            'ip_address' => $this->ip(),
            'user_agent' => $this->userAgent(),
            'status' => $status === 'success' ? 'success' : 'failed',
            'failure_reason' => $failureReason ?? ($status === 'success' ? null : $status),
            'attempted_at' => now(),
        ]);
    }

    /**
     * Get lockout minutes based on failed attempts
     * Progressive: 5 attempts = 5 min, 10 attempts = 15 min, 15+ attempts = 30 min
     */
    protected function getLockoutMinutes(int $failedAttempts): int
    {
        if ($failedAttempts >= 15) {
            return 30;
        } elseif ($failedAttempts >= 10) {
            return 15;
        } elseif ($failedAttempts >= 5) {
            return 5;
        }
        
        return 0;
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $key = $this->throttleKey();
        $maxAttempts = 5;
        
        // Check IP-based rate limiting as well
        $ipKey = 'login:ip:' . $this->ip();
        $ipAttempts = LoginAttempt::getFailedAttemptsCountForIp($this->ip(), 15);
        
        // If IP has too many failed attempts, block it
        if ($ipAttempts >= 20) {
            RateLimiter::hit($ipKey, 60 * 60); // 1 hour lockout for IP
            $seconds = RateLimiter::availableIn($ipKey);
            
            throw ValidationException::withMessages([
                'email' => __('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        if (! RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
