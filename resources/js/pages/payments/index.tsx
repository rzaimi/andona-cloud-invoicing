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
import { Plus, Edit, Trash2, Search, CreditCard, CheckCircle, Clock, XCircle, Eye } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem } from "@/types"
import { route } from "ziggy-js"

interface Payment {
    id: string
    invoice_id: string
    company_id: string
    amount: number
    payment_date: string
    payment_method?: string
    reference?: string
    notes?: string
    status: "pending" | "completed" | "cancelled"
    created_by?: string
    invoice?: {
        id: string
        number: string
        customer_id: string
        customer?: {
            id: string
            name: string
        }
    }
    createdBy?: {
        id: string
        name: string
    }
    created_at: string
    updated_at: string
}

interface PaymentsIndexProps {
    payments: {
        data: Payment[]
        links: any[]
        meta: any
    }
    filters: {
        search?: string
        status?: string
        invoice_id?: string
    }
    stats: {
        total: number
        completed: number
        pending: number
        total_amount: number
    }
}

const breadcrumbs: BreadcrumbItem[] = [{ title: "Dashboard", href: "/dashboard" }, { title: "Zahlungen" }]

export default function PaymentsIndex() {
    // @ts-ignore
    const { payments, filters, stats } = usePage<PaymentsIndexProps>().props
    const [search, setSearch] = useState(filters.search || "")
    const [status, setStatus] = useState(filters.status || "all")

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault()
        const params: any = {}
        if (search) params.search = search
        if (status && status !== 'all') params.status = status
        router.get("/payments", params, { preserveScroll: true })
    }

    const handleStatusChange = (newStatus: string) => {
        setStatus(newStatus)
        const params: any = {}
        if (search) params.search = search
        if (newStatus && newStatus !== 'all') params.status = newStatus
        router.get("/payments", params, { preserveScroll: true })
    }

    const handleDelete = (payment: Payment) => {
        if (confirm(`Möchten Sie die Zahlung vom ${new Date(payment.payment_date).toLocaleDateString('de-DE')} wirklich löschen?`)) {
            router.delete(`/payments/${payment.id}`)
        }
    }

    const getStatusBadge = (status: string) => {
        const statusConfig = {
            pending: { label: "Ausstehend", variant: "secondary" as const },
            completed: { label: "Abgeschlossen", variant: "default" as const },
            cancelled: { label: "Storniert", variant: "destructive" as const },
        }

        const config = statusConfig[status as keyof typeof statusConfig] || statusConfig.pending
        return <Badge variant={config.variant}>{config.label}</Badge>
    }

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat("de-DE", {
            style: "currency",
            currency: "EUR",
        }).format(amount)
    }

    const formatDate = (date: string) => {
        return new Date(date).toLocaleDateString("de-DE")
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Zahlungen" />

            <div className="flex flex-1 flex-col gap-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-gray-100">Zahlungsverwaltung</h1>
                        <p className="text-gray-600">Verwalten Sie alle Zahlungen für Ihre Rechnungen</p>
                    </div>

                    <Link href="/payments/create">
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            Neue Zahlung
                        </Button>
                    </Link>
                </div>

                {/* Statistics Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Gesamt</CardTitle>
                            <CreditCard className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Abgeschlossen</CardTitle>
                            <CheckCircle className="h-4 w-4 text-green-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">{stats.completed}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Ausstehend</CardTitle>
                            <Clock className="h-4 w-4 text-yellow-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-yellow-600">{stats.pending}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Gesamtbetrag</CardTitle>
                            <CreditCard className="h-4 w-4 text-blue-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600">{formatCurrency(stats.total_amount)}</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle>Zahlungen filtern</CardTitle>
                        <CardDescription>Suchen und filtern Sie Ihre Zahlungen</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSearch} className="flex gap-4">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                                <Input
                                    placeholder="Nach Referenz, Notizen oder Rechnungsnummer suchen..."
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
                                    <SelectItem value="completed">Abgeschlossen</SelectItem>
                                    <SelectItem value="pending">Ausstehend</SelectItem>
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
                                        router.get("/payments")
                                    }}
                                >
                                    Zurücksetzen
                                </Button>
                            )}
                        </form>
                    </CardContent>
                </Card>

                {/* Payments Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Zahlungen</CardTitle>
                        <CardDescription>Alle Zahlungen in Ihrem System</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Zahlungsdatum</TableHead>
                                    <TableHead>Rechnung</TableHead>
                                    <TableHead>Kunde</TableHead>
                                    <TableHead>Betrag</TableHead>
                                    <TableHead>Zahlungsmethode</TableHead>
                                    <TableHead>Referenz</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Aktionen</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {payments.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={8} className="text-center text-gray-500 py-8">
                                            Keine Zahlungen gefunden
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    payments.data.map((payment) => (
                                        <TableRow key={payment.id}>
                                            <TableCell>{formatDate(payment.payment_date)}</TableCell>
                                            <TableCell>
                                                <Link
                                                    href={`/invoices/${payment.invoice_id}`}
                                                    className="text-blue-600 hover:underline"
                                                >
                                                    {payment.invoice?.number || payment.invoice_id}
                                                </Link>
                                            </TableCell>
                                            <TableCell>
                                                {payment.invoice?.customer?.name || "-"}
                                            </TableCell>
                                            <TableCell className="font-medium">
                                                {formatCurrency(payment.amount)}
                                            </TableCell>
                                            <TableCell>{payment.payment_method || "-"}</TableCell>
                                            <TableCell>{payment.reference || "-"}</TableCell>
                                            <TableCell>{getStatusBadge(payment.status)}</TableCell>
                                            <TableCell>
                                                <div className="flex gap-2">
                                                    <Link href={`/payments/${payment.id}`}>
                                                        <Button variant="ghost" size="sm">
                                                            <Eye className="h-4 w-4" />
                                                        </Button>
                                                    </Link>
                                                    <Link href={`/payments/${payment.id}/edit`}>
                                                        <Button variant="ghost" size="sm">
                                                            <Edit className="h-4 w-4" />
                                                        </Button>
                                                    </Link>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleDelete(payment)}
                                                    >
                                                        <Trash2 className="h-4 w-4 text-red-600" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>

                        {/* Pagination */}
                        {payments.links && payments.links.length > 3 && (
                            <div className="flex justify-center gap-2 mt-4">
                                {payments.links.map((link: any, index: number) => (
                                    <Link
                                        key={index}
                                        href={link.url || "#"}
                                        className={`px-3 py-2 rounded ${
                                            link.active
                                                ? "bg-blue-600 text-white"
                                                : "bg-gray-100 text-gray-700 hover:bg-gray-200"
                                        } ${!link.url ? "opacity-50 cursor-not-allowed" : ""}`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    )
}


