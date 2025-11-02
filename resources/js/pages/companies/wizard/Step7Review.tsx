import { Alert, AlertDescription } from "@/components/ui/alert"
import { CheckCircle, Building2, Mail, FileText, Bell, Landmark, User } from "lucide-react"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"

export default function Step7Review({ data }: any) {
    return (
        <div className="space-y-6">
            <Alert>
                <CheckCircle className="h-4 w-4" />
                <AlertDescription>
                    Bitte überprüfen Sie alle Eingaben. Sie können zurückgehen, um Änderungen vorzunehmen.
                </AlertDescription>
            </Alert>

            {/* Company Info */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-lg">
                        <Building2 className="h-5 w-5" />
                        Firmeninformationen
                    </CardTitle>
                </CardHeader>
                <CardContent className="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <span className="font-medium">Name:</span>
                        <p>{data.company_info?.name || '-'}</p>
                    </div>
                    <div>
                        <span className="font-medium">E-Mail:</span>
                        <p>{data.company_info?.email || '-'}</p>
                    </div>
                    <div>
                        <span className="font-medium">Telefon:</span>
                        <p>{data.company_info?.phone || '-'}</p>
                    </div>
                    <div>
                        <span className="font-medium">Website:</span>
                        <p>{data.company_info?.website || '-'}</p>
                    </div>
                    <div className="col-span-2">
                        <span className="font-medium">Adresse:</span>
                        <p>
                            {[data.company_info?.address, data.company_info?.postal_code, data.company_info?.city, data.company_info?.country]
                                .filter(Boolean)
                                .join(', ') || '-'}
                        </p>
                    </div>
                    <div>
                        <span className="font-medium">Steuernummer:</span>
                        <p>{data.company_info?.tax_number || '-'}</p>
                    </div>
                    <div>
                        <span className="font-medium">USt-IdNr.:</span>
                        <p>{data.company_info?.vat_number || '-'}</p>
                    </div>
                </CardContent>
            </Card>

            {/* Email Settings */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-lg">
                        <Mail className="h-5 w-5" />
                        E-Mail Konfiguration
                    </CardTitle>
                </CardHeader>
                <CardContent className="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <span className="font-medium">SMTP Host:</span>
                        <p>{data.email_settings?.smtp_host || '-'}</p>
                    </div>
                    <div>
                        <span className="font-medium">Port:</span>
                        <p>{data.email_settings?.smtp_port || '-'}</p>
                    </div>
                    <div>
                        <span className="font-medium">Benutzername:</span>
                        <p>{data.email_settings?.smtp_username || '-'}</p>
                    </div>
                    <div>
                        <span className="font-medium">Verschlüsselung:</span>
                        <p className="uppercase">{data.email_settings?.smtp_encryption || '-'}</p>
                    </div>
                    <div>
                        <span className="font-medium">Absender E-Mail:</span>
                        <p>{data.email_settings?.smtp_from_address || '-'}</p>
                    </div>
                    <div>
                        <span className="font-medium">Absender Name:</span>
                        <p>{data.email_settings?.smtp_from_name || '-'}</p>
                    </div>
                </CardContent>
            </Card>

            {/* Invoice Settings */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-lg">
                        <FileText className="h-5 w-5" />
                        Rechnungseinstellungen
                    </CardTitle>
                </CardHeader>
                <CardContent className="grid grid-cols-3 gap-3 text-sm">
                    <div>
                        <span className="font-medium">Rechnungspräfix:</span>
                        <p>{data.invoice_settings?.invoice_prefix || '-'}</p>
                    </div>
                    <div>
                        <span className="font-medium">Angebotspräfix:</span>
                        <p>{data.invoice_settings?.offer_prefix || '-'}</p>
                    </div>
                    <div>
                        <span className="font-medium">Währung:</span>
                        <p>{data.invoice_settings?.currency || '-'}</p>
                    </div>
                    <div>
                        <span className="font-medium">Steuersatz:</span>
                        <p>{((data.invoice_settings?.tax_rate || 0) * 100).toFixed(0)}%</p>
                    </div>
                    <div>
                        <span className="font-medium">Zahlungsziel:</span>
                        <p>{data.invoice_settings?.payment_terms || '-'} Tage</p>
                    </div>
                    <div>
                        <span className="font-medium">Angebotsgültigkeit:</span>
                        <p>{data.invoice_settings?.offer_validity_days || '-'} Tage</p>
                    </div>
                </CardContent>
            </Card>

            {/* Mahnung Settings */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-lg">
                        <Bell className="h-5 w-5" />
                        Mahnungseinstellungen
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3 text-sm">
                    <div className="grid grid-cols-3 gap-3">
                        <div>
                            <span className="font-medium">Freundlich:</span>
                            <p>{data.mahnung_settings?.reminder_friendly_days || '-'} Tage</p>
                        </div>
                        <div>
                            <span className="font-medium">1. Mahnung:</span>
                            <p>{data.mahnung_settings?.reminder_mahnung1_days || '-'} Tage (€{data.mahnung_settings?.reminder_mahnung1_fee?.toFixed(2) || '-'})</p>
                        </div>
                        <div>
                            <span className="font-medium">2. Mahnung:</span>
                            <p>{data.mahnung_settings?.reminder_mahnung2_days || '-'} Tage (€{data.mahnung_settings?.reminder_mahnung2_fee?.toFixed(2) || '-'})</p>
                        </div>
                        <div>
                            <span className="font-medium">3. Mahnung:</span>
                            <p>{data.mahnung_settings?.reminder_mahnung3_days || '-'} Tage (€{data.mahnung_settings?.reminder_mahnung3_fee?.toFixed(2) || '-'})</p>
                        </div>
                        <div>
                            <span className="font-medium">Inkasso:</span>
                            <p>{data.mahnung_settings?.reminder_inkasso_days || '-'} Tage</p>
                        </div>
                        <div>
                            <span className="font-medium">Verzugszins:</span>
                            <p>{data.mahnung_settings?.reminder_interest_rate?.toFixed(2) || '-'}% p.a.</p>
                        </div>
                    </div>
                    <div>
                        <span className="font-medium">Automatischer Versand:</span>
                        <p>{data.mahnung_settings?.reminder_auto_send ? 'Ja' : 'Nein'}</p>
                    </div>
                </CardContent>
            </Card>

            {/* Banking Info */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-lg">
                        <Landmark className="h-5 w-5" />
                        Bankinformationen
                    </CardTitle>
                </CardHeader>
                <CardContent className="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <span className="font-medium">Bank:</span>
                        <p>{data.banking_info?.bank_name || '-'}</p>
                    </div>
                    <div>
                        <span className="font-medium">Kontoinhaber:</span>
                        <p>{data.banking_info?.account_holder || '-'}</p>
                    </div>
                    <div>
                        <span className="font-medium">IBAN:</span>
                        <p className="font-mono">{data.banking_info?.iban || '-'}</p>
                    </div>
                    <div>
                        <span className="font-medium">BIC:</span>
                        <p className="font-mono">{data.banking_info?.bic || '-'}</p>
                    </div>
                </CardContent>
            </Card>

            {/* First User */}
            {data.first_user?.create_user && (
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-lg">
                            <User className="h-5 w-5" />
                            Erster Benutzer
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <span className="font-medium">Name:</span>
                            <p>{data.first_user?.name || '-'}</p>
                        </div>
                        <div>
                            <span className="font-medium">E-Mail:</span>
                            <p>{data.first_user?.email || '-'}</p>
                        </div>
                        <div className="col-span-2">
                            <span className="font-medium">Willkommens-E-Mail:</span>
                            <p>{data.first_user?.send_welcome_email ? 'Ja' : 'Nein'}</p>
                        </div>
                    </CardContent>
                </Card>
            )}
        </div>
    )
}


