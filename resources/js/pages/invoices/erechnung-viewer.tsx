"use client"

import { useRef, useState } from "react"
import { Head, router, usePage } from "@inertiajs/react"
import { route } from "ziggy-js"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem } from "@/types"
import { AlertCircle, FileSearch, FileText, Landmark, Upload, X } from "lucide-react"

interface TaxRegistration {
    type: string
    number: string
}

interface ParsedParty {
    name: string | null
    address: string
    tax_registrations: TaxRegistration[]
    contact?: { name?: string; phone?: string; email?: string } | null
}

interface ParsedERechnung {
    profile: { context: string | null; label: string }
    document: {
        number: string | null
        type_code: string | null
        type_label: string
        date: string | null
        currency: string | null
        buyer_reference: string | null
        notes: string[]
    }
    seller: ParsedParty
    buyer: ParsedParty
    positions: {
        line_id: string | null
        name: string | null
        description: string | null
        quantity: number | null
        unit: string | null
        unit_price: number | null
        line_total: number | null
        tax_rate: number | null
        tax_category: string | null
    }[]
    allowances: { amount: number | null; is_charge: boolean; reason: string; tax_rate: number | null }[]
    taxes: {
        category: string | null
        category_label: string
        rate: number | null
        basis: number | null
        amount: number | null
        exemption_reason: string | null
    }[]
    totals: {
        line_total: number | null
        allowance_total: number | null
        charge_total: number | null
        net_total: number | null
        tax_total: number | null
        grand_total: number | null
        prepaid: number | null
        due_payable: number | null
    }
    payment: {
        means: { type?: string; iban?: string; bic?: string; account_name?: string }[]
        terms: { description?: string; due_date?: string }[]
    }
}

interface ViewerProps {
    parsed: ParsedERechnung | null
    parse_error: string | null
    filename: string | null
    errors: Record<string, string>
    [key: string]: unknown
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Rechnungen", href: "/invoices" },
    { title: "E-Rechnung Viewer" },
]

const UNIT_LABELS: Record<string, string> = {
    C62: "Stk.",
    H87: "Stk.",
    HUR: "Std.",
    DAY: "Tage",
    MTK: "m²",
    MTQ: "m³",
    MTR: "m",
    KGM: "kg",
    TNE: "t",
    LTR: "l",
    KMT: "km",
    P1: "%",
    SET: "Set",
    XPK: "Paket",
}

export default function ERechnungViewer() {
    const { parsed, parse_error, filename, errors } = usePage<ViewerProps>().props
    const [dragActive, setDragActive] = useState(false)
    const [uploading, setUploading] = useState(false)
    const inputRef = useRef<HTMLInputElement>(null)

    const currency = parsed?.document.currency || "EUR"
    const money = (amount: number | null | undefined) =>
        amount === null || amount === undefined
            ? "—"
            : new Intl.NumberFormat("de-DE", { style: "currency", currency }).format(amount)
    const date = (value: string | null | undefined) =>
        value ? new Date(value).toLocaleDateString("de-DE") : "—"
    const unit = (code: string | null) => (code ? UNIT_LABELS[code] ?? code : "")

    const submitFile = (file: File) => {
        setUploading(true)
        router.post(
            route("e-invoice.parse"),
            { file },
            {
                forceFormData: true,
                preserveScroll: true,
                onFinish: () => setUploading(false),
            },
        )
    }

    const onDrop = (event: React.DragEvent) => {
        event.preventDefault()
        setDragActive(false)
        const file = event.dataTransfer.files?.[0]
        if (file) submitFile(file)
    }

    const fileError = errors?.file ?? parse_error

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="E-Rechnung Viewer" />
            <div className="flex flex-1 flex-col gap-6">
                <div>
                    <h1 className="text-xl font-bold text-gray-900 dark:text-gray-100">E-Rechnung Viewer</h1>
                    <p className="text-muted-foreground">
                        Empfangene E-Rechnungen lesbar anzeigen — XRechnung (XML, UBL oder CII) und
                        ZUGFeRD/Factur-X (PDF mit eingebettetem XML). Die Datei wird nur angezeigt, nicht gespeichert.
                    </p>
                </div>

                {/* Upload zone */}
                <Card>
                    <CardContent className="pt-6">
                        <div
                            role="button"
                            tabIndex={0}
                            onClick={() => inputRef.current?.click()}
                            onKeyDown={(e) => e.key === "Enter" && inputRef.current?.click()}
                            onDragOver={(e) => {
                                e.preventDefault()
                                setDragActive(true)
                            }}
                            onDragLeave={() => setDragActive(false)}
                            onDrop={onDrop}
                            className={`flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed p-8 text-center transition-colors ${
                                dragActive ? "border-primary bg-primary/5" : "border-muted-foreground/25 hover:border-muted-foreground/50"
                            }`}
                        >
                            <Upload className="h-8 w-8 text-muted-foreground" />
                            <p className="font-medium">
                                {uploading ? "Wird gelesen…" : "E-Rechnung hierher ziehen oder klicken"}
                            </p>
                            <p className="text-sm text-muted-foreground">XML (XRechnung) oder PDF (ZUGFeRD/Factur-X), max. 20 MB</p>
                            <input
                                ref={inputRef}
                                type="file"
                                accept=".xml,.pdf,application/xml,text/xml,application/pdf"
                                className="hidden"
                                onChange={(e) => {
                                    const file = e.target.files?.[0]
                                    if (file) submitFile(file)
                                    e.target.value = ""
                                }}
                            />
                        </div>

                        {fileError && (
                            <div className="mt-4 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
                                <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
                                <span>{fileError}</span>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {parsed && (
                    <>
                        {/* Document header */}
                        <Card>
                            <CardHeader>
                                <div className="flex flex-wrap items-start justify-between gap-2">
                                    <div>
                                        <CardTitle className="flex items-center gap-2">
                                            <FileSearch className="h-5 w-5" />
                                            {parsed.document.type_label} {parsed.document.number ?? ""}
                                        </CardTitle>
                                        <CardDescription>{filename}</CardDescription>
                                    </div>
                                    <Badge variant={parsed.profile.label.includes("veraltet") ? "destructive" : "secondary"}>
                                        {parsed.profile.label}
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <div>
                                    <p className="text-sm text-muted-foreground">Rechnungsdatum</p>
                                    <p className="font-medium">{date(parsed.document.date)}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Währung</p>
                                    <p className="font-medium">{parsed.document.currency ?? "—"}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Fällig am</p>
                                    <p className="font-medium">{date(parsed.payment.terms[0]?.due_date)}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Leitweg-ID / Käuferreferenz</p>
                                    <p className="font-medium">{parsed.document.buyer_reference ?? "—"}</p>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Parties */}
                        <div className="grid gap-4 md:grid-cols-2">
                            {([
                                ["Verkäufer (Rechnungssteller)", parsed.seller],
                                ["Käufer (Rechnungsempfänger)", parsed.buyer],
                            ] as [string, ParsedParty][]).map(([title, party]) => (
                                <Card key={title}>
                                    <CardHeader>
                                        <CardTitle className="text-base">{title}</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-2 text-sm">
                                        <p className="font-medium">{party.name ?? "—"}</p>
                                        <p className="text-muted-foreground">{party.address || "—"}</p>
                                        {party.tax_registrations.map((reg) => (
                                            <p key={`${reg.type}-${reg.number}`}>
                                                <span className="text-muted-foreground">{reg.type}:</span> {reg.number}
                                            </p>
                                        ))}
                                        {party.contact && (
                                            <p className="text-muted-foreground">
                                                {[party.contact.name, party.contact.phone, party.contact.email]
                                                    .filter(Boolean)
                                                    .join(" · ")}
                                            </p>
                                        )}
                                    </CardContent>
                                </Card>
                            ))}
                        </div>

                        {/* Positions */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <FileText className="h-4 w-4" />
                                    Positionen
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {parsed.positions.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        Keine Positionen enthalten (z.B. BASIC-WL- oder MINIMUM-Profil).
                                    </p>
                                ) : (
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-sm">
                                            <thead>
                                                <tr className="border-b text-left text-muted-foreground">
                                                    <th className="py-2 pr-2 font-medium">Pos.</th>
                                                    <th className="py-2 pr-2 font-medium">Bezeichnung</th>
                                                    <th className="py-2 pr-2 text-right font-medium">Menge</th>
                                                    <th className="py-2 pr-2 text-right font-medium">Einzelpreis</th>
                                                    <th className="py-2 pr-2 text-right font-medium">USt.</th>
                                                    <th className="py-2 text-right font-medium">Gesamt</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {parsed.positions.map((pos, index) => (
                                                    <tr key={pos.line_id ?? index} className="border-b last:border-0 align-top">
                                                        <td className="py-2 pr-2 text-muted-foreground">{pos.line_id ?? index + 1}</td>
                                                        <td className="py-2 pr-2">
                                                            <p className="font-medium">{pos.name ?? "—"}</p>
                                                            {pos.description && (
                                                                <p className="whitespace-pre-line text-xs text-muted-foreground">{pos.description}</p>
                                                            )}
                                                        </td>
                                                        <td className="py-2 pr-2 text-right whitespace-nowrap">
                                                            {pos.quantity ?? "—"} {unit(pos.unit)}
                                                        </td>
                                                        <td className="py-2 pr-2 text-right whitespace-nowrap">{money(pos.unit_price)}</td>
                                                        <td className="py-2 pr-2 text-right whitespace-nowrap">
                                                            {pos.tax_rate !== null ? `${pos.tax_rate} %` : "—"}
                                                        </td>
                                                        <td className="py-2 text-right whitespace-nowrap font-medium">{money(pos.line_total)}</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                )}

                                {parsed.allowances.length > 0 && (
                                    <div className="mt-4 space-y-1 border-t pt-3 text-sm">
                                        {parsed.allowances.map((entry, index) => (
                                            <div key={index} className="flex justify-between">
                                                <span className="text-muted-foreground">
                                                    {entry.is_charge ? "Zuschlag" : "Nachlass"}: {entry.reason}
                                                </span>
                                                <span>{entry.is_charge ? "" : "−"}{money(entry.amount)}</span>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        <div className="grid gap-4 md:grid-cols-2">
                            {/* Totals + tax breakdown */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">Beträge</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-2 text-sm">
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">Summe Positionen</span>
                                        <span>{money(parsed.totals.line_total)}</span>
                                    </div>
                                    {(parsed.totals.allowance_total ?? 0) > 0 && (
                                        <div className="flex justify-between">
                                            <span className="text-muted-foreground">Nachlässe</span>
                                            <span>−{money(parsed.totals.allowance_total)}</span>
                                        </div>
                                    )}
                                    {(parsed.totals.charge_total ?? 0) > 0 && (
                                        <div className="flex justify-between">
                                            <span className="text-muted-foreground">Zuschläge</span>
                                            <span>{money(parsed.totals.charge_total)}</span>
                                        </div>
                                    )}
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">Nettobetrag</span>
                                        <span>{money(parsed.totals.net_total)}</span>
                                    </div>
                                    {parsed.taxes.map((tax, index) => (
                                        <div key={index} className="flex justify-between">
                                            <span className="text-muted-foreground">
                                                USt. {tax.rate !== null ? `${tax.rate} %` : ""} ({tax.category_label})
                                                {tax.exemption_reason ? ` — ${tax.exemption_reason}` : ""}
                                            </span>
                                            <span>{money(tax.amount)}</span>
                                        </div>
                                    ))}
                                    <div className="flex justify-between border-t pt-2 text-base font-semibold">
                                        <span>Gesamtbetrag</span>
                                        <span>{money(parsed.totals.grand_total)}</span>
                                    </div>
                                    {(parsed.totals.prepaid ?? 0) > 0 && (
                                        <>
                                            <div className="flex justify-between">
                                                <span className="text-muted-foreground">Bereits gezahlt</span>
                                                <span>−{money(parsed.totals.prepaid)}</span>
                                            </div>
                                            <div className="flex justify-between font-semibold">
                                                <span>Zahlbetrag</span>
                                                <span>{money(parsed.totals.due_payable)}</span>
                                            </div>
                                        </>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Payment */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <Landmark className="h-4 w-4" />
                                        Zahlung
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3 text-sm">
                                    {parsed.payment.means.length === 0 && parsed.payment.terms.length === 0 && (
                                        <p className="text-muted-foreground">Keine Zahlungsinformationen enthalten.</p>
                                    )}
                                    {parsed.payment.means.map((mean, index) => (
                                        <div key={index} className="space-y-1">
                                            {mean.type && <p className="font-medium">{mean.type}</p>}
                                            {mean.iban && (
                                                <p>
                                                    <span className="text-muted-foreground">IBAN:</span> {mean.iban}
                                                </p>
                                            )}
                                            {mean.bic && (
                                                <p>
                                                    <span className="text-muted-foreground">BIC:</span> {mean.bic}
                                                </p>
                                            )}
                                            {mean.account_name && (
                                                <p>
                                                    <span className="text-muted-foreground">Kontoinhaber:</span> {mean.account_name}
                                                </p>
                                            )}
                                        </div>
                                    ))}
                                    {parsed.payment.terms.map((term, index) => (
                                        <div key={index} className="border-t pt-2 first:border-0 first:pt-0">
                                            {term.description && <p className="text-muted-foreground">{term.description}</p>}
                                            {term.due_date && (
                                                <p>
                                                    <span className="text-muted-foreground">Fällig am:</span> {date(term.due_date)}
                                                </p>
                                            )}
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>
                        </div>

                        {/* Notes */}
                        {parsed.document.notes.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">Bemerkungen</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-2 text-sm">
                                    {parsed.document.notes.map((note, index) => (
                                        <p key={index} className="whitespace-pre-line text-muted-foreground">{note}</p>
                                    ))}
                                </CardContent>
                            </Card>
                        )}

                        <div>
                            <Button variant="outline" onClick={() => router.get(route("e-invoice.viewer"))}>
                                <X className="mr-2 h-4 w-4" />
                                Andere Datei prüfen
                            </Button>
                        </div>
                    </>
                )}
            </div>
        </AppLayout>
    )
}
