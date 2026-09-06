import { Head, Link, router } from "@inertiajs/react"
import AppLayout from "@/layouts/app-layout"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Bell, History, Send } from "lucide-react"
import type { BreadcrumbItem, PaginatedResponse } from "@/types"
import { route } from "ziggy-js"

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
    can_send_next: boolean
    next_level_name: string | null
    next_auto_due: boolean
    customer: { id: string; name: string; email: string | null } | null
}

interface Props {
    invoices: PaginatedResponse<MahnungInvoice>
    due_count: number
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Mahnwesen" },
]

function formatCurrency(value: number | string) {
    return new Intl.NumberFormat("de-DE", { style: "currency", currency: "EUR" }).format(Number(value) || 0)
}

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

export default function MahnungenIndex({ invoices, due_count }: Props) {
    const send = (invoice: MahnungInvoice) => {
        if (!confirm(`Nächste Stufe (${invoice.next_level_name}) für ${invoice.number} jetzt versenden?`)) {
            return
        }
        router.post(route("mahnungen.store", invoice.id))
    }

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
                        <CardContent className="text-2xl font-bold">{due_count}</CardContent>
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
                        <CardTitle className="flex items-center gap-2">
                            <Bell className="h-5 w-5" />
                            Offene Mahnungen
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {invoices.data.length === 0 ? (
                            <p className="text-sm text-muted-foreground py-8 text-center">
                                Keine Rechnungen im Mahnverfahren.
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
                                                <div className="flex flex-col gap-1">
                                                    {levelBadge(invoice.reminder_level, invoice.reminder_level_name)}
                                                    {invoice.next_auto_due && (
                                                        <span className="text-xs text-orange-600">Auto-Versand fällig</span>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right">{formatCurrency(invoice.total)}</TableCell>
                                            <TableCell className="text-right space-x-1">
                                                <Button variant="ghost" size="sm" asChild>
                                                    <Link href={route("mahnungen.show", invoice.id)}>
                                                        <History className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                                {invoice.can_send_next && (
                                                    <Button size="sm" onClick={() => send(invoice)}>
                                                        <Send className="h-4 w-4 mr-1" />
                                                        {invoice.next_level_name}
                                                    </Button>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    )
}
