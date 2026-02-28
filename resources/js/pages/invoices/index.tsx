"use client"

import type React from "react"

import { useState } from "react"
import { Head, Link, router, usePage } from "@inertiajs/react"
import { useTranslation } from "react-i18next"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Badge } from "@/components/ui/badge"
import {
    Plus,
    Edit,
    Trash2,
    Search,
    FileText,
    Send,
    Clock,
    CheckCircle,
    XCircle,
    AlertTriangle,
    Bell,
    History,
    FileCheck,
    Download,
    Eye,
    MoreHorizontal,
    ArrowUpDown,
    ArrowUp,
    ArrowDown,
} from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import { SendEmailDialog } from "@/components/send-email-dialog"
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog"
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu"
import type { BreadcrumbItem, Invoice } from "@/types"
import { route } from "ziggy-js"
import { Pagination } from "@/components/pagination"

interface InvoicesIndexProps {
    invoices: {
        data: Invoice[]
        links: any[]
        meta?: {
            total: number
            from: number
            to: number
            current_page: number
            last_page: number
        }
        total?: number
    }
    filters: {
        search?: string
        status?: string
        sort?: string
        direction?: string
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
    const { t } = useTranslation()
    // @ts-ignore
    const { invoices, filters, stats } = usePage<InvoicesIndexProps>().props
    const [search, setSearch] = useState(filters.search || "")
    const [status, setStatus] = useState(filters.status || "all")
    const [sendDialogOpen, setSendDialogOpen] = useState(false)
    const [selectedInvoice, setSelectedInvoice] = useState<Invoice | null>(null)
    const [reminderHistoryOpen, setReminderHistoryOpen] = useState(false)
    const [reminderHistory, setReminderHistory] = useState<any>(null)
    const currentSort = filters.sort || "issue_date"
    const currentDirection = filters.direction || "desc"

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault()
        const params: any = {}
        if (search) params.search = search
        if (status && status !== 'all') params.status = status
        if (currentSort) params.sort = currentSort
        if (currentDirection) params.direction = currentDirection
        router.get("/invoices", params, { preserveScroll: true })
    }

    const handleStatusChange = (newStatus: string) => {
        setStatus(newStatus)
        const params: any = {}
        if (search) params.search = search
        if (newStatus && newStatus !== 'all') params.status = newStatus
        if (currentSort) params.sort = currentSort
        if (currentDirection) params.direction = currentDirection
        router.get("/invoices", params, { preserveScroll: true })
    }

    const handleSort = (field: string) => {
        let direction = "asc"
        
        // If clicking the same field, toggle direction
        if (currentSort === field) {
            direction = currentDirection === "asc" ? "desc" : "asc"
        }

        const params: any = {
            sort: field,
            direction: direction,
        }
        if (search) params.search = search
        if (status && status !== 'all') params.status = status
        
        router.get("/invoices", params, { preserveScroll: true })
    }

    const getSortIcon = (field: string) => {
        if (currentSort !== field) {
            return <ArrowUpDown className="ml-2 h-4 w-4 opacity-50" />
        }
        return currentDirection === "asc" 
            ? <ArrowUp className="ml-2 h-4 w-4" />
            : <ArrowDown className="ml-2 h-4 w-4" />
    }

    const handleDelete = (invoice: Invoice) => {
        if (confirm(t('pages.invoices.deleteConfirm', { number: invoice.number }))) {
            router.delete(`/invoices/${invoice.id}`)
        }
    }

    const handleSendReminder = (invoice: Invoice) => {
        if (confirm(t('pages.invoices.confirmSendReminder', { number: invoice.number }))) {
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

    const getTypeBadge = (invoice: any) => {
        const type = invoice.invoice_type || "standard"
        const seq  = invoice.sequence_number
        const labels: Record<string, string> = {
            standard:          "",
            abschlagsrechnung: `Abschlag ${seq ?? ""}`,
            schlussrechnung:   "Schlussrechnung",
            nachtragsrechnung: "Nachtrag",
            korrekturrechnung: "Korrektur",
        }
        const label = labels[type]
        if (!label) return null
        return (
            <Badge variant="outline" className="text-xs bg-indigo-50 text-indigo-700 border-indigo-200">
                {label}
            </Badge>
        )
    }

    const getStatusBadge = (status: string) => {
        const statusConfig = {
            draft: { label: t('common.draft'), variant: "outline" as const },
            sent: { label: t('common.sent'), variant: "secondary" as const },
            paid: { label: t('common.paid'), variant: "default" as const },
            overdue: { label: t('common.overdue'), variant: "destructive" as const },
            cancelled: { label: t('common.cancelled'), variant: "outline" as const },
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
            <Head title={t('pages.invoices.title')} />

            <div className="flex flex-1 flex-col gap-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-1xl font-bold text-gray-900 dark:text-gray-100">{t('pages.invoices.title')}</h1>
                        <p className="text-gray-600">{t('pages.invoices.subtitle')}</p>
                    </div>

                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            onClick={() => {
                                const params = new URLSearchParams()
                                if (filters.search) params.append('search', filters.search)
                                if (filters.status && filters.status !== 'all') params.append('status', filters.status)
                                if (filters.sort) params.append('sort', filters.sort)
                                if (filters.direction) params.append('direction', filters.direction)
                                window.location.href = route('export.invoices') + (params.toString() ? '?' + params.toString() : '')
                            }}
                        >
                            <Download className="mr-2 h-4 w-4" />
                            {t('common.export')}
                        </Button>
                        <Link href="/invoices/create">
                            <Button>
                                <Plus className="mr-2 h-4 w-4" />
                                {t('pages.invoices.new')}
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Statistics Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{t('pages.invoices.statsTotal')}</CardTitle>
                            <FileText className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{t('pages.invoices.statsDraft')}</CardTitle>
                            <Edit className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.draft}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{t('pages.invoices.statsSent')}</CardTitle>
                            <Clock className="h-4 w-4 text-blue-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600">{stats.sent}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{t('pages.invoices.statsPaid')}</CardTitle>
                            <CheckCircle className="h-4 w-4 text-green-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">{stats.paid}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{t('pages.invoices.statsOverdue')}</CardTitle>
                            <AlertTriangle className="h-4 w-4 text-red-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-red-600">{stats.overdue}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{t('common.cancelled')}</CardTitle>
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
                        <CardTitle>{t('pages.invoices.filterTitle')}</CardTitle>
                        <CardDescription>{t('pages.invoices.filterDesc')}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSearch} className="flex gap-4">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                                <Input
                                    placeholder={t('pages.invoices.searchPlaceholder')}
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-10"
                                />
                            </div>
                            <Select value={status} onValueChange={handleStatusChange}>
                                <SelectTrigger className="w-48">
                                    <SelectValue placeholder={t('common.status')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">{t('pages.invoices.allStatuses')}</SelectItem>
                                    <SelectItem value="draft">{t('common.draft')}</SelectItem>
                                    <SelectItem value="sent">{t('common.sent')}</SelectItem>
                                    <SelectItem value="paid">{t('common.paid')}</SelectItem>
                                    <SelectItem value="overdue">{t('common.overdue')}</SelectItem>
                                    <SelectItem value="cancelled">{t('common.cancelled')}</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button type="submit">{t('common.search')}</Button>
                            {(filters.search || filters.status || filters.sort) && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => {
                                        setSearch("")
                                        setStatus("all")
                                        router.get("/invoices")
                                    }}
                                >
                                    {t('common.reset')}
                                </Button>
                            )}
                        </form>
                    </CardContent>
                </Card>

                {/* Invoices Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>{t('nav.invoices')} ({stats.total})</CardTitle>
                        <CardDescription>{t('pages.invoices.subtitle')}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="-ml-3 h-8 data-[state=open]:bg-accent"
                                            onClick={() => handleSort("number")}
                                        >
                                            {t('pages.invoices.number')}
                                            {getSortIcon("number")}
                                        </Button>
                                    </TableHead>
                                    <TableHead>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="-ml-3 h-8 data-[state=open]:bg-accent"
                                            onClick={() => handleSort("customer")}
                                        >
                                            {t('pages.invoices.customer')}
                                            {getSortIcon("customer")}
                                        </Button>
                                    </TableHead>
                                    <TableHead>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="-ml-3 h-8 data-[state=open]:bg-accent"
                                            onClick={() => handleSort("issue_date")}
                                        >
                                            {t('pages.invoices.issueDate')}
                                            {getSortIcon("issue_date")}
                                        </Button>
                                    </TableHead>
                                    <TableHead>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="-ml-3 h-8 data-[state=open]:bg-accent"
                                            onClick={() => handleSort("due_date")}
                                        >
                                            {t('pages.invoices.dueDate')}
                                            {getSortIcon("due_date")}
                                        </Button>
                                    </TableHead>
                                    <TableHead>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="-ml-3 h-8 data-[state=open]:bg-accent"
                                            onClick={() => handleSort("total")}
                                        >
                                            {t('common.amount')}
                                            {getSortIcon("total")}
                                        </Button>
                                    </TableHead>
                                    <TableHead>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="-ml-3 h-8 data-[state=open]:bg-accent"
                                            onClick={() => handleSort("status")}
                                        >
                                            {t('common.status')}
                                            {getSortIcon("status")}
                                        </Button>
                                    </TableHead>
                                    <TableHead>Mahnung</TableHead>
                                    <TableHead className="w-[156px] text-right">{t('common.actions')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {invoices.data.map((invoice) => (
                                    <TableRow key={invoice.id} className="group">
                                        <TableCell className="font-medium">
                                            <div className="flex items-center gap-2 flex-wrap">
                                                {invoice.number}
                                                {getTypeBadge(invoice)}
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
                                                    + {formatCurrency(invoice.reminder_fee)} {t('settings.mahnungFee_short')}
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
                                        <TableCell className="w-[156px]">
                                            <div className="flex w-[156px] items-center justify-end gap-2">
                                                {/* Quick actions (visible on row hover, like the screenshot) */}
                                                <div className="flex items-center gap-1 invisible pointer-events-none group-hover:visible group-hover:pointer-events-auto">
                                                    <Link href={`/invoices/${invoice.id}`}>
                                                        <Button variant="ghost" size="icon" className="h-9 w-9" title="Anzeigen">
                                                            <Eye className="h-4 w-4" />
                                                        </Button>
                                                    </Link>
                                                    <Link href={`/invoices/${invoice.id}/edit`}>
                                                        <Button variant="ghost" size="icon" className="h-9 w-9" title="Bearbeiten">
                                                            <Edit className="h-4 w-4" />
                                                        </Button>
                                                    </Link>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-9 w-9"
                                                        title={t('pages.invoices.openPdf')}
                                                        onClick={() => window.open(route("invoices.pdf", invoice.id), "_blank")}
                                                    >
                                                        <FileText className="h-4 w-4" />
                                                    </Button>
                                                </div>

                                                {/* All actions */}
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="h-9 w-9 opacity-70 hover:opacity-100"
                                                            title="Aktionen"
                                                        >
                                                            <MoreHorizontal className="h-5 w-5" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end" className="w-56">
                                                        <DropdownMenuItem asChild>
                                                            <Link href={`/invoices/${invoice.id}`}>
                                                                <Eye className="mr-2 h-4 w-4" />
                                                                {t('common.view')}
                                                            </Link>
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem asChild>
                                                            <Link href={`/invoices/${invoice.id}/edit`}>
                                                                <Edit className="mr-2 h-4 w-4" />
                                                                {t('common.edit')}
                                                            </Link>
                                                        </DropdownMenuItem>

                                                        <DropdownMenuItem onClick={() => window.open(route("invoices.pdf", invoice.id), "_blank")}>
                                                            <FileText className="mr-2 h-4 w-4" />
                                                            PDF
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem onClick={() => window.open(route("invoices.xrechnung", invoice.id), "_blank")}>
                                                            <FileText className="mr-2 h-4 w-4" />
                                                            XRechnung (XML)
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem onClick={() => window.open(route("invoices.zugferd", invoice.id), "_blank")}>
                                                            <FileCheck className="mr-2 h-4 w-4" />
                                                            ZUGFeRD (PDF+XML)
                                                        </DropdownMenuItem>

                                                        {invoice.status === "draft" && (
                                                            <DropdownMenuItem
                                                                onClick={() => {
                                                                    setSelectedInvoice(invoice)
                                                                    setSendDialogOpen(true)
                                                                }}
                                                            >
                                                                <Send className="mr-2 h-4 w-4" />
                                                                {t('common.send')}
                                                            </DropdownMenuItem>
                                                        )}

                                                        {(invoice.status === "overdue" || invoice.status === "sent") && invoice.reminder_level < 5 && (
                                                            <DropdownMenuItem onClick={() => handleSendReminder(invoice)}>
                                                                <Bell className="mr-2 h-4 w-4" />
                                                                Mahnung versenden
                                                            </DropdownMenuItem>
                                                        )}

                                                        <DropdownMenuItem
                                                            className="text-red-600 focus:text-red-600"
                                                            onClick={() => handleDelete(invoice)}
                                                        >
                                                            <Trash2 className="mr-2 h-4 w-4" />
                                                            {t('common.delete')}
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        {invoices.data.length === 0 && (
                            <div className="text-center py-8">
                                <p className="text-gray-500">
                                    {filters.search || filters.status ? t('common.noResults') : t('common.noData')}
                                </p>
                                {!filters.search && !filters.status && (
                                    <Link href="/invoices/create">
                                        <Button className="mt-4">
                                            <Plus className="mr-2 h-4 w-4" />
                                            {t('pages.invoices.new')}
                                        </Button>
                                    </Link>
                                )}
                            </div>
                        )}

                        {/* Pagination */}
                        <Pagination links={invoices.links || []} className="mt-6" />
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
                            {t('pages.invoices.allReminders')}
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
                                    <p className="text-sm font-medium">{t('pages.invoices.totalReminderFees')}</p>
                                    <p className="text-2xl font-bold text-orange-600">
                                        {formatCurrency(reminderHistory.reminder_fee || 0)}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium">{t('pages.invoices.daysOverdue')}</p>
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
                                                        {entry.days_overdue} {t('pages.invoices.daysOverdue')}
                                                    </p>
                                                    {entry.fee > 0 && (
                                                        <p className="text-sm font-medium text-orange-600">
                                                            {t('pages.invoices.fee')}: {formatCurrency(entry.fee)}
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
                                        <p className="text-sm font-medium">{t('pages.invoices.nextLevel')}</p>
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
