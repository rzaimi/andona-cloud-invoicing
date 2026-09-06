import { Head, Link, router } from "@inertiajs/react"
import AppLayout from "@/layouts/app-layout"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { ArrowLeft, Send } from "lucide-react"
import type { BreadcrumbItem } from "@/types"
import { route } from "ziggy-js"

interface HistoryEntry {
    level: number
    level_name: string
    sent_at: string
    days_overdue: number
    fee: number
}

interface Props {
    invoice: {
        id: string
        number: string
        status: string
        due_date: string | null
        total: number | string
        customer: { id: string; name: string; email: string | null } | null
    }
    dunning: {
        reminder_level: number
        reminder_level_name: string
        last_reminder_sent_at: string | null
        reminder_fee: number | string
        reminder_history: HistoryEntry[]
        days_overdue: number
        can_send_next: boolean
        next_level: number | null
        next_level_name: string | null
        next_auto_due: boolean
    }
}

function formatCurrency(value: number | string) {
    return new Intl.NumberFormat("de-DE", { style: "currency", currency: "EUR" }).format(Number(value) || 0)
}

export default function MahnungShow({ invoice, dunning }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Mahnwesen", href: "/mahnungen" },
        { title: invoice.number },
    ]

    const send = () => {
        if (!confirm(`Nächste Stufe (${dunning.next_level_name}) für ${invoice.number} jetzt versenden?`)) {
            return
        }
        router.post(route("mahnungen.store", invoice.id))
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Mahnwesen ${invoice.number}`} />
            <div className="flex flex-1 flex-col gap-6">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <Button variant="ghost" size="sm" asChild className="mb-2 -ml-2">
                            <Link href={route("mahnungen.index")}>
                                <ArrowLeft className="h-4 w-4 mr-1" />
                                Zurück
                            </Link>
                        </Button>
                        <h1 className="text-xl font-bold">Mahnlauf {invoice.number}</h1>
                        <p className="text-muted-foreground">
                            {invoice.customer?.name ?? "Kunde"} · {formatCurrency(invoice.total)}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={route("invoices.show", invoice.id)}>Rechnung</Link>
                        </Button>
                        {dunning.can_send_next && (
                            <Button onClick={send}>
                                <Send className="h-4 w-4 mr-2" />
                                {dunning.next_level_name} senden
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm">Aktuelle Stufe</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Badge>{dunning.reminder_level_name}</Badge>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm">Überfällig</CardTitle>
                        </CardHeader>
                        <CardContent className="text-xl font-semibold">
                            {dunning.days_overdue > 0 ? `${dunning.days_overdue} Tage` : "—"}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm">Mahngebühren</CardTitle>
                            <CardDescription>bereits in der Rechnung enthalten</CardDescription>
                        </CardHeader>
                        <CardContent className="text-xl font-semibold">{formatCurrency(dunning.reminder_fee || 0)}</CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm">Letzter Versand</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {dunning.last_reminder_sent_at
                                ? new Date(dunning.last_reminder_sent_at).toLocaleString("de-DE")
                                : "—"}
                        </CardContent>
                    </Card>
                </div>

                {dunning.next_auto_due && (
                    <p className="text-sm text-orange-700">
                        Die nächste Stufe ist nach Intervall automatisch fällig.
                    </p>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Verlauf</CardTitle>
                        <CardDescription>Freundliche Erinnerung → 1. / 2. / 3. Mahnung → Inkasso</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {(dunning.reminder_history ?? []).length === 0 ? (
                            <p className="text-sm text-muted-foreground">Noch keine Mahnungen versendet.</p>
                        ) : (
                            <ol className="space-y-3">
                                {dunning.reminder_history.map((entry, i) => (
                                    <li key={`${entry.level}-${entry.sent_at}-${i}`} className="flex justify-between gap-4 border-b pb-3 last:border-0">
                                        <div>
                                            <p className="font-medium">{entry.level_name}</p>
                                            <p className="text-sm text-muted-foreground">
                                                {new Date(entry.sent_at).toLocaleString("de-DE")} · {entry.days_overdue} Tage überfällig
                                            </p>
                                        </div>
                                        <div className="text-sm">
                                            {Number(entry.fee) > 0 ? formatCurrency(entry.fee) : "keine Gebühr"}
                                        </div>
                                    </li>
                                ))}
                            </ol>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    )
}
