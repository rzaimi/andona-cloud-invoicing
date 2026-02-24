"use client"

import type React from "react"
import { useForm } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Badge } from "@/components/ui/badge"
import { Save } from "lucide-react"
import { route } from "ziggy-js"

/** Resolve a number-format string to a preview using today's date and a sample counter. */
function previewNumberFormat(format: string, sample = 1): string {
    if (!format) return ""
    const now = new Date()
    const yyyy = String(now.getFullYear())
    const yy   = yyyy.slice(-2)
    const mm   = String(now.getMonth() + 1).padStart(2, "0")
    const dd   = String(now.getDate()).padStart(2, "0")

    let result = format
        .replace(/\{YYYY\}/g, yyyy)
        .replace(/\{YY\}/g, yy)
        .replace(/\{MM\}/g, mm)
        .replace(/\{DD\}/g, dd)

    // Replace {####} with padded counter
    result = result.replace(/\{(#+)\}/g, (_: string, hashes: string) =>
        String(sample).padStart(hashes.length, "0")
    )
    return result
}

interface CompanySettingsTabProps {
    company: any
    settings: any
}

export default function CompanySettingsTab({ company, settings }: CompanySettingsTabProps) {
    const { data, setData, put, processing, errors } = useForm({
        currency: settings?.currency || "EUR",
        tax_rate: settings?.tax_rate || 0.19,
        reduced_tax_rate: settings?.reduced_tax_rate || 0.07,
        invoice_number_format:  settings?.invoice_number_format  || "RE-{YYYY}-{####}",
        offer_number_format:    settings?.offer_number_format    || "AN-{YYYY}-{####}",
        customer_number_format: settings?.customer_number_format || "KU-{YYYY}-{####}",
        date_format: settings?.date_format || "d.m.Y",
        payment_terms: settings?.payment_terms || 14,
        language: settings?.language || "de",
        timezone: settings?.timezone || "Europe/Berlin",
        decimal_separator: settings?.decimal_separator || ",",
        thousands_separator: settings?.thousands_separator || ".",
        invoice_footer: settings?.invoice_footer || "",
        invoice_tax_note: settings?.invoice_tax_note || "",
        offer_footer: settings?.offer_footer || "",
        offer_validity_days: settings?.offer_validity_days || 30,
    })

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        put(route("settings.update"))
    }

    const currencies = ["EUR", "USD", "GBP", "JPY", "CHF"]
    const dateFormats = [
        { value: "d.m.Y", label: "DD.MM.YYYY" },
        { value: "Y-m-d", label: "YYYY-MM-DD" },
        { value: "d/m/Y", label: "DD/MM/YYYY" },
        { value: "m/d/Y", label: "MM/DD/YYYY" },
    ]
    const languages = [
        { value: "de", label: "Deutsch" },
        { value: "en", label: "English" },
        { value: "fr", label: "Français" },
        { value: "it", label: "Italiano" },
        { value: "es", label: "Español" },
    ]

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Allgemeine Einstellungen</CardTitle>
                    <CardDescription>Grundlegende Konfigurationen für Ihre Firma</CardDescription>
                </CardHeader>
                <CardContent className="grid gap-6 md:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="currency">Währung *</Label>
                        <Select value={data.currency} onValueChange={(value) => setData("currency", value)}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {currencies.map((currency) => (
                                    <SelectItem key={currency} value={currency}>
                                        {currency}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.currency && <p className="text-red-600 text-sm">{errors.currency}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="language">Sprache</Label>
                        <Select value={data.language} onValueChange={(value) => setData("language", value)}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {languages.map((lang) => (
                                    <SelectItem key={lang.value} value={lang.value}>
                                        {lang.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.language && <p className="text-red-600 text-sm">{errors.language}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="timezone">Zeitzone</Label>
                        <Input
                            id="timezone"
                            value={data.timezone}
                            onChange={(e) => setData("timezone", e.target.value)}
                            placeholder="Europe/Berlin"
                        />
                        {errors.timezone && <p className="text-red-600 text-sm">{errors.timezone}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="date_format">Datumsformat *</Label>
                        <Select value={data.date_format} onValueChange={(value) => setData("date_format", value)}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {dateFormats.map((format) => (
                                    <SelectItem key={format.value} value={format.value}>
                                        {format.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.date_format && <p className="text-red-600 text-sm">{errors.date_format}</p>}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Steuereinstellungen</CardTitle>
                    <CardDescription>Mehrwertsteuer und Steuersätze</CardDescription>
                </CardHeader>
                <CardContent className="grid gap-6 md:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="tax_rate">Standard-Steuersatz *</Label>
                        <Input
                            id="tax_rate"
                            type="number"
                            step="0.01"
                            min="0"
                            max="1"
                            value={data.tax_rate}
                            onChange={(e) => setData("tax_rate", parseFloat(e.target.value) || 0)}
                            required
                        />
                        <p className="text-xs text-gray-500">z.B. 0.19 für 19%</p>
                        {errors.tax_rate && <p className="text-red-600 text-sm">{errors.tax_rate}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="reduced_tax_rate">Ermäßigter Steuersatz</Label>
                        <Input
                            id="reduced_tax_rate"
                            type="number"
                            step="0.01"
                            min="0"
                            max="1"
                            value={data.reduced_tax_rate}
                            onChange={(e) => setData("reduced_tax_rate", parseFloat(e.target.value) || 0)}
                        />
                        <p className="text-xs text-gray-500">z.B. 0.07 für 7%</p>
                        {errors.reduced_tax_rate && <p className="text-red-600 text-sm">{errors.reduced_tax_rate}</p>}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Nummernformat</CardTitle>
                    <CardDescription>
                        Dynamisches Format für automatisch generierte Nummern. Verfügbare Tokens:
                    </CardDescription>
                    <div className="flex flex-wrap gap-2 pt-1">
                        {[
                            ["{YYYY}", "4-stelliges Jahr (2025)"],
                            ["{YY}",   "2-stelliges Jahr (25)"],
                            ["{MM}",   "Monat (01–12)"],
                            ["{DD}",   "Tag (01–31)"],
                            ["{####}", "Laufende Nr. (Anzahl # = Stellenanzahl)"],
                        ].map(([token, desc]) => (
                            <span key={token} title={desc} className="cursor-help">
                                <Badge variant="outline" className="font-mono text-xs">{token}</Badge>
                            </span>
                        ))}
                    </div>
                </CardHeader>
                <CardContent className="grid gap-6 md:grid-cols-3">
                    <div className="space-y-2">
                        <Label htmlFor="invoice_number_format">Rechnungsformat *</Label>
                        <Input
                            id="invoice_number_format"
                            value={data.invoice_number_format}
                            onChange={(e) => setData("invoice_number_format", e.target.value)}
                            maxLength={60}
                            placeholder="RE-{YYYY}-{####}"
                            required
                        />
                        {data.invoice_number_format && (
                            <p className="text-xs text-muted-foreground">
                                Vorschau: <span className="font-mono font-medium">{previewNumberFormat(data.invoice_number_format)}</span>
                            </p>
                        )}
                        {errors.invoice_number_format && <p className="text-red-600 text-sm">{errors.invoice_number_format}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="offer_number_format">Angebotsformat *</Label>
                        <Input
                            id="offer_number_format"
                            value={data.offer_number_format}
                            onChange={(e) => setData("offer_number_format", e.target.value)}
                            maxLength={60}
                            placeholder="AN-{YYYY}-{####}"
                            required
                        />
                        {data.offer_number_format && (
                            <p className="text-xs text-muted-foreground">
                                Vorschau: <span className="font-mono font-medium">{previewNumberFormat(data.offer_number_format)}</span>
                            </p>
                        )}
                        {errors.offer_number_format && <p className="text-red-600 text-sm">{errors.offer_number_format}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="customer_number_format">Kundenformat</Label>
                        <Input
                            id="customer_number_format"
                            value={data.customer_number_format}
                            onChange={(e) => setData("customer_number_format", e.target.value)}
                            maxLength={60}
                            placeholder="KU-{YYYY}-{####}"
                        />
                        {data.customer_number_format && (
                            <p className="text-xs text-muted-foreground">
                                Vorschau: <span className="font-mono font-medium">{previewNumberFormat(data.customer_number_format)}</span>
                            </p>
                        )}
                        {errors.customer_number_format && <p className="text-red-600 text-sm">{errors.customer_number_format}</p>}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Zahlungsbedingungen</CardTitle>
                    <CardDescription>Standard-Zahlungsbedingungen und Gültigkeitsdauern</CardDescription>
                </CardHeader>
                <CardContent className="grid gap-6 md:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="payment_terms">Zahlungsziel (Tage) *</Label>
                        <Input
                            id="payment_terms"
                            type="number"
                            min="1"
                            max="365"
                            value={data.payment_terms}
                            onChange={(e) => setData("payment_terms", parseInt(e.target.value) || 14)}
                            required
                        />
                        {errors.payment_terms && <p className="text-red-600 text-sm">{errors.payment_terms}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="offer_validity_days">Angebots-Gültigkeit (Tage) *</Label>
                        <Input
                            id="offer_validity_days"
                            type="number"
                            min="1"
                            max="365"
                            value={data.offer_validity_days}
                            onChange={(e) => setData("offer_validity_days", parseInt(e.target.value) || 30)}
                            required
                        />
                        {errors.offer_validity_days && <p className="text-red-600 text-sm">{errors.offer_validity_days}</p>}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Zahlenformatierung</CardTitle>
                    <CardDescription>Einstellungen für Dezimal- und Tausendertrennzeichen</CardDescription>
                </CardHeader>
                <CardContent className="grid gap-6 md:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="decimal_separator">Dezimaltrennzeichen *</Label>
                        <Select value={data.decimal_separator} onValueChange={(value) => setData("decimal_separator", value)}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value=",">Komma (,)</SelectItem>
                                <SelectItem value=".">Punkt (.)</SelectItem>
                            </SelectContent>
                        </Select>
                        {errors.decimal_separator && <p className="text-red-600 text-sm">{errors.decimal_separator}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="thousands_separator">Tausendertrennzeichen *</Label>
                        <Select value={data.thousands_separator} onValueChange={(value) => setData("thousands_separator", value)}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value=".">Punkt (.)</SelectItem>
                                <SelectItem value=",">Komma (,)</SelectItem>
                            </SelectContent>
                        </Select>
                        {errors.thousands_separator && <p className="text-red-600 text-sm">{errors.thousands_separator}</p>}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Fußzeilentexte</CardTitle>
                    <CardDescription>Standardtexte für Rechnungen und Angebote</CardDescription>
                </CardHeader>
                <CardContent className="grid gap-6 md:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="invoice_footer">Rechnungs-Fußzeile</Label>
                        <Textarea
                            id="invoice_footer"
                            value={data.invoice_footer}
                            onChange={(e) => setData("invoice_footer", e.target.value)}
                            placeholder="Vielen Dank für Ihr Vertrauen!"
                            rows={3}
                            maxLength={500}
                        />
                        {errors.invoice_footer && <p className="text-red-600 text-sm">{errors.invoice_footer}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="invoice_tax_note">Steuerhinweis (z.B. §13b / §19 UStG)</Label>
                        <Textarea
                            id="invoice_tax_note"
                            value={data.invoice_tax_note}
                            onChange={(e) => setData("invoice_tax_note", e.target.value)}
                            placeholder='Beispiel: "Steuerschuldnerschaft des Leistungsempfängers (§ 13b UStG)."'
                            rows={3}
                            maxLength={500}
                        />
                        {errors.invoice_tax_note && <p className="text-red-600 text-sm">{errors.invoice_tax_note}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="offer_footer">Angebots-Fußzeile</Label>
                        <Textarea
                            id="offer_footer"
                            value={data.offer_footer}
                            onChange={(e) => setData("offer_footer", e.target.value)}
                            placeholder="Wir freuen uns auf Ihre Rückmeldung!"
                            rows={3}
                            maxLength={500}
                        />
                        {errors.offer_footer && <p className="text-red-600 text-sm">{errors.offer_footer}</p>}
                    </div>
                </CardContent>
            </Card>

            <div className="flex justify-end">
                <Button type="submit" disabled={processing}>
                    <Save className="mr-2 h-4 w-4" />
                    {processing ? "Speichert..." : "Einstellungen speichern"}
                </Button>
            </div>
        </form>
    )
}



