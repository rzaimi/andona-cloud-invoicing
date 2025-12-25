"use client"

import type React from "react"
import { Head, Link, router, usePage } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { ArrowLeft, Edit, Trash2, FileText, Download, Send, CreditCard, Plus, CheckCircle, Clock, XCircle, Eye } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem, Invoice, InvoiceItem } from "@/types"
import { route } from "ziggy-js"

interface Payment {
    id: string
    amount: number
    payment_date: string
    payment_method?: string
    reference?: string
    notes?: string
    status: "pending" | "completed" | "cancelled"
    createdBy?: {
        id: string
        name: string
    }
    created_at: string
}

interface InvoicesShowProps {
    invoice: Invoice & {
        items: InvoiceItem[]
        payments?: Payment[]
    }
    settings: any
    paidAmount: number
    remainingBalance: number
}

export default function InvoicesShow() {
    // @ts-ignore
    const { invoice, settings, paidAmount, remainingBalance } = usePage<InvoicesShowProps>().props

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Rechnungen", href: "/invoices" },
        { title: invoice.number },
    ]

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

    const getPaymentStatusBadge = (status: string) => {
        const statusConfig = {
            pending: { label: "Ausstehend", variant: "secondary" as const },
            completed: { label: "Abgeschlossen", variant: "default" as const },
            cancelled: { label: "Storniert", variant: "destructive" as const },
        }

        const config = statusConfig[status as keyof typeof statusConfig] || statusConfig.pending
        return <Badge variant={config.variant}>{config.label}</Badge>
    }

    const formatCurrency = (amount: number | string | null | undefined) => {
        const numAmount = typeof amount === 'string' ? parseFloat(amount) : (amount || 0)
        if (isNaN(numAmount)) return "0,00 €"
        return new Intl.NumberFormat("de-DE", {
            style: "currency",
            currency: "EUR",
        }).format(numAmount)
    }

    const formatDate = (date: string) => {
        return new Date(date).toLocaleDateString("de-DE")
    }

    const handleDelete = () => {
        if (confirm(`Möchten Sie die Rechnung "${invoice.number}" wirklich löschen?`)) {
            router.delete(`/invoices/${invoice.id}`)
        }
    }

    const payments = invoice.payments || []
    const completedPayments = payments.filter(p => p.status === 'completed')

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Rechnung ${invoice.number}`} />

            <div className="flex flex-1 flex-col gap-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href="/invoices">
                            <Button variant="ghost" size="sm">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Zurück
                            </Button>
                        </Link>
                        <div>
                            <div className="flex items-center gap-3">
                                <h1 className="text-3xl font-bold text-gray-900">
                                    {invoice.is_correction ? "Stornorechnung" : "Rechnung"} {invoice.number}
                                </h1>
                                {getStatusBadge(invoice.status)}
                                {invoice.is_correction && (
                                    <Badge variant="destructive">Stornorechnung</Badge>
                                )}
                            </div>
                            <p className="text-gray-600 mt-1">
                                {invoice.customer?.name} • {formatDate(invoice.issue_date)}
                            </p>
                        </div>
                    </div>

                    <div className="flex gap-2">
                        <Link href={`/payments/create?invoice_id=${invoice.id}`}>
                            <Button>
                                <Plus className="mr-2 h-4 w-4" />
                                Zahlung erfassen
                            </Button>
                        </Link>
                        <Link href={`/invoices/${invoice.id}/edit`}>
                            <Button variant="outline">
                                <Edit className="mr-2 h-4 w-4" />
                                Bearbeiten
                            </Button>
                        </Link>
                        <Button
                            variant="outline"
                            onClick={() => window.open(route("invoices.pdf", invoice.id), "_blank")}
                        >
                            <FileText className="mr-2 h-4 w-4" />
                            PDF
                        </Button>
                        <Button variant="destructive" onClick={handleDelete}>
                            <Trash2 className="mr-2 h-4 w-4" />
                            Löschen
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Main Content */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Invoice Details */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Rechnungsdetails</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <div className="text-sm text-gray-600">Rechnungsnummer</div>
                                        <div className="font-medium">{invoice.number}</div>
                                    </div>
                                    <div>
                                        <div className="text-sm text-gray-600">Status</div>
                                        <div className="mt-1">{getStatusBadge(invoice.status)}</div>
                                    </div>
                                    <div>
                                        <div className="text-sm text-gray-600">Rechnungsdatum</div>
                                        <div className="font-medium">{formatDate(invoice.issue_date)}</div>
                                    </div>
                                    <div>
                                        <div className="text-sm text-gray-600">Fälligkeitsdatum</div>
                                        <div className="font-medium">{formatDate(invoice.due_date)}</div>
                                    </div>
                                    <div>
                                        <div className="text-sm text-gray-600">Kunde</div>
                                        <div className="font-medium">{invoice.customer?.name}</div>
                                    </div>
                                    {invoice.customer?.email && (
                                        <div>
                                            <div className="text-sm text-gray-600">E-Mail</div>
                                            <div className="font-medium">{invoice.customer.email}</div>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Invoice Items */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Rechnungsposten</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Beschreibung</TableHead>
                                            <TableHead>Menge</TableHead>
                                            <TableHead>Einzelpreis</TableHead>
                                            <TableHead className="text-right">Gesamt</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {invoice.items?.map((item) => (
                                            <TableRow key={item.id}>
                                                <TableCell>{item.description}</TableCell>
                                                <TableCell>
                                                    {item.quantity} {item.unit}
                                                </TableCell>
                                                <TableCell>{formatCurrency(item.unit_price)}</TableCell>
                                                <TableCell className="text-right font-medium">
                                                    {formatCurrency(item.total)}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>

                                <div className="mt-4 flex justify-end">
                                    <div className="w-64 space-y-2">
                                        <div className="flex justify-between">
                                            <span className="text-gray-600">Zwischensumme (netto)</span>
                                            <span className="font-medium">{formatCurrency(invoice.subtotal)}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="text-gray-600">
                                                {Number(invoice.tax_rate || 0) * 100}% Umsatzsteuer
                                            </span>
                                            <span className="font-medium">{formatCurrency(invoice.tax_amount)}</span>
                                        </div>
                                        {(Number(invoice.reminder_fee) || 0) > 0 && (
                                            <div className="flex justify-between text-orange-600">
                                                <span>Mahngebühr</span>
                                                <span className="font-medium">{formatCurrency(invoice.reminder_fee)}</span>
                                            </div>
                                        )}
                                        <div className="flex justify-between pt-2 border-t text-lg font-bold">
                                            <span>Rechnungsbetrag</span>
                                            <span>{formatCurrency((Number(invoice.total) || 0) + (Number(invoice.reminder_fee) || 0))}</span>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Payment History */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Zahlungshistorie</CardTitle>
                                <CardDescription>Alle Zahlungen für diese Rechnung</CardDescription>
                            </CardHeader>
                            <CardContent>
                                {payments.length === 0 ? (
                                    <div className="text-center text-gray-500 py-8">
                                        Noch keine Zahlungen erfasst
                                    </div>
                                ) : (
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Datum</TableHead>
                                                <TableHead>Betrag</TableHead>
                                                <TableHead>Zahlungsmethode</TableHead>
                                                <TableHead>Referenz</TableHead>
                                                <TableHead>Status</TableHead>
                                                <TableHead>Aktionen</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {payments.map((payment) => (
                                                <TableRow key={payment.id}>
                                                    <TableCell>{formatDate(payment.payment_date)}</TableCell>
                                                    <TableCell className="font-medium">
                                                        {formatCurrency(payment.amount)}
                                                    </TableCell>
                                                    <TableCell>{payment.payment_method || "-"}</TableCell>
                                                    <TableCell>{payment.reference || "-"}</TableCell>
                                                    <TableCell>{getPaymentStatusBadge(payment.status)}</TableCell>
                                                    <TableCell>
                                                        <Link href={`/payments/${payment.id}`}>
                                                            <Button variant="ghost" size="sm">
                                                                <Eye className="h-4 w-4" />
                                                            </Button>
                                                        </Link>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                )}
                            </CardContent>
                        </Card>

                        {invoice.notes && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Notizen</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="whitespace-pre-wrap">{invoice.notes}</p>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Sidebar */}
                    <div className="lg:col-span-1 space-y-6">
                        {/* Payment Summary */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Zahlungsübersicht</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <div className="text-sm text-gray-600">Rechnungsbetrag</div>
                                    <div className="text-2xl font-bold">{formatCurrency((Number(invoice.total) || 0) + (Number(invoice.reminder_fee) || 0))}</div>
                                </div>
                                <div>
                                    <div className="text-sm text-gray-600">Bereits gezahlt</div>
                                    <div className="text-xl font-semibold text-green-600">
                                        {formatCurrency(paidAmount)}
                                    </div>
                                </div>
                                <div className="pt-4 border-t">
                                    <div className="text-sm text-gray-600">Verbleibender Betrag</div>
                                    <div className={`text-xl font-semibold ${remainingBalance > 0 ? 'text-red-600' : 'text-green-600'}`}>
                                        {formatCurrency(remainingBalance)}
                                    </div>
                                </div>
                                {remainingBalance > 0 && (
                                    <Link href={`/payments/create?invoice_id=${invoice.id}`} className="block">
                                        <Button className="w-full">
                                            <Plus className="mr-2 h-4 w-4" />
                                            Zahlung erfassen
                                        </Button>
                                    </Link>
                                )}
                            </CardContent>
                        </Card>

                        {/* Quick Actions */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Schnellaktionen</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                <Button
                                    variant="outline"
                                    className="w-full justify-start"
                                    onClick={() => window.open(route("invoices.pdf", invoice.id), "_blank")}
                                >
                                    <Download className="mr-2 h-4 w-4" />
                                    PDF herunterladen
                                </Button>
                                {invoice.status === "draft" && (
                                    <Button
                                        variant="outline"
                                        className="w-full justify-start"
                                        onClick={() => router.post(route("invoices.send", invoice.id))}
                                    >
                                        <Send className="mr-2 h-4 w-4" />
                                        Rechnung versenden
                                    </Button>
                                )}
                            </CardContent>
                        </Card>

                        {/* Metadata */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Metadaten</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <div className="text-sm text-gray-600">Erstellt am</div>
                                    <div className="font-medium">
                                        {new Date(invoice.created_at).toLocaleDateString("de-DE")}
                                    </div>
                                </div>
                                {invoice.user && (
                                    <div>
                                        <div className="text-sm text-gray-600">Erstellt von</div>
                                        <div className="font-medium">{invoice.user.name}</div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    )
}
