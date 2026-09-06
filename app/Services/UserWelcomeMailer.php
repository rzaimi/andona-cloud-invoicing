<?php

namespace App\Services;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class UserWelcomeMailer
{
    /**
     * Welcome a newly created company admin with a password-setup link.
     * The password itself is never mailed — the link carries a single-use
     * reset token (see routes/auth.php: only the token-consuming routes are
     * enabled, so this stays an admin-initiated flow).
     * Uses the company's SMTP when configured, otherwise the app default mailer.
     */
    public function send(User $user, Company $company): bool
    {
        $subject = "Willkommen bei AndoBill – Zugang für {$company->name}";

        try {
            $this->configureMailer($company);

            $token = Password::createToken($user);
            $setupUrl = route('password.reset', ['token' => $token, 'email' => $user->email]);
            $validDays = (int) ceil(config('auth.passwords.users.expire', 4320) / 1440);

            $mailable = (new Mailable)
                ->subject($subject)
                ->view('emails.user-welcome', [
                    'user' => $user,
                    'company' => $company,
                    'setupUrl' => $setupUrl,
                    'validDays' => $validDays,
                    'loginUrl' => url('/login'),
                ]);

            Mail::to($user->email, $user->name)->send($mailable);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Welcome email failed', [
                'user_id' => $user->id,
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function configureMailer(Company $company): void
    {
        if (! $company->smtp_host || ! $company->smtp_username) {
            return;
        }

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', $company->smtp_host);
        Config::set('mail.mailers.smtp.port', $company->smtp_port);
        Config::set('mail.mailers.smtp.username', $company->smtp_username);
        Config::set('mail.mailers.smtp.password', $company->smtp_password);
        Config::set('mail.mailers.smtp.encryption', $company->smtp_encryption ?: 'tls');
        Config::set('mail.from.address', $company->smtp_from_address ?: $company->email);
        Config::set('mail.from.name', $company->smtp_from_name ?: $company->name);
    }
}
