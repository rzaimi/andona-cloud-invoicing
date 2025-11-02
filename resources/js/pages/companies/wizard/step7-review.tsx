import { CheckCircle, Building2, Mail, FileText, Bell, CreditCard, User } from "lucide-react"
import { Badge } from "@/components/ui/badge"

interface Step7Props {
    data: any
    updateData: (key: string, data: any) => void
}

export default function Step7Review({ data }: Step7Props) {
    const companyInfo = data.company_info || {}
    const emailSettings = data.email_settings || {}
    const invoiceSettings = data.invoice_settings || {}
    const mahnungSettings = data.mahnung_settings || {}
    const bankingInfo = data.banking_info || {}
    const firstUser = data.first_user || {}

    const isComplete = (obj: any, requiredFields: string[]) => {
        return requiredFields.every((field) => obj[field])
    }

    return (
        <div className="space-y-6">
            {/* Summary Message */}
            <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                <div className="flex items-start gap-3">
                    <CheckCircle className="h-6 w-6 text-green-600 mt-1" />
                    <div>
                        <h3 className="font-semibold text-green-900">Fast geschafft!</h3>
                        <p className="text-sm text-green-800 mt-1">
                            Überprüfen Sie Ihre Eingaben und klicken Sie dann auf "Firma erstellen",
                            um die vollständig konfigurierte Firma anzulegen.
                        </p>
                    </div>
                </div>
            </div>

            {/* Company Info */}
            <div className="border rounded-lg p-4 space-y-3">
                <div className="flex items-center justify-between">
                    <h3 className="font-semibold flex items-center gap-2">
                        <Building2 className="h-5 w-5" />
                        Firmeninformationen
                    </h3>
                    <Badge variant={isComplete(companyInfo, ["name", "email"]) ? "default" : "destructive"}>
                        {isComplete(companyInfo, ["name", "email"]) ? "Vollständig" : "Unvollständig"}
                    </Badge>
                </div>
                <div className="grid gap-2 text-sm">
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">Firmenname:</span>
                        <span className="font-medium">{companyInfo.name || "-"}</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">E-Mail:</span>
                        <span className="font-medium">{companyInfo.email || "-"}</span>
                    </div>
                    {companyInfo.phone && (
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Telefon:</span>
                            <span className="font-medium">{companyInfo.phone}</span>
                        </div>
                    )}
                    {companyInfo.address && (
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Adresse:</span>
                            <span className="font-medium">
                                {companyInfo.address}
                                {companyInfo.postal_code && `, ${companyInfo.postal_code}`}
                                {companyInfo.city && ` ${companyInfo.city}`}
                            </span>
                        </div>
                    )}
                    {companyInfo.tax_number && (
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Steuernummer:</span>
                            <span className="font-medium">{companyInfo.tax_number}</span>
                        </div>
                    )}
                    {companyInfo.vat_number && (
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">USt-IdNr.:</span>
                            <span className="font-medium">{companyInfo.vat_number}</span>
                        </div>
                    )}
                </div>
            </div>

            {/* Email Settings */}
            <div className="border rounded-lg p-4 space-y-3">
                <div className="flex items-center justify-between">
                    <h3 className="font-semibold flex items-center gap-2">
                        <Mail className="h-5 w-5" />
                        E-Mail Einstellungen (SMTP)
                    </h3>
                    <Badge
                        variant={
                            isComplete(emailSettings, [
                                "smtp_host",
                                "smtp_port",
                                "smtp_username",
                                "smtp_password",
                            ])
                                ? "default"
                                : "destructive"
                        }
                    >
                        {isComplete(emailSettings, [
                            "smtp_host",
                            "smtp_port",
                            "smtp_username",
                            "smtp_password",
                        ])
                            ? "Konfiguriert"
                            : "Erforderlich"}
                    </Badge>
                </div>
                <div className="grid gap-2 text-sm">
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">SMTP Host:</span>
                        <span className="font-medium">{emailSettings.smtp_host || "-"}</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">Port:</span>
                        <span className="font-medium">{emailSettings.smtp_port || "-"}</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">Verschlüsselung:</span>
                        <span className="font-medium uppercase">
                            {emailSettings.smtp_encryption || "TLS"}
                        </span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">Benutzername:</span>
                        <span className="font-medium">{emailSettings.smtp_username || "-"}</span>
                    </div>
                </div>
            </div>

            {/* Invoice Settings */}
            <div className="border rounded-lg p-4 space-y-3">
                <div className="flex items-center justify-between">
                    <h3 className="font-semibold flex items-center gap-2">
                        <FileText className="h-5 w-5" />
                        Rechnungseinstellungen
                    </h3>
                    <Badge variant="secondary">Vorkonfiguriert</Badge>
                </div>
                <div className="grid gap-2 text-sm">
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">Rechnungs-Präfix:</span>
                        <span className="font-medium">{invoiceSettings.invoice_prefix || "RE-"}</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">Angebots-Präfix:</span>
                        <span className="font-medium">{invoiceSettings.offer_prefix || "AN-"}</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">Währung:</span>
                        <span className="font-medium">{invoiceSettings.currency || "EUR"}</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">Standard MwSt.:</span>
                        <span className="font-medium">
                            {((invoiceSettings.tax_rate || 0.19) * 100).toFixed(0)}%
                        </span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">Zahlungsziel:</span>
                        <span className="font-medium">{invoiceSettings.payment_terms || 14} Tage</span>
                    </div>
                </div>
            </div>

            {/* Mahnung Settings */}
            <div className="border rounded-lg p-4 space-y-3">
                <div className="flex items-center justify-between">
                    <h3 className="font-semibold flex items-center gap-2">
                        <Bell className="h-5 w-5" />
                        Mahnungseinstellungen
                    </h3>
                    <Badge variant="secondary">Vorkonfiguriert</Badge>
                </div>
                <div className="grid gap-2 text-sm">
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">Freundliche Erinnerung:</span>
                        <span className="font-medium">
                            Tag {mahnungSettings.reminder_friendly_days || 7} (keine Gebühr)
                        </span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">1. Mahnung:</span>
                        <span className="font-medium">
                            Tag {mahnungSettings.reminder_mahnung1_days || 14} (
                            {mahnungSettings.reminder_mahnung1_fee || 5}€)
                        </span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">2. Mahnung:</span>
                        <span className="font-medium">
                            Tag {mahnungSettings.reminder_mahnung2_days || 21} (
                            {mahnungSettings.reminder_mahnung2_fee || 10}€)
                        </span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">3. Mahnung:</span>
                        <span className="font-medium">
                            Tag {mahnungSettings.reminder_mahnung3_days || 30} (
                            {mahnungSettings.reminder_mahnung3_fee || 15}€)
                        </span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">Automatischer Versand:</span>
                        <span className="font-medium">
                            {mahnungSettings.reminder_auto_send ? "Aktiviert" : "Deaktiviert"}
                        </span>
                    </div>
                </div>
            </div>

            {/* Banking Info */}
            <div className="border rounded-lg p-4 space-y-3">
                <div className="flex items-center justify-between">
                    <h3 className="font-semibold flex items-center gap-2">
                        <CreditCard className="h-5 w-5" />
                        Bankverbindung
                    </h3>
                    <Badge variant={bankingInfo.iban ? "default" : "secondary"}>
                        {bankingInfo.iban ? "Angegeben" : "Optional"}
                    </Badge>
                </div>
                {bankingInfo.iban ? (
                    <div className="grid gap-2 text-sm">
                        {bankingInfo.bank_name && (
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">Bank:</span>
                                <span className="font-medium">{bankingInfo.bank_name}</span>
                            </div>
                        )}
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">IBAN:</span>
                            <span className="font-medium font-mono">{bankingInfo.iban}</span>
                        </div>
                        {bankingInfo.bic && (
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">BIC:</span>
                                <span className="font-medium font-mono">{bankingInfo.bic}</span>
                            </div>
                        )}
                    </div>
                ) : (
                    <p className="text-sm text-muted-foreground">Keine Bankverbindung angegeben</p>
                )}
            </div>

            {/* First User */}
            <div className="border rounded-lg p-4 space-y-3">
                <div className="flex items-center justify-between">
                    <h3 className="font-semibold flex items-center gap-2">
                        <User className="h-5 w-5" />
                        Erster Administrator
                    </h3>
                    <Badge variant={firstUser.create_user ? "default" : "secondary"}>
                        {firstUser.create_user ? "Wird erstellt" : "Übersprungen"}
                    </Badge>
                </div>
                {firstUser.create_user ? (
                    <div className="grid gap-2 text-sm">
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Name:</span>
                            <span className="font-medium">{firstUser.name}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">E-Mail:</span>
                            <span className="font-medium">{firstUser.email}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">Willkommens-E-Mail:</span>
                            <span className="font-medium">
                                {firstUser.send_welcome_email ? "Ja" : "Nein"}
                            </span>
                        </div>
                    </div>
                ) : (
                    <p className="text-sm text-muted-foreground">
                        Kein Benutzer wird erstellt. Sie können später Benutzer hinzufügen.
                    </p>
                )}
            </div>

            {/* Final Note */}
            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p className="text-sm text-blue-800">
                    <strong>Hinweis:</strong> Nach dem Erstellen der Firma können Sie alle Einstellungen
                    jederzeit über die Firmeneinstellungen anpassen.
                </p>
            </div>
        </div>
    )
}

