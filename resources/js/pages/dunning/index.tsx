import { Head, Link, router, usePage } from "@inertiajs/react"
import { useState } from "react"
import AppLayout from "@/layouts/app-layout"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Bell, History, Send, PauseCircle, PlayCircle, AlertTriangle } from "lucide-react"
import type { BreadcrumbItem, PaginatedResponse } from "@/types"
import { route } from "ziggy-js"
import { cn } from "@/lib/utils"
import { Pagination } from "@/components/pagination"
import { PauseDunningDialog } from "@/components/pause-dunning-dialog"
import { formatCurrency as formatCurrencyUtil } from "@/utils/formatting"

interface MahnungInvoice {
    id: string
    number: string
    status: string
    due_date: string | null
    total: number | string
    reminder_level: number
    reminder_level_name: string
    reminder_fee: number | string
    last_reminder_sent_at: string | null
    days_overdue: number
    dunning_paused_until: string | null
    is_paused: boolean
    can_send_next: boolean
    next_level_name: string | null
    next_auto_due: boolean
    last_send_failed: boolean
    last_send_error: string | null
    customer: { id: string; name: string; email: string | null } | null
}

interface Props {
    invoices: PaginatedResponse<MahnungInvoice>
    due_count: number
    filter?: string | null
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Mahnwesen" },
]

function levelBadge(level: number, name: string) {
    const colors: Record<number, string> = {
        0: "bg-muted text-muted-foreground",
        1: "bg-blue-100 text-blue-800",
        2: "bg-yellow-100 text-yellow-800",
        3: "bg-orange-100 text-orange-800",
        4: "bg-red-100 text-red-800",
        5: "bg-red-200 text-red-900",
    }
    return <Badge className={colors[level] ?? "bg-muted"}>{name}</Badge>
}

export default function MahnungenIndex({ invoices, due_count, filter }: Props) {
    const [pauseTarget, setPauseTarget] = useState<MahnungInvoice | null>(null)
    const { auth } = usePage().props as any
    const formatCurrency = (value: number | string) => formatCurrencyUtil(Number(value), auth?.user?.company?.settings)

    const send = (invoice: MahnungInvoice) => {
        if (!confirm(`Nächste Stufe (${invoice.next_level_name}) für ${invoice.number} jetzt versenden?`)) {
            return
        }
        router.post(route("dunning.store", invoice.id), {}, { preserveScroll: true })
    }

    const sendAllDue = () => {
        if (!confirm(`${due_count} fällige Mahnung(en) jetzt versenden?`)) {
            return
        }
        router.post(route("dunning.send-due"), {}, { preserveScroll: true })
    }

    const resume = (invoice: MahnungInvoice) => {
        router.post(route("dunning.resume", invoice.id), {}, { preserveScroll: true })
    }

    const setFilter = (value: string | null) => {
        router.get(route("dunning.index"), value ? { filter: value } : {}, { preserveState: false, preserveScroll: true })
    }

    const filters = [
        { value: null, label: "Alle" },
        { value: "failed", label: "Versand fehlgeschlagen" },
        { value: "paused", label: "Pausiert" },
    ]

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Mahnwesen" />
            <div className="flex flex-1 flex-col gap-6">
                <div>
                    <h1 className="text-xl font-bold">Mahnwesen</h1>
                    <p className="text-muted-foreground">
                        Offene Rechnungen im deutschen Mahnverfahren. Manuell kann eine Stufe vor Fälligkeit
                        gesendet werden; der tägliche Lauf wartet auf die hinterlegten Intervalle.
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">Heute automatisch fällig</CardTitle>
                            <CardDescription>Nächste Stufe hat das Tagesintervall erreicht</CardDescription>
                        </CardHeader>
                        <CardContent className="flex items-center justify-between">
                            <span className="text-2xl font-bold">{due_count}</span>
                            {due_count > 0 && (
                                <Button size="sm" onClick={sendAllDue}>
                                    <Send className="mr-1 h-4 w-4" />
                                    Alle jetzt versenden
                                </Button>
                            )}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">In Bearbeitung</CardTitle>
                            <CardDescription>Gesendete und überfällige Rechnungen</CardDescription>
                        </CardHeader>
                        <CardContent className="text-2xl font-bold">{invoices.total}</CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <CardTitle className="flex items-center gap-2">
                                <Bell className="h-5 w-5" />
                                Offene Mahnungen
                            </CardTitle>
                            <div className="flex gap-1">
                                {filters.map((f) => (
                                    <button
                                        key={f.label}
                                        type="button"
                                        onClick={() => setFilter(f.value)}
                                        className={cn(
                                            "rounded-full border px-3 py-1 text-xs",
                                            (filter ?? null) === f.value
                                                ? "border-primary bg-primary/10 font-medium text-foreground"
                                                : "text-muted-foreground hover:bg-muted",
                                        )}
                                    >
                                        {f.label}
                                    </button>
                                ))}
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        {invoices.data.length === 0 ? (
                            <p className="text-sm text-muted-foreground py-8 text-center">
                                Keine Rechnungen {filter ? "für diesen Filter" : "im Mahnverfahren"}.
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Rechnung</TableHead>
                                        <TableHead>Kunde</TableHead>
                                        <TableHead>Fällig</TableHead>
                                        <TableHead>Überfällig</TableHead>
                                        <TableHead>Stufe</TableHead>
                                        <TableHead className="text-right">Betrag</TableHead>
                                        <TableHead />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {invoices.data.map((invoice) => (
                                        <TableRow key={invoice.id}>
                                            <TableCell className="font-medium">
                                                <Link href={route("invoices.show", invoice.id)} className="hover:underline">
                                                    {invoice.number}
                                                </Link>
                                            </TableCell>
                                            <TableCell>{invoice.customer?.name ?? "—"}</TableCell>
                                            <TableCell>
                                                {invoice.due_date
                                                    ? new Date(invoice.due_date).toLocaleDateString("de-DE")
                                                    : "—"}
                                            </TableCell>
                                            <TableCell>
                                                {invoice.days_overdue > 0 ? `${invoice.days_overdue} Tage` : "—"}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-col items-start gap-1">
                                                    {levelBadge(invoice.reminder_level, invoice.reminder_level_name)}
                                                    {invoice.last_send_failed && (
                                                        <Badge variant="destructive" className="gap-1" title={invoice.last_send_error ?? undefined}>
                                                            <AlertTriangle className="h-3 w-3" />
                                                            Versand fehlgeschlagen
                                                        </Badge>
                                                    )}
                                                    {invoice.is_paused && invoice.dunning_paused_until && (
                                                        <Badge variant="outline" className="gap-1">
                                                            <PauseCircle className="h-3 w-3" />
                                                            Pausiert bis {new Date(invoice.dunning_paused_until).toLocaleDateString("de-DE")}
                                                        </Badge>
                                                    )}
                                                    {!invoice.is_paused && invoice.next_auto_due && (
                                                        <span className="text-xs text-orange-600">Auto-Versand fällig</span>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right">{formatCurrency(invoice.total)}</TableCell>
                                            <TableCell className="text-right space-x-1 whitespace-nowrap">
                                                <Button variant="ghost" size="sm" asChild title="Verlauf">
                                                    <Link href={route("dunning.show", invoice.id)}>
                                                        <History className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                                {invoice.is_paused ? (
                                                    <Button variant="ghost" size="sm" onClick={() => resume(invoice)} title="Mahnlauf fortsetzen">
                                                        <PlayCircle className="h-4 w-4" />
                                                    </Button>
                                                ) : (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => setPauseTarget(invoice)}
                                                        title="Mahnlauf pausieren"
                                                    >
                                                        <PauseCircle className="h-4 w-4" />
                                                    </Button>
                                                )}
                                                {invoice.can_send_next && (
                                                    <Button size="sm" onClick={() => send(invoice)}>
                                                        <Send className="h-4 w-4 mr-1" />
                                                        {invoice.last_send_failed ? "Erneut senden" : invoice.next_level_name}
                                                    </Button>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                        <Pagination links={(invoices as any).links || []} className="mt-6" />
                    </CardContent>
                </Card>
            </div>

            <PauseDunningDialog
                invoiceId={pauseTarget?.id ?? null}
                invoiceNumber={pauseTarget?.number}
                onClose={() => setPauseTarget(null)}
            />
        </AppLayout>
    )
}
