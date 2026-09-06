<?php

namespace App\Traits;

use App\Modules\Company\Models\Company;
use Illuminate\Support\Facades\Config;

trait ConfiguresCompanySmtp
{
    protected function configureCompanySmtp(Company $company): void
    {
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
