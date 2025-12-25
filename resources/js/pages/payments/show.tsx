"use client"

import type React from "react"
import { Head, Link, router, usePage } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { ArrowLeft, Edit, Trash2, Receipt } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem, Invoice } from "@/types"

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
    invoice?: Invoice & {
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

interface PaymentsShowProps {
    payment: Payment
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Zahlungen", href: "/payments" },
    { title: "Zahlungsdetails" },
]

export default function PaymentsShow() {
    // @ts-ignore
    const { payment } = usePage<PaymentsShowProps>().props

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
        return new Date(date).toLocaleDateString("de-DE", {
            year: "numeric",
            month: "long",
            day: "numeric",
        })
    }

    const formatDateTime = (date: string) => {
        return new Date(date).toLocaleString("de-DE", {
            year: "numeric",
            month: "long",
            day: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        })
    }

    const handleDelete = () => {
        if (confirm("Möchten Sie diese Zahlung wirklich löschen?")) {
            router.delete(`/payments/${payment.id}`)
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Zahlungsdetails" />

            <div className="flex flex-1 flex-col gap-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href="/payments">
                            <Button variant="ghost" size="sm">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Zurück
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">Zahlungsdetails</h1>
                            <p className="text-gray-600">Details zur Zahlung</p>
                        </div>
                    </div>

                    <div className="flex gap-2">
                        <Link href={`/payments/${payment.id}/edit`}>
                            <Button variant="outline">
                                <Edit className="mr-2 h-4 w-4" />
                                Bearbeiten
                            </Button>
                        </Link>
                        <Button variant="destructive" onClick={handleDelete}>
                            <Trash2 className="mr-2 h-4 w-4" />
                            Löschen
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Main Details */}
                    <div className="lg:col-span-2 space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Zahlungsinformationen</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <div className="text-sm text-gray-600">Betrag</div>
                                        <div className="text-2xl font-bold">{formatCurrency(payment.amount)}</div>
                                    </div>
                                    <div>
                                        <div className="text-sm text-gray-600">Status</div>
                                        <div className="mt-1">{getStatusBadge(payment.status)}</div>
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-4 pt-4 border-t">
                                    <div>
                                        <div className="text-sm text-gray-600">Zahlungsdatum</div>
                                        <div className="font-medium">{formatDate(payment.payment_date)}</div>
                                    </div>
                                    {payment.payment_method && (
                                        <div>
                                            <div className="text-sm text-gray-600">Zahlungsmethode</div>
                                            <div className="font-medium">{payment.payment_method}</div>
                                        </div>
                                    )}
                                </div>

                                {payment.reference && (
                                    <div className="pt-4 border-t">
                                        <div className="text-sm text-gray-600">Referenz / Verwendungszweck</div>
                                        <div className="font-medium">{payment.reference}</div>
                                    </div>
                                )}

                                {payment.notes && (
                                    <div className="pt-4 border-t">
                                        <div className="text-sm text-gray-600">Notizen</div>
                                        <div className="mt-1 whitespace-pre-wrap">{payment.notes}</div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {payment.invoice && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Zugehörige Rechnung</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <div className="text-sm text-gray-600">Rechnungsnummer</div>
                                            <div className="font-medium text-lg">{payment.invoice.number}</div>
                                            {payment.invoice.customer && (
                                                <div className="text-sm text-gray-600 mt-1">
                                                    Kunde: {payment.invoice.customer.name}
                                                </div>
                                            )}
                                            <div className="text-sm text-gray-600 mt-1">
                                                Rechnungsbetrag: {formatCurrency(payment.invoice.total)}
                                            </div>
                                        </div>
                                        <Link href={`/invoices/${payment.invoice_id}`}>
                                            <Button variant="outline">
                                                <Receipt className="mr-2 h-4 w-4" />
                                                Rechnung anzeigen
                                            </Button>
                                        </Link>
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Sidebar */}
                    <div className="lg:col-span-1 space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Metadaten</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <div className="text-sm text-gray-600">Erstellt am</div>
                                    <div className="font-medium">{formatDateTime(payment.created_at)}</div>
                                </div>
                                <div>
                                    <div className="text-sm text-gray-600">Zuletzt aktualisiert</div>
                                    <div className="font-medium">{formatDateTime(payment.updated_at)}</div>
                                </div>
                                {payment.createdBy && (
                                    <div>
                                        <div className="text-sm text-gray-600">Erstellt von</div>
                                        <div className="font-medium">{payment.createdBy.name}</div>
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


