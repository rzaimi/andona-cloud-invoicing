<?php

namespace App\Http\Controllers;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use App\Services\SettingsService;
use App\Traits\ResizesCompanyLogo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CompanyWizardController extends Controller
{
    use ResizesCompanyLogo;
    protected SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Old bookmark URL. Send visitors to the canonical wizard page.
     */
    public function start()
    {
        return redirect()->route('companies.wizard.show');
    }

    /**
     * Render the wizard page. All form state lives in React; the backend only
     * provides the rendered shell plus any validation errors from a previous
     * complete() attempt (stored in the session flash by Laravel).
     */
    public function show()
    {
        return Inertia::render('companies/wizard');
    }

    /**
     * Validate and create the company from the full wizard payload.
     *
     * The wizard is purely client-side – every step uses React state. The only
     * time the backend is called is here, on the final "Firma erstellen" click.
     */
    public function complete(Request $request)
    {
        $configureSmtp = filter_var(
            $request->input('email_settings.configure_smtp', false),
            FILTER_VALIDATE_BOOLEAN
        );
        $createUser = filter_var(
            $request->input('first_user.create_user', false),
            FILTER_VALIDATE_BOOLEAN
        );
        $sendWelcomeEmail = filter_var(
            $request->input('first_user.send_welcome_email', false),
            FILTER_VALIDATE_BOOLEAN
        );
        $initializeIndustry = filter_var(
            $request->input('industry_type.initialize_data', true),
            FILTER_VALIDATE_BOOLEAN
        );

        $smtpRule    = $configureSmtp ? 'required' : 'nullable';
        $userRule    = $createUser    ? 'required' : 'nullable';

        // 422 (not a redirect) so Inertia keeps the client-side wizard state.
        $request->validate([
            // Company basics
            'company_info.name'         => 'required|string|max:255',
            'company_info.email'        => 'required|email|max:255|unique:companies,email',
            'company_info.phone'        => 'nullable|string|max:50',
            'company_info.address'      => 'nullable|string|max:255',
            'company_info.postal_code'  => 'nullable|string|max:20',
            'company_info.city'         => 'nullable|string|max:100',
            'company_info.country'      => 'nullable|string|max:100',
            'company_info.tax_number'   => 'nullable|string|max:100',
            'company_info.vat_number'   => 'nullable|string|max:100',
            'company_info.website'      => 'nullable|url|max:255',
            'company_info.logo'         => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

            'industry_type.slug'             => 'nullable|in:gartenbau,bauunternehmen,raumausstattung,gebaudetechnik,logistik,handel,dienstleistung',
            'industry_type.initialize_data'  => 'nullable|boolean',

            // Email / SMTP – all optional unless configure_smtp is on
            'email_settings.configure_smtp'     => 'nullable|boolean',
            'email_settings.smtp_host'          => $smtpRule . '|string|max:255',
            'email_settings.smtp_port'          => $smtpRule . '|integer|min:1|max:65535',
            'email_settings.smtp_username'      => $smtpRule . '|string|max:255',
            'email_settings.smtp_password'      => $smtpRule . '|string|max:255',
            'email_settings.smtp_encryption'    => $smtpRule . '|in:tls,ssl,none',
            'email_settings.smtp_from_address'  => $smtpRule . '|email|max:255',
            'email_settings.smtp_from_name'     => $smtpRule . '|string|max:255',

            // Invoice settings — number formats
            'invoice_settings.invoice_number_format'  => ['nullable', 'string', 'max:60', 'regex:/\{#+\}/'],
            'invoice_settings.invoice_next_counter'   => 'nullable|integer|min:1|max:999999',
            'invoice_settings.storno_number_format'   => ['nullable', 'string', 'max:60', 'regex:/\{#+\}/'],
            'invoice_settings.storno_next_counter'    => 'nullable|integer|min:1|max:999999',
            'invoice_settings.offer_number_format'    => ['nullable', 'string', 'max:60', 'regex:/\{#+\}/'],
            'invoice_settings.offer_next_counter'     => 'nullable|integer|min:1|max:999999',
            'invoice_settings.customer_number_format' => ['nullable', 'string', 'max:60', 'regex:/\{#+\}/'],
            'invoice_settings.customer_next_counter'  => 'nullable|integer|min:1|max:999999',
            'invoice_settings.product_number_format'  => ['nullable', 'string', 'max:60', 'regex:/\{#+\}/'],
            'invoice_settings.product_next_counter'   => 'nullable|integer|min:1|max:999999',
            'invoice_settings.currency'             => 'nullable|in:EUR,USD,GBP,CHF',
            'invoice_settings.tax_rate'             => 'nullable|numeric|min:0|max:1',
            'invoice_settings.reduced_tax_rate'     => 'nullable|numeric|min:0|max:1',
            'invoice_settings.payment_terms'        => 'nullable|integer|min:1|max:365',
            'invoice_settings.offer_validity_days'  => 'nullable|integer|min:1|max:365',
            'invoice_settings.date_format'          => 'nullable|in:d.m.Y,Y-m-d,d/m/Y,m/d/Y',
            'invoice_settings.decimal_separator'    => 'nullable|string|max:1',
            'invoice_settings.thousands_separator'  => 'nullable|string|max:1',

            // Mahnung settings
            'mahnung_settings.reminder_friendly_days'  => 'nullable|integer|min:1|max:90',
            'mahnung_settings.reminder_mahnung1_days'  => 'nullable|integer|min:1|max:90',
            'mahnung_settings.reminder_mahnung2_days'  => 'nullable|integer|min:1|max:90',
            'mahnung_settings.reminder_mahnung3_days'  => 'nullable|integer|min:1|max:90',
            'mahnung_settings.reminder_inkasso_days'   => 'nullable|integer|min:1|max:365',
            'mahnung_settings.reminder_mahnung1_fee'   => 'nullable|numeric|min:0',
            'mahnung_settings.reminder_mahnung2_fee'   => 'nullable|numeric|min:0',
            'mahnung_settings.reminder_mahnung3_fee'   => 'nullable|numeric|min:0',
            'mahnung_settings.reminder_interest_rate'  => 'nullable|numeric|min:0|max:30',
            'mahnung_settings.reminder_auto_send'      => 'nullable|boolean',

            // Banking – all optional
            'banking_info.bank_name'       => 'nullable|string|max:255',
            'banking_info.iban'            => 'nullable|string|max:42',
            'banking_info.bic'             => 'nullable|string|max:15',
            'banking_info.account_holder'  => 'nullable|string|max:255',

            // First user – required only when create_user is on
            'first_user.create_user'       => 'nullable|boolean',
            'first_user.name'              => $userRule . '|string|max:255',
            'first_user.email'             => $userRule . '|email|max:255|unique:users,email',
            'first_user.password'          => $userRule . '|string|min:8',
            'first_user.send_welcome_email' => 'nullable|boolean',
        ], $this->validationMessages(), $this->validationAttributes());

        try {
            DB::beginTransaction();

            // ── Company ──────────────────────────────────────────────────────
            $ci = $request->input('company_info', []);
            $company = Company::create([
                'id'           => Str::uuid(),
                'name'         => $ci['name'] ?? '',
                'email'        => $ci['email'] ?? '',
                'phone'        => $ci['phone'] ?? null,
                'address'      => $ci['address'] ?? null,
                'postal_code'  => $ci['postal_code'] ?? null,
                'city'         => $ci['city'] ?? null,
                'country'      => $ci['country'] ?? 'Deutschland',
                'tax_number'   => $ci['tax_number'] ?? null,
                'vat_number'   => $ci['vat_number'] ?? null,
                'website'      => $ci['website'] ?? null,
            ]);

            // ── Logo (needs company ID for tenant path) ───────────────────────
            if ($request->hasFile('company_info.logo')) {
                $logoPath = $this->processAndStoreLogo($request->file('company_info.logo'), $company->id);
                $company->update(['logo' => $logoPath]);
            }

            // ── SMTP ─────────────────────────────────────────────────────────
            if ($configureSmtp) {
                $es = $request->input('email_settings', []);
                $company->setSmtpSettings([
                    'smtp_host'         => $es['smtp_host'] ?? null,
                    'smtp_port'         => (int) ($es['smtp_port'] ?? 587),
                    'smtp_username'     => $es['smtp_username'] ?? null,
                    'smtp_password'     => $es['smtp_password'] ?? null,
                    'smtp_encryption'   => $es['smtp_encryption'] ?? 'tls',
                    'smtp_from_address' => $es['smtp_from_address'] ?? null,
                    'smtp_from_name'    => $es['smtp_from_name'] ?? null,
                ]);
            }

            // ── Banking ──────────────────────────────────────────────────────
            $bank = $request->input('banking_info', []);
            $iban = $this->normalizeIban($bank['iban'] ?? null);
            $bic  = $this->normalizeBic($bank['bic'] ?? null);
            $holder = trim((string) ($bank['account_holder'] ?? ''));
            $bankName = trim((string) ($bank['bank_name'] ?? ''));
            if ($iban || $bic || $holder !== '' || $bankName !== '') {
                $company->setBankSettings([
                    'bank_name'           => $bankName !== '' ? $bankName : null,
                    'bank_iban'           => $iban,
                    'bank_bic'            => $bic,
                    'bank_account_holder' => $holder !== '' ? $holder : null,
                ]);
            }

            // ── Invoice settings ─────────────────────────────────────────────
            $invoiceDefaults = [
                'invoice_number_format'  => 'RE-{YYYY}-{####}',
                'invoice_next_counter'   => 1,
                'storno_number_format'   => 'STORNO-{YYYY}-{####}',
                'storno_next_counter'    => 1,
                'offer_number_format'    => 'AN-{YYYY}-{####}',
                'offer_next_counter'     => 1,
                'customer_number_format' => 'KU-{YYYY}-{####}',
                'customer_next_counter'  => 1,
                'product_number_format'  => 'PR-{YYYY}-{####}',
                'product_next_counter'   => 1,
                'currency'            => 'EUR',
                'tax_rate'            => 0.19,
                'reduced_tax_rate'    => 0.07,
                'payment_terms'       => 14,
                'offer_validity_days' => 30,
                'date_format'         => 'd.m.Y',
                'decimal_separator'   => ',',
                'thousands_separator' => '.',
            ];
            $invoiceSettings = array_merge($invoiceDefaults, $request->input('invoice_settings', []));
            foreach ($invoiceSettings as $key => $value) {
                if ($value !== null && $value !== '') {
                    $this->settingsService->setCompany($key, $value, $company->id, $this->settingType($value));
                }
            }

            // ── Mahnung settings ─────────────────────────────────────────────
            $mahnungDefaults = [
                'reminder_friendly_days'   => 7,
                'reminder_mahnung1_days'   => 14,
                'reminder_mahnung2_days'   => 21,
                'reminder_mahnung3_days'   => 30,
                'reminder_inkasso_days'    => 45,
                'reminder_mahnung1_fee'    => 5.0,
                'reminder_mahnung2_fee'    => 10.0,
                'reminder_mahnung3_fee'    => 15.0,
                'reminder_interest_rate'   => 9.0,
                'reminder_auto_send'       => true,
            ];
            $mahnungRaw      = $request->input('mahnung_settings', []);
            $mahnungSettings = array_merge($mahnungDefaults, $mahnungRaw);
            // Cast booleans and numbers received as strings via FormData
            $mahnungSettings['reminder_auto_send'] = filter_var(
                $mahnungSettings['reminder_auto_send'], FILTER_VALIDATE_BOOLEAN
            );
            foreach ($mahnungSettings as $key => $value) {
                $this->settingsService->setCompany($key, $value, $company->id, $this->settingType($value));
            }

            // ── First user ───────────────────────────────────────────────────
            $createdUser = null;
            $plainPassword = null;
            if ($createUser && !empty($request->input('first_user.email'))) {
                $fu = $request->input('first_user', []);
                $plainPassword = (string) $fu['password'];
                // User model hashes via the `hashed` cast — pass the plain value.
                $createdUser = User::create([
                    'id'                => Str::uuid(),
                    'name'              => $fu['name'],
                    'email'             => $fu['email'],
                    'password'          => $plainPassword,
                    'company_id'        => $company->id,
                    'role'              => 'admin',
                    'status'            => 'active',
                    'email_verified_at' => now(),
                ]);
                $createdUser->assignRole('admin');
            }

            DB::commit();

            // Run industry initialisation after commit (non-blocking, non-fatal)
            $industrySlug = $request->input('industry_type.slug');
            $validSlugs   = ['gartenbau', 'bauunternehmen', 'raumausstattung', 'gebaudetechnik', 'logistik', 'handel', 'dienstleistung'];
            if ($initializeIndustry && $industrySlug && in_array($industrySlug, $validSlugs, true)) {
                try {
                    Artisan::call('company:init', [
                        'company_id' => $company->id,
                        '--type'     => $industrySlug,
                    ]);
                } catch (\Throwable $initEx) {
                    Log::warning("company:init failed for {$company->id}: " . $initEx->getMessage());
                }
            }

            $welcomeFailed = false;
            if ($createdUser && $sendWelcomeEmail && $plainPassword) {
                $welcomeFailed = ! $this->sendWelcomeEmail($createdUser, $company, $plainPassword);
            }

            $message = 'Firma "'.$company->name.'" wurde erfolgreich erstellt.';
            if ($welcomeFailed) {
                $message .= ' Die Willkommens-E-Mail konnte nicht gesendet werden.';
            }

            return redirect()->route('companies.index')->with('success', $message);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Company wizard failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            throw ValidationException::withMessages([
                'general' => 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.',
            ]);
        }
    }

    /**
     * Cancel wizard – just redirect back to the company list.
     */
    public function cancel()
    {
        return redirect()->route('companies.index');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    protected function normalizeIban(?string $iban): ?string
    {
        $iban = strtoupper(preg_replace('/\s+/', '', (string) $iban) ?? '');

        return $iban !== '' ? $iban : null;
    }

    protected function normalizeBic(?string $bic): ?string
    {
        $bic = strtoupper(preg_replace('/\s+/', '', (string) $bic) ?? '');

        return $bic !== '' ? $bic : null;
    }

    protected function sendWelcomeEmail(User $user, Company $company, string $plainPassword): bool
    {
        $subject = "Willkommen bei AndoBill – Zugang für {$company->name}";
        $loginUrl = url('/login');

        try {
            if ($company->smtp_host && $company->smtp_username) {
                Config::set('mail.default', 'smtp');
                Config::set('mail.mailers.smtp.host', $company->smtp_host);
                Config::set('mail.mailers.smtp.port', $company->smtp_port);
                Config::set('mail.mailers.smtp.username', $company->smtp_username);
                Config::set('mail.mailers.smtp.password', $company->smtp_password);
                Config::set('mail.mailers.smtp.encryption', $company->smtp_encryption ?: 'tls');
                Config::set('mail.from.address', $company->smtp_from_address ?: $company->email);
                Config::set('mail.from.name', $company->smtp_from_name ?: $company->name);
            }

            $companyName = $this->e($company->name);
            $userName = $this->e($user->name);
            $userEmail = $this->e($user->email);
            $safePassword = $this->e($plainPassword);
            $safeLoginUrl = $this->e($loginUrl);

            $html = <<<HTML
<!DOCTYPE html>
<html lang="de">
<head><meta charset="UTF-8"><title>Willkommen bei AndoBill</title></head>
<body style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;line-height:1.7;color:#1a1a1a;max-width:600px;margin:0 auto;padding:40px 20px;">
  <p style="font-size:14px;font-weight:600;letter-spacing:0.3px;text-transform:uppercase;margin:0 0 8px;">Willkommen bei AndoBill</p>
  <p style="font-size:13px;color:#666;margin:0 0 32px;">{$companyName}</p>
  <p>Hallo {$userName},</p>
  <p>für Sie wurde ein Administratorkonto bei <strong>{$companyName}</strong> eingerichtet. Mit diesen Zugangsdaten können Sie sich anmelden:</p>
  <table style="width:100%;border-collapse:collapse;margin:24px 0;">
    <tr><td style="padding:12px 0;border-bottom:1px solid #f0f0f0;color:#666;">Firma</td><td style="padding:12px 0;border-bottom:1px solid #f0f0f0;text-align:right;">{$companyName}</td></tr>
    <tr><td style="padding:12px 0;border-bottom:1px solid #f0f0f0;color:#666;">E-Mail</td><td style="padding:12px 0;border-bottom:1px solid #f0f0f0;text-align:right;">{$userEmail}</td></tr>
    <tr><td style="padding:12px 0;color:#666;">Passwort</td><td style="padding:12px 0;text-align:right;">{$safePassword}</td></tr>
  </table>
  <p><a href="{$safeLoginUrl}" style="display:inline-block;padding:12px 24px;background:#111827;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;">Jetzt anmelden</a></p>
  <p>Bitte ändern Sie das Passwort nach der ersten Anmeldung unter <strong>Einstellungen → Profil</strong>.</p>
  <p>Mit freundlichen Grüßen<br>AndoBill</p>
</body>
</html>
HTML;

            Mail::to($user->email, $user->name)->send(
                (new \Illuminate\Mail\Mailable)->subject($subject)->html($html)
            );

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

    protected function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    protected function settingType(mixed $value): string
    {
        if (is_bool($value))  return 'boolean';
        if (is_int($value))   return 'integer';
        if (is_float($value)) return 'decimal';
        if (is_array($value)) return 'json';
        return 'string';
    }

    protected function validationMessages(): array
    {
        return [
            'required'  => 'Das Feld :attribute ist erforderlich.',
            'email'     => 'Bitte geben Sie eine gültige E-Mail-Adresse für :attribute ein.',
            'url'       => 'Bitte geben Sie eine gültige URL für :attribute ein (z. B. https://example.de).',
            'max'       => 'Das Feld :attribute darf höchstens :max Zeichen enthalten.',
            'min'       => 'Das Feld :attribute muss mindestens :min sein.',
            'integer'   => 'Das Feld :attribute muss eine ganze Zahl sein.',
            'numeric'   => 'Das Feld :attribute muss eine Zahl sein.',
            'string'    => 'Das Feld :attribute muss ein Text sein.',
            'in'        => 'Der ausgewählte Wert für :attribute ist ungültig.',
            'unique'    => 'Der Wert für :attribute ist bereits vergeben.',
            'boolean'   => 'Das Feld :attribute muss wahr oder falsch sein.',
            'company_info.email.unique'  => 'Die Firmen-E-Mail ist bereits vergeben.',
            'first_user.email.unique'    => 'Die E-Mail des Benutzers ist bereits vergeben.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'company_info.name'         => 'Firmenname',
            'company_info.email'        => 'E-Mail',
            'company_info.phone'        => 'Telefon',
            'company_info.address'      => 'Adresse',
            'company_info.postal_code'  => 'Postleitzahl',
            'company_info.city'         => 'Stadt',
            'company_info.country'      => 'Land',
            'company_info.tax_number'   => 'Steuernummer',
            'company_info.vat_number'   => 'USt-IdNr.',
            'company_info.website'      => 'Webseite',
            'company_info.logo'         => 'Firmenlogo',

            'industry_type.slug'            => 'Branchenpaket',
            'industry_type.initialize_data' => 'Branchenpaket einrichten',

            'email_settings.smtp_host'         => 'SMTP Host',
            'email_settings.smtp_port'         => 'SMTP Port',
            'email_settings.smtp_username'     => 'SMTP Benutzername',
            'email_settings.smtp_password'     => 'SMTP Passwort',
            'email_settings.smtp_encryption'   => 'SMTP Verschlüsselung',
            'email_settings.smtp_from_address' => 'Absender E-Mail',
            'email_settings.smtp_from_name'    => 'Absendername',

            'invoice_settings.invoice_number_format'  => 'Rechnungsnummernformat',
            'invoice_settings.offer_number_format'    => 'Angebotsnummernformat',
            'invoice_settings.customer_number_format' => 'Kundennummernformat',
            'invoice_settings.product_number_format'  => 'Produktnummernformat',
            'invoice_settings.currency'            => 'Währung',
            'invoice_settings.tax_rate'            => 'Steuersatz',
            'invoice_settings.reduced_tax_rate'    => 'Ermäßigter Steuersatz',
            'invoice_settings.payment_terms'       => 'Zahlungsziel',
            'invoice_settings.offer_validity_days' => 'Angebotsgültigkeit',
            'invoice_settings.date_format'         => 'Datumsformat',
            'invoice_settings.decimal_separator'   => 'Dezimaltrennzeichen',
            'invoice_settings.thousands_separator' => 'Tausendertrennzeichen',

            'mahnung_settings.reminder_friendly_days'  => 'Tage bis freundliche Erinnerung',
            'mahnung_settings.reminder_mahnung1_days'  => 'Tage bis 1. Mahnung',
            'mahnung_settings.reminder_mahnung2_days'  => 'Tage bis 2. Mahnung',
            'mahnung_settings.reminder_mahnung3_days'  => 'Tage bis 3. Mahnung',
            'mahnung_settings.reminder_inkasso_days'   => 'Tage bis Inkasso',
            'mahnung_settings.reminder_mahnung1_fee'   => 'Gebühr 1. Mahnung',
            'mahnung_settings.reminder_mahnung2_fee'   => 'Gebühr 2. Mahnung',
            'mahnung_settings.reminder_mahnung3_fee'   => 'Gebühr 3. Mahnung',
            'mahnung_settings.reminder_interest_rate'  => 'Verzugszinssatz',
            'mahnung_settings.reminder_auto_send'      => 'Automatischer Versand',

            'banking_info.bank_name'      => 'Bankname',
            'banking_info.iban'           => 'IBAN',
            'banking_info.bic'            => 'BIC',
            'banking_info.account_holder' => 'Kontoinhaber',

            'first_user.create_user'        => 'Benutzer erstellen',
            'first_user.name'               => 'Name des ersten Benutzers',
            'first_user.email'              => 'E-Mail des ersten Benutzers',
            'first_user.password'           => 'Passwort des ersten Benutzers',
            'first_user.send_welcome_email' => 'Willkommens-E-Mail senden',
        ];
    }
}
