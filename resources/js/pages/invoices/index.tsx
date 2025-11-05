"use client"

import type React from "react"

import { useState } from "react"
import { Head, Link, router, usePage } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Badge } from "@/components/ui/badge"
import { Plus, Edit, Trash2, Search, FileText, Send, Clock, CheckCircle, XCircle, AlertTriangle, Bell, History, FileCheck, ChevronDown } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import { SendEmailDialog } from "@/components/send-email-dialog"
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog"
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu"
import type { BreadcrumbItem, Invoice } from "@/types"

interface InvoicesIndexProps {
    invoices: {
        data: Invoice[]
        links: any[]
        meta: any
    }
    filters: {
        search?: string
        status?: string
    }
    stats: {
        total: number
        draft: number
        sent: number
        paid: number
        overdue: number
        cancelled: number
    }
}

const breadcrumbs: BreadcrumbItem[] = [{ title: "Dashboard", href: "/dashboard" }, { title: "Rechnungen" }]

export default function InvoicesIndex() {
    // @ts-ignore
    const { invoices, filters, stats } = usePage<InvoicesIndexProps>().props
    const [search, setSearch] = useState(filters.search || "")
    const [status, setStatus] = useState(filters.status || "all")
    const [sendDialogOpen, setSendDialogOpen] = useState(false)
    const [selectedInvoice, setSelectedInvoice] = useState<Invoice | null>(null)
    const [reminderHistoryOpen, setReminderHistoryOpen] = useState(false)
    const [reminderHistory, setReminderHistory] = useState<any>(null)

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault()
        const params: any = {}
        if (search) params.search = search
        if (status && status !== 'all') params.status = status
        router.get("/invoices", params, { preserveScroll: true })
    }

    const handleStatusChange = (newStatus: string) => {
        setStatus(newStatus)
        const params: any = {}
        if (search) params.search = search
        if (newStatus && newStatus !== 'all') params.status = newStatus
        router.get("/invoices", params, { preserveScroll: true })
    }

    const handleDelete = (invoice: Invoice) => {
        if (confirm(`Möchten Sie die Rechnung "${invoice.number}" wirklich löschen?`)) {
            router.delete(`/invoices/${invoice.id}`)
        }
    }

    const handleSendReminder = (invoice: Invoice) => {
        if (confirm(`Möchten Sie die nächste Mahnung für Rechnung "${invoice.number}" versenden?`)) {
            router.post(route("invoices.send-reminder", invoice.id))
        }
    }

    const fetchReminderHistory = async (invoice: Invoice) => {
        try {
            const response = await fetch(route("invoices.reminder-history", invoice.id))
            const data = await response.json()
            setReminderHistory(data)
            setReminderHistoryOpen(true)
        } catch (error) {
            console.error("Failed to fetch reminder history:", error)
        }
    }

    const getReminderBadge = (invoice: any) => {
        const level = invoice.reminder_level || 0
        if (level === 0) return null
        
        const badges: Record<number, { label: string; color: string }> = {
            1: { label: "Freundlich", color: "bg-blue-100 text-blue-800" },
            2: { label: "1. Mahnung", color: "bg-yellow-100 text-yellow-800" },
            3: { label: "2. Mahnung", color: "bg-orange-100 text-orange-800" },
            4: { label: "3. Mahnung", color: "bg-red-100 text-red-800" },
            5: { label: "Inkasso", color: "bg-gray-900 text-white" },
        }
        
        const badge = badges[level]
        if (!badge) return null
        
        return (
            <Badge className={`${badge.color} text-xs`}>
                {badge.label}
            </Badge>
        )
    }

    const getStatusBadge = (status: string) => {
        const statusConfig = {
            draft: { label: "Entwurf", variant: "outline" as const },
            sent: { label: "Versendet", variant: "secondary" as const },
            paid: { label: "Bezahlt", variant: "default" as const },
            overdue: { label: "Überfällig", variant: "destructive" as const },
            cancelled: { label: "Storniert", variant: "outline" as const },
        }

        const config = statusConfig[status as keyof typeof statusConfig] || statusConfig.draft
        return <Badge variant={config.variant}>{config.label}</Badge>
    }

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat("de-DE", {
            style: "currency",
            currency: "EUR",
        }).format(amount)
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Rechnungen" />

            <div className="flex flex-1 flex-col gap-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Rechnungsverwaltung</h1>
                        <p className="text-gray-600">Verwalten Sie Ihre Rechnungen und deren Status</p>
                    </div>

                    <Link href="/invoices/create">
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            Neue Rechnung
                        </Button>
                    </Link>
                </div>

                {/* Statistics Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Gesamt</CardTitle>
                            <FileText className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Entwürfe</CardTitle>
                            <Edit className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.draft}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Versendet</CardTitle>
                            <Clock className="h-4 w-4 text-blue-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600">{stats.sent}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Bezahlt</CardTitle>
                            <CheckCircle className="h-4 w-4 text-green-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">{stats.paid}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Überfällig</CardTitle>
                            <AlertTriangle className="h-4 w-4 text-red-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-red-600">{stats.overdue}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Storniert</CardTitle>
                            <XCircle className="h-4 w-4 text-gray-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-gray-600">{stats.cancelled}</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle>Rechnungen filtern</CardTitle>
                        <CardDescription>Suchen und filtern Sie Ihre Rechnungen</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSearch} className="flex gap-4">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                                <Input
                                    placeholder="Nach Rechnungsnummer oder Kunde suchen..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-10"
                                />
                            </div>
                            <Select value={status} onValueChange={handleStatusChange}>
                                <SelectTrigger className="w-48">
                                    <SelectValue placeholder="Status wählen" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Alle Status</SelectItem>
                                    <SelectItem value="draft">Entwurf</SelectItem>
                                    <SelectItem value="sent">Versendet</SelectItem>
                                    <SelectItem value="paid">Bezahlt</SelectItem>
                                    <SelectItem value="overdue">Überfällig</SelectItem>
                                    <SelectItem value="cancelled">Storniert</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button type="submit">Suchen</Button>
                            {(filters.search || filters.status) && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => {
                                        setSearch("")
                                        setStatus("all")
                                        router.get("/invoices")
                                    }}
                                >
                                    Zurücksetzen
                                </Button>
                            )}
                        </form>
                    </CardContent>
                </Card>

                {/* Invoices Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Rechnungen ()</CardTitle>
                        <CardDescription>Alle Rechnungen in Ihrem System</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Rechnungsnummer</TableHead>
                                    <TableHead>Kunde</TableHead>
                                    <TableHead>Rechnungsdatum</TableHead>
                                    <TableHead>Fälligkeitsdatum</TableHead>
                                    <TableHead>Betrag</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Mahnung</TableHead>
                                    <TableHead>Aktionen</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {invoices.data.map((invoice) => (
                                    <TableRow key={invoice.id}>
                                        <TableCell className="font-medium">
                                            <div className="flex items-center gap-2">
                                                {invoice.number}
                                                {invoice.is_correction && (
                                                    <Badge variant="destructive" className="text-xs">
                                                        Storno
                                                    </Badge>
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell>{invoice.customer?.name}</TableCell>
                                        <TableCell>{new Date(invoice.issue_date).toLocaleDateString("de-DE")}</TableCell>
                                        <TableCell>{new Date(invoice.due_date).toLocaleDateString("de-DE")}</TableCell>
                                        <TableCell className="font-medium">
                                            {formatCurrency(invoice.total)}
                                            {invoice.reminder_fee > 0 && (
                                                <div className="text-xs text-orange-600">
                                                    + {formatCurrency(invoice.reminder_fee)} Gebühr
                                                </div>
                                            )}
                                        </TableCell>
                                        <TableCell>{getStatusBadge(invoice.status)}</TableCell>
                                        <TableCell>
                                            <div className="flex flex-col gap-1">
                                                {getReminderBadge(invoice)}
                                                {invoice.reminder_level > 0 && (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="h-6 px-2 text-xs"
                                                        onClick={() => fetchReminderHistory(invoice)}
                                                    >
                                                        <History className="h-3 w-3 mr-1" />
                                                        Historie
                                                    </Button>
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex space-x-2">
                                                <Link href={`/invoices/${invoice.id}/edit`}>
                                                    <Button variant="outline" size="sm" title="Bearbeiten">
                                                        <Edit className="h-4 w-4" />
                                                    </Button>
                                                </Link>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => window.open(route("invoices.pdf", invoice.id), "_blank")}
                                                >
                                                    <FileText className="h-4 w-4 mr-1" />
                                                    PDF
                                                </Button>
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="outline" size="sm" title="E-Rechnung">
                                                            <FileCheck className="h-4 w-4 mr-1" />
                                                            <ChevronDown className="h-3 w-3" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        <DropdownMenuItem
                                                            onClick={() => window.open(route("invoices.xrechnung", invoice.id), "_blank")}
                                                        >
                                                            <FileText className="mr-2 h-4 w-4" />
                                                            XRechnung (XML)
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem
                                                            onClick={() => window.open(route("invoices.zugferd", invoice.id), "_blank")}
                                                        >
                                                            <FileCheck className="mr-2 h-4 w-4" />
                                                            ZUGFeRD (PDF+XML)
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                                {invoice.status === "draft" && (
                                                    <Button 
                                                        variant="outline" 
                                                        size="sm" 
                                                        title="Versenden"
                                                        onClick={() => {
                                                            setSelectedInvoice(invoice)
                                                            setSendDialogOpen(true)
                                                        }}
                                                    >
                                                        <Send className="h-4 w-4" />
                                                    </Button>
                                                )}
                                                {(invoice.status === "overdue" || invoice.status === "sent") && invoice.reminder_level < 5 && (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        className="bg-orange-50 hover:bg-orange-100"
                                                        title="Mahnung versenden"
                                                        onClick={() => handleSendReminder(invoice)}
                                                    >
                                                        <Bell className="h-4 w-4" />
                                                    </Button>
                                                )}
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => handleDelete(invoice)}
                                                    className="text-red-600 hover:text-red-700"
                                                    title="Löschen"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        {invoices.data.length === 0 && (
                            <div className="text-center py-8">
                                <p className="text-gray-500">
                                    {filters.search || filters.status ? "Keine Rechnungen gefunden." : "Noch keine Rechnungen vorhanden."}
                                </p>
                                {!filters.search && !filters.status && (
                                    <Link href="/invoices/create">
                                        <Button className="mt-4">
                                            <Plus className="mr-2 h-4 w-4" />
                                            Erste Rechnung erstellen
                                        </Button>
                                    </Link>
                                )}
                            </div>
                        )}

                        {/* Pagination */}
                        {invoices.links && invoices.links.length > 3 && (
                            <div className="flex justify-center mt-6 gap-2">
                                {invoices.links.map((link, index) => (
                                    <Button
                                        key={index}
                                        variant={link.active ? "default" : "outline"}
                                        size="sm"
                                        disabled={!link.url}
                                        onClick={() => link.url && router.get(link.url)}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {selectedInvoice && (
                <SendEmailDialog
                    open={sendDialogOpen}
                    onOpenChange={setSendDialogOpen}
                    type="invoice"
                    documentId={selectedInvoice.id}
                    documentNumber={selectedInvoice.number}
                    customerEmail={selectedInvoice.customer?.email}
                    onSuccess={() => {
                        router.reload()
                    }}
                />
            )}

            {/* Reminder History Modal */}
            <Dialog open={reminderHistoryOpen} onOpenChange={setReminderHistoryOpen}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Mahnhistorie</DialogTitle>
                        <DialogDescription>
                            Alle versendeten Mahnungen für diese Rechnung
                        </DialogDescription>
                    </DialogHeader>
                    {reminderHistory && (
                        <div className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm font-medium">Aktuelle Mahnstufe</p>
                                    <p className="text-2xl font-bold">{reminderHistory.reminder_level_name}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium">Gesamte Mahngebühren</p>
                                    <p className="text-2xl font-bold text-orange-600">
                                        {formatCurrency(reminderHistory.reminder_fee || 0)}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium">Tage überfällig</p>
                                    <p className="text-2xl font-bold text-red-600">{reminderHistory.days_overdue || 0}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium">Letzte Mahnung</p>
                                    <p className="text-sm">
                                        {reminderHistory.last_reminder_sent_at 
                                            ? new Date(reminderHistory.last_reminder_sent_at).toLocaleDateString("de-DE")
                                            : "Keine"}
                                    </p>
                                </div>
                            </div>

                            <div className="border-t pt-4">
                                <h4 className="font-semibold mb-3">Historie</h4>
                                <div className="space-y-3">
                                    {reminderHistory.reminder_history && reminderHistory.reminder_history.length > 0 ? (
                                        reminderHistory.reminder_history.map((entry: any, index: number) => (
                                            <div key={index} className="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                                                <div className="flex-shrink-0">
                                                    <Badge className={`${
                                                        entry.level === 1 ? "bg-blue-100 text-blue-800" :
                                                        entry.level === 2 ? "bg-yellow-100 text-yellow-800" :
                                                        entry.level === 3 ? "bg-orange-100 text-orange-800" :
                                                        entry.level === 4 ? "bg-red-100 text-red-800" :
                                                        "bg-gray-900 text-white"
                                                    }`}>
                                                        {entry.level_name}
                                                    </Badge>
                                                </div>
                                                <div className="flex-1">
                                                    <p className="text-sm">
                                                        <strong>Versendet am:</strong>{" "}
                                                        {new Date(entry.sent_at).toLocaleDateString("de-DE", {
                                                            year: "numeric",
                                                            month: "long",
                                                            day: "numeric",
                                                            hour: "2-digit",
                                                            minute: "2-digit",
                                                        })}
                                                    </p>
                                                    <p className="text-sm text-gray-600">
                                                        {entry.days_overdue} Tage überfällig
                                                    </p>
                                                    {entry.fee > 0 && (
                                                        <p className="text-sm font-medium text-orange-600">
                                                            Gebühr: {formatCurrency(entry.fee)}
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                        ))
                                    ) : (
                                        <p className="text-sm text-gray-500 text-center py-4">
                                            Noch keine Mahnungen versendet
                                        </p>
                                    )}
                                </div>
                            </div>

                            {reminderHistory.can_send_next && (
                                <div className="border-t pt-4">
                                    <div className="bg-orange-50 p-4 rounded-lg">
                                        <p className="text-sm font-medium">Nächste Mahnstufe</p>
                                        <p className="text-lg font-bold">{reminderHistory.next_level_name}</p>
                                        <Button
                                            className="mt-2"
                                            onClick={() => {
                                                setReminderHistoryOpen(false)
                                                // Trigger reminder send if needed
                                            }}
                                        >
                                            <Bell className="mr-2 h-4 w-4" />
                                            Jetzt versenden
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </AppLayout>
    )
}
