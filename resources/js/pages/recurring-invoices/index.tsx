"use client"

import type React from "react"
import { useState } from "react"
import { Head, Link, router, usePage } from "@inertiajs/react"
import AppLayout from "@/layouts/app-layout"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Badge } from "@/components/ui/badge"
import { Plus, Search, Play, Pause, RotateCw, Edit, Trash2, Repeat } from "lucide-react"
import { route } from "ziggy-js"
import type { BreadcrumbItem, RecurringInvoiceProfile } from "@/types"
import { Pagination } from "@/components/pagination"

interface IndexProps {
    profiles: {
        data: (RecurringInvoiceProfile & { generated_invoices_count: number })[]
        links: any[]
        meta?: { total: number; from: number; to: number; current_page: number; last_page: number }
    }
    filters: { search?: string; status?: string; sort?: string; direction?: string }
    stats: { total: number; active: number; paused: number; completed: number }
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Abo-Rechnungen" },
]

const STATUS_LABEL: Record<string, { label: string; variant: "default" | "secondary" | "outline" | "destructive" }> = {
    active:    { label: "Aktiv",          variant: "default" },
    paused:    { label: "Pausiert",       variant: "secondary" },
    completed: { label: "Abgeschlossen",  variant: "outline" },
    cancelled: { label: "Abgebrochen",    variant: "destructive" },
}

const INTERVAL_LABEL: Record<string, string> = {
    day: "Täglich",
    week: "Wöchentlich",
    month: "Monatlich",
    quarter: "Quartalsweise",
    year: "Jährlich",
}

export default function RecurringInvoicesIndex() {
    const { profiles, filters, stats } = usePage<IndexProps>().props as unknown as IndexProps
    const [search, setSearch] = useState(filters.search ?? "")
    const [status, setStatus] = useState(filters.status ?? "all")

    const applyFilters = (next: Partial<{ search: string; status: string }>) => {
        const params: Record<string, string> = {}
        const s = next.search ?? search
        const st = next.status ?? status
        if (s) params.search = s
        if (st && st !== "all") params.status = st
        router.get("/recurring-invoices", params, { preserveScroll: true })
    }

    const onSearch = (e: React.FormEvent) => {
        e.preventDefault()
        applyFilters({})
    }

    const onStatusChange = (v: string) => {
        setStatus(v)
        applyFilters({ status: v })
    }

    const onDelete = (p: RecurringInvoiceProfile) => {
        if (confirm(`Abo-Rechnung "${p.name}" wirklich löschen?`)) {
            router.delete(route("recurring-invoices.destroy", p.id))
        }
    }

    const onRunNow = (p: RecurringInvoiceProfile) => {
        if (confirm(`Jetzt sofort eine Rechnung aus "${p.name}" erzeugen?`)) {
            router.post(route("recurring-invoices.run-now", p.id))
        }
    }

    const onPause = (p: RecurringInvoiceProfile) =>
        router.post(route("recurring-invoices.pause", p.id), {}, { preserveScroll: true })

    const onResume = (p: RecurringInvoiceProfile) =>
        router.post(route("recurring-invoices.resume", p.id), {}, { preserveScroll: true })

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Abo-Rechnungen" />
            <div className="p-4 md:p-6 space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold flex items-center gap-2">
                            <Repeat className="h-5 w-5" />
                            Abo-Rechnungen
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Wiederkehrende Rechnungen, die automatisch erzeugt werden.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={route("recurring-invoices.create")}>
                            <Plus className="h-4 w-4 mr-1" />
                            Neue Abo-Rechnung
                        </Link>
                    </Button>
                </div>

                <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <StatCard label="Gesamt" value={stats.total} />
                    <StatCard label="Aktiv" value={stats.active} />
                    <StatCard label="Pausiert" value={stats.paused} />
                    <StatCard label="Abgeschlossen" value={stats.completed} />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Filter</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={onSearch} className="flex flex-col sm:flex-row gap-3">
                            <div className="relative flex-1">
                                <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Name oder Kunde suchen"
                                    className="pl-8"
                                />
                            </div>
                            <Select value={status} onValueChange={onStatusChange}>
                                <SelectTrigger className="sm:w-48">
                                    <SelectValue placeholder="Alle Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Alle Status</SelectItem>
                                    <SelectItem value="active">Aktiv</SelectItem>
                                    <SelectItem value="paused">Pausiert</SelectItem>
                                    <SelectItem value="completed">Abgeschlossen</SelectItem>
                                    <SelectItem value="cancelled">Abgebrochen</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button type="submit" variant="outline">
                                Anwenden
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Kunde</TableHead>
                                    <TableHead>Zeitplan</TableHead>
                                    <TableHead>Nächste Ausführung</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">Erzeugt</TableHead>
                                    <TableHead className="text-right">Aktionen</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {profiles.data.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={7} className="text-center text-muted-foreground py-8">
                                            Noch keine Abo-Rechnungen.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {profiles.data.map((p) => {
                                    const statusCfg = STATUS_LABEL[p.status] ?? STATUS_LABEL.active
                                    return (
                                        <TableRow key={p.id}>
                                            <TableCell>
                                                <Link
                                                    href={route("recurring-invoices.show", p.id)}
                                                    className="font-medium hover:underline"
                                                >
                                                    {p.name}
                                                </Link>
                                            </TableCell>
                                            <TableCell>{p.customer?.name ?? "—"}</TableCell>
                                            <TableCell className="text-sm">
                                                {INTERVAL_LABEL[p.interval_unit] ?? p.interval_unit}
                                                {p.interval_count > 1 ? ` (alle ${p.interval_count})` : ""}
                                            </TableCell>
                                            <TableCell className="text-sm">
                                                {p.next_run_date
                                                    ? new Date(p.next_run_date).toLocaleDateString("de-DE")
                                                    : "—"}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={statusCfg.variant}>{statusCfg.label}</Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {p.generated_invoices_count ?? 0}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="inline-flex gap-1">
                                                    {p.status === "active" && (
                                                        <>
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                title="Jetzt ausführen"
                                                                onClick={() => onRunNow(p)}
                                                            >
                                                                <RotateCw className="h-4 w-4" />
                                                            </Button>
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                title="Pausieren"
                                                                onClick={() => onPause(p)}
                                                            >
                                                                <Pause className="h-4 w-4" />
                                                            </Button>
                                                        </>
                                                    )}
                                                    {p.status === "paused" && (
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            title="Fortsetzen"
                                                            onClick={() => onResume(p)}
                                                        >
                                                            <Play className="h-4 w-4" />
                                                        </Button>
                                                    )}
                                                    <Button size="sm" variant="outline" asChild title="Bearbeiten">
                                                        <Link href={route("recurring-invoices.edit", p.id)}>
                                                            <Edit className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        title="Löschen"
                                                        onClick={() => onDelete(p)}
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    )
                                })}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Pagination links={profiles.links} />
            </div>
        </AppLayout>
    )
}

function StatCard({ label, value }: { label: string; value: number }) {
    return (
        <Card>
            <CardContent className="p-4">
                <div className="text-sm text-muted-foreground">{label}</div>
                <div className="text-2xl font-semibold">{value}</div>
            </CardContent>
        </Card>
    )
}
