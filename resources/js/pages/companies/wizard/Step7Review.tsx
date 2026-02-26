import { Alert, AlertDescription } from "@/components/ui/alert"
import { CheckCircle, Building2, Mail, FileText, Bell, Landmark, User, Briefcase } from "lucide-react"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"

const INDUSTRY_LABELS: Record<string, string> = {
    gartenbau:       "Garten- und Außenanlagenbau",
    bauunternehmen:  "Bauunternehmen",
    raumausstattung: "Raumausstattung & Fliesenarbeiten",
    gebaudetechnik:  "Gebäudetechnik",
    logistik:        "Logistik & Palettenhandel",
    handel:          "Handelsunternehmen",
    dienstleistung:  "Sonstige Dienstleistungen",
}

function fmt(value: any, decimals = 2): string {
    const n = parseFloat(value)
    return isNaN(n) ? "-" : n.toFixed(decimals)
}

function fmtInt(value: any): string {
    const n = parseInt(value)
    return isNaN(n) ? "-" : String(n)
}

export default function Step7Review({ data, logoPreview }: any) {
    const ci = data.company_info ?? {}
    const industry = data.industry_type ?? {}
    const es = data.email_settings ?? {}
    const inv = data.invoice_settings ?? {}
    const mah = data.mahnung_settings ?? {}
    const bank = data.banking_info ?? {}
    const fu = data.first_user ?? {}

    // Use the passed local preview (blob URL) or fall back to a stored path in ci.logo
    const storedLogoPath = ci.logo && typeof ci.logo === "string" ? ci.logo : null
    const storedLogoUrl = storedLogoPath
        ? (storedLogoPath.startsWith("http") ? storedLogoPath : `/storage/${storedLogoPath}`)
        : null
    const logoUrl = logoPreview || storedLogoUrl

    const configureSmtp = es.configure_smtp === true || es.configure_smtp === "true"

    return (
        <div className="space-y-6">
            <Alert>
                <CheckCircle className="h-4 w-4" />
                <AlertDescription>
                    Bitte überprüfen Sie alle Eingaben. Sie können zurückgehen, um Änderungen vorzunehmen.
                </AlertDescription>
            </Alert>

            {/* Industry Type */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-lg">
                        <Briefcase className="h-5 w-5" />
                        Branchenpaket
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    {industry.slug ? (
                        <div className="flex items-center gap-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                            <CheckCircle className="h-5 w-5 text-green-600 shrink-0" />
                            <div>
                                <p className="font-semibold text-sm text-green-900">
                                    {INDUSTRY_LABELS[industry.slug] ?? industry.slug}
                                </p>
                                <p className="text-xs text-green-700 mt-0.5">
                                    Produkte, Kategorien, Lager und Layouts werden automatisch angelegt.
                                </p>
                            </div>
                        </div>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            Kein Branchenpaket gewählt — manuelle Einrichtung.
                        </p>
                    )}
                </CardContent>
            </Card>

            {/* Company Info */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-lg">
                        <Building2 className="h-5 w-5" />
                        Firmeninformationen
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    {logoUrl && (
                        <div>
                            <span className="text-sm font-medium">Logo:</span>
                            <div className="mt-2">
                                <img
                                    src={logoUrl}
                                    alt="Firmenlogo"
                                    className="h-16 w-auto object-contain rounded border bg-white"
                                />
                            </div>
                        </div>
                    )}
                    <div className="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <span className="font-medium">Name:</span>
                            <p>{ci.name || "-"}</p>
                        </div>
                        <div>
                            <span className="font-medium">E-Mail:</span>
                            <p>{ci.email || "-"}</p>
                        </div>
                        <div>
                            <span className="font-medium">Telefon:</span>
                            <p>{ci.phone || "-"}</p>
                        </div>
                        <div>
                            <span className="font-medium">Website:</span>
                            <p>{ci.website || "-"}</p>
                        </div>
                        <div className="col-span-2">
                            <span className="font-medium">Adresse:</span>
                            <p>
                                {[ci.address, ci.postal_code, ci.city, ci.country]
                                    .filter(Boolean)
                                    .join(", ") || "-"}
                            </p>
                        </div>
                        <div>
                            <span className="font-medium">Steuernummer:</span>
                            <p>{ci.tax_number || "-"}</p>
                        </div>
                        <div>
                            <span className="font-medium">USt-IdNr.:</span>
                            <p>{ci.vat_number || "-"}</p>
                        </div>
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
                    <div className="col-span-2">
                        <span className="font-medium">Status:</span>
                        <p>{configureSmtp ? "Aktiviert" : "Nicht konfiguriert"}</p>
                    </div>
                    {configureSmtp && (
                        <>
                            <div>
                                <span className="font-medium">SMTP Host:</span>
                                <p>{es.smtp_host || "-"}</p>
                            </div>
                            <div>
                                <span className="font-medium">Port:</span>
                                <p>{es.smtp_port || "-"}</p>
                            </div>
                            <div>
                                <span className="font-medium">Benutzername:</span>
                                <p>{es.smtp_username || "-"}</p>
                            </div>
                            <div>
                                <span className="font-medium">Verschlüsselung:</span>
                                <p className="uppercase">{es.smtp_encryption || "-"}</p>
                            </div>
                            <div>
                                <span className="font-medium">Absender E-Mail:</span>
                                <p>{es.smtp_from_address || "-"}</p>
                            </div>
                            <div>
                                <span className="font-medium">Absender Name:</span>
                                <p>{es.smtp_from_name || "-"}</p>
                            </div>
                        </>
                    )}
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
                <CardContent className="space-y-3 text-sm">
                    <div className="grid grid-cols-[1fr_auto] gap-x-4 gap-y-1">
                        {[
                            ["Rechnung",  inv.invoice_number_format  || "RE-{YYYY}-{####}"],
                            ["Storno",    inv.storno_number_format   || "STORNO-{YYYY}-{####}"],
                            ["Angebot",   inv.offer_number_format    || "AN-{YYYY}-{####}"],
                            ["Kunde",     inv.customer_number_format || "KU-{YYYY}-{####}"],
                        ].map(([label, fmt]) => (
                            <div key={label} className="contents">
                                <span className="font-medium">{label}:</span>
                                <span className="font-mono text-xs bg-muted px-2 py-0.5 rounded">{fmt}</span>
                            </div>
                        ))}
                    </div>
                    <div className="grid grid-cols-3 gap-3 pt-2 border-t">
                        <div>
                            <span className="font-medium">Währung:</span>
                            <p>{inv.currency || "EUR"}</p>
                        </div>
                        <div>
                            <span className="font-medium">Steuersatz:</span>
                            <p>{inv.tax_rate != null ? `${(parseFloat(inv.tax_rate) * 100).toFixed(0)} %` : "19 %"}</p>
                        </div>
                        <div>
                            <span className="font-medium">Zahlungsziel:</span>
                            <p>{fmtInt(inv.payment_terms)} Tage</p>
                        </div>
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
                            <p>{fmtInt(mah.reminder_friendly_days)} Tage</p>
                        </div>
                        <div>
                            <span className="font-medium">1. Mahnung:</span>
                            <p>{fmtInt(mah.reminder_mahnung1_days)} Tage (€ {fmt(mah.reminder_mahnung1_fee)})</p>
                        </div>
                        <div>
                            <span className="font-medium">2. Mahnung:</span>
                            <p>{fmtInt(mah.reminder_mahnung2_days)} Tage (€ {fmt(mah.reminder_mahnung2_fee)})</p>
                        </div>
                        <div>
                            <span className="font-medium">3. Mahnung:</span>
                            <p>{fmtInt(mah.reminder_mahnung3_days)} Tage (€ {fmt(mah.reminder_mahnung3_fee)})</p>
                        </div>
                        <div>
                            <span className="font-medium">Inkasso:</span>
                            <p>{fmtInt(mah.reminder_inkasso_days)} Tage</p>
                        </div>
                        <div>
                            <span className="font-medium">Verzugszins:</span>
                            <p>{fmt(mah.reminder_interest_rate)} % p.a.</p>
                        </div>
                    </div>
                    <div>
                        <span className="font-medium">Automatischer Versand:</span>
                        <p>
                            {mah.reminder_auto_send === false || mah.reminder_auto_send === "false" || mah.reminder_auto_send === "0"
                                ? "Nein"
                                : "Ja"}
                        </p>
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
                        <p>{bank.bank_name || "-"}</p>
                    </div>
                    <div>
                        <span className="font-medium">Kontoinhaber:</span>
                        <p>{bank.account_holder || "-"}</p>
                    </div>
                    <div>
                        <span className="font-medium">IBAN:</span>
                        <p className="font-mono">{bank.iban || "-"}</p>
                    </div>
                    <div>
                        <span className="font-medium">BIC:</span>
                        <p className="font-mono">{bank.bic || "-"}</p>
                    </div>
                </CardContent>
            </Card>

            {/* First User */}
            {fu.create_user && fu.create_user !== "false" && fu.create_user !== "0" && (
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
                            <p>{fu.name || "-"}</p>
                        </div>
                        <div>
                            <span className="font-medium">E-Mail:</span>
                            <p>{fu.email || "-"}</p>
                        </div>
                        <div className="col-span-2">
                            <span className="font-medium">Willkommens-E-Mail:</span>
                            <p>{fu.send_welcome_email && fu.send_welcome_email !== "false" ? "Ja" : "Nein"}</p>
                        </div>
                    </CardContent>
                </Card>
            )}
        </div>
    )
}
