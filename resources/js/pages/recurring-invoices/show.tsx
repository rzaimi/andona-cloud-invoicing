"use client"

import { Head, Link, router, usePage } from "@inertiajs/react"
import AppLayout from "@/layouts/app-layout"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Edit, Play, Pause, RotateCw, Trash2, ArrowLeft } from "lucide-react"
import { route } from "ziggy-js"
import type { BreadcrumbItem, Invoice, RecurringInvoiceProfile } from "@/types"

interface ShowProps {
    profile: RecurringInvoiceProfile & { generated_invoices?: Invoice[] }
    nextRuns: string[]
    scheduleLabel: string
}

const STATUS_LABEL: Record<string, { label: string; variant: "default" | "secondary" | "outline" | "destructive" }> = {
    active:    { label: "Aktiv",          variant: "default" },
    paused:    { label: "Pausiert",       variant: "secondary" },
    completed: { label: "Abgeschlossen",  variant: "outline" },
    cancelled: { label: "Abgebrochen",    variant: "destructive" },
}

const INVOICE_STATUS: Record<string, { label: string; variant: "default" | "secondary" | "outline" | "destructive" }> = {
    draft:     { label: "Entwurf",    variant: "outline" },
    sent:      { label: "Versendet",  variant: "secondary" },
    paid:      { label: "Bezahlt",    variant: "default" },
    overdue:   { label: "Überfällig", variant: "destructive" },
    cancelled: { label: "Storniert",  variant: "outline" },
}

export default function RecurringInvoicesShow() {
    const { profile, nextRuns, scheduleLabel } = usePage<ShowProps>().props as unknown as ShowProps

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Abo-Rechnungen", href: "/recurring-invoices" },
        { title: profile.name },
    ]

    const statusCfg = STATUS_LABEL[profile.status] ?? STATUS_LABEL.active

    const onRunNow = () => {
        if (confirm("Jetzt sofort eine Rechnung erzeugen?")) {
            router.post(route("recurring-invoices.run-now", profile.id))
        }
    }

    const onPause = () => router.post(route("recurring-invoices.pause", profile.id), {}, { preserveScroll: true })
    const onResume = () => router.post(route("recurring-invoices.resume", profile.id), {}, { preserveScroll: true })

    const onDelete = () => {
        if (confirm(`"${profile.name}" wirklich löschen?`)) {
            router.delete(route("recurring-invoices.destroy", profile.id))
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={profile.name} />
            <div className="p-4 md:p-6 space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <div className="flex items-center gap-3">
                            <Button variant="ghost" size="sm" asChild>
                                <Link href="/recurring-invoices">
                                    <ArrowLeft className="h-4 w-4" />
                                </Link>
                            </Button>
                            <h1 className="text-2xl font-semibold">{profile.name}</h1>
                            <Badge variant={statusCfg.variant}>{statusCfg.label}</Badge>
                        </div>
                        <p className="text-sm text-muted-foreground mt-1">
                            {scheduleLabel} · Kunde:{" "}
                            <Link
                                href={`/customers/${profile.customer_id}`}
                                className="underline underline-offset-2"
                            >
                                {profile.customer?.name ?? "—"}
                            </Link>
                        </p>
                    </div>
                    <div className="flex gap-2">
                        {profile.status === "active" && (
                            <>
                                <Button variant="outline" onClick={onRunNow}>
                                    <RotateCw className="h-4 w-4 mr-1" /> Jetzt ausführen
                                </Button>
                                <Button variant="outline" onClick={onPause}>
                                    <Pause className="h-4 w-4 mr-1" /> Pausieren
                                </Button>
                            </>
                        )}
                        {profile.status === "paused" && (
                            <Button variant="outline" onClick={onResume}>
                                <Play className="h-4 w-4 mr-1" /> Fortsetzen
                            </Button>
                        )}
                        <Button variant="outline" asChild>
                            <Link href={route("recurring-invoices.edit", profile.id)}>
                                <Edit className="h-4 w-4 mr-1" /> Bearbeiten
                            </Link>
                        </Button>
                        <Button variant="outline" onClick={onDelete}>
                            <Trash2 className="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Zeitplan</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <Row label="Nächste Ausführung">
                                {profile.next_run_date
                                    ? new Date(profile.next_run_date).toLocaleDateString("de-DE")
                                    : "—"}
                            </Row>
                            <Row label="Letzte Ausführung">
                                {profile.last_run_date
                                    ? new Date(profile.last_run_date).toLocaleDateString("de-DE")
                                    : "—"}
                            </Row>
                            <Row label="Startdatum">
                                {new Date(profile.start_date).toLocaleDateString("de-DE")}
                            </Row>
                            <Row label="Enddatum">
                                {profile.end_date
                                    ? new Date(profile.end_date).toLocaleDateString("de-DE")
                                    : "— (unbegrenzt)"}
                            </Row>
                            <Row label="Bereits erstellt">
                                {profile.occurrences_count}
                                {profile.max_occurrences ? ` / ${profile.max_occurrences}` : ""}
                            </Row>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Rechnungsvorlage</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <Row label="USt-Regime">{profile.vat_regime ?? "standard"}</Row>
                            <Row label="MwSt.-Satz">
                                {((profile.tax_rate ?? 0) * 100).toLocaleString("de-DE", {
                                    minimumFractionDigits: 0,
                                    maximumFractionDigits: 2,
                                })}
                                %
                            </Row>
                            <Row label="Zahlungsziel">{profile.due_days_after_issue ?? 14} Tage</Row>
                            <Row label="Automatischer Versand">{profile.auto_send ? "Ja" : "Nein"}</Row>
                            <Row label="Layout">{profile.layout?.name ?? "Standard-Layout"}</Row>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Vorschau: nächste Ausführungen</CardTitle>
                        <CardDescription>
                            Die folgenden Termine werden — falls der Status aktiv bleibt — zur Erzeugung neuer
                            Rechnungen herangezogen.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {nextRuns.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Keine weiteren Ausführungen geplant.
                            </p>
                        ) : (
                            <ul className="text-sm list-disc pl-5 space-y-1">
                                {nextRuns.map((d) => (
                                    <li key={d}>{new Date(d).toLocaleDateString("de-DE")}</li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Positionen</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Beschreibung</TableHead>
                                    <TableHead className="text-right">Menge</TableHead>
                                    <TableHead>Einheit</TableHead>
                                    <TableHead className="text-right">Einzelpreis</TableHead>
                                    <TableHead className="text-right">MwSt.</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {(profile.items ?? []).map((it) => (
                                    <TableRow key={it.id}>
                                        <TableCell className="whitespace-pre-wrap">{it.description}</TableCell>
                                        <TableCell className="text-right">{Number(it.quantity)}</TableCell>
                                        <TableCell>{it.unit}</TableCell>
                                        <TableCell className="text-right">
                                            {Number(it.unit_price).toLocaleString("de-DE", {
                                                style: "currency",
                                                currency: "EUR",
                                            })}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {it.tax_rate != null
                                                ? `${(Number(it.tax_rate) * 100).toFixed(0)}%`
                                                : "—"}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Erzeugte Rechnungen</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nummer</TableHead>
                                    <TableHead>Rechnungsdatum</TableHead>
                                    <TableHead>Fällig am</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">Betrag</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {(profile.generated_invoices ?? []).length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={5} className="text-center text-muted-foreground py-6">
                                            Noch keine Rechnungen aus diesem Abo erstellt.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {(profile.generated_invoices ?? []).map((inv) => {
                                    const cfg = INVOICE_STATUS[inv.status] ?? { label: inv.status, variant: "outline" as const }
                                    return (
                                        <TableRow key={inv.id}>
                                            <TableCell>
                                                <Link
                                                    href={`/invoices/${inv.id}`}
                                                    className="font-medium hover:underline"
                                                >
                                                    {inv.number}
                                                </Link>
                                            </TableCell>
                                            <TableCell>
                                                {new Date(inv.issue_date).toLocaleDateString("de-DE")}
                                            </TableCell>
                                            <TableCell>
                                                {new Date(inv.due_date).toLocaleDateString("de-DE")}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={cfg.variant}>{cfg.label}</Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {Number(inv.total).toLocaleString("de-DE", {
                                                    style: "currency",
                                                    currency: "EUR",
                                                })}
                                            </TableCell>
                                        </TableRow>
                                    )
                                })}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    )
}

function Row({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="flex justify-between gap-4">
            <span className="text-muted-foreground">{label}</span>
            <span className="font-medium">{children}</span>
        </div>
    )
}
