"use client"

import type React from "react"
import { useState, useEffect } from "react"
import { Head, Link, useForm, usePage } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { ArrowLeft } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem, Invoice } from "@/types"

interface PaymentCreateProps {
    invoices: Invoice[]
    selectedInvoice?: Invoice & {
        payments?: Array<{
            id: string
            amount: number
            payment_date: string
            status: string
        }>
    }
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Zahlungen", href: "/payments" },
    { title: "Neue Zahlung" },
]

export default function PaymentsCreate() {
    const { invoices, selectedInvoice } = usePage<PaymentCreateProps>().props

    const { data, setData, post, processing, errors } = useForm({
        invoice_id: selectedInvoice?.id || "",
        amount: selectedInvoice ? (selectedInvoice.total - (selectedInvoice.payments?.reduce((sum, p) => sum + (p.status === 'completed' ? p.amount : 0), 0) || 0)) : 0,
        payment_date: new Date().toISOString().split("T")[0],
        payment_method: "",
        reference: "",
        notes: "",
        status: "completed",
    })

    const selectedInvoiceData = invoices.find((inv) => inv.id === data.invoice_id)

    const paymentMethods = [
        "Überweisung",
        "SEPA-Lastschrift",
        "Bar",
        "Kreditkarte",
        "PayPal",
        "Stripe",
        "Andere",
    ]

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat("de-DE", {
            style: "currency",
            currency: "EUR",
        }).format(amount)
    }

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        post("/payments")
    }

    const calculateRemainingBalance = () => {
        if (!selectedInvoiceData) return 0
        const totalPaid = selectedInvoiceData.payments?.reduce(
            (sum, p) => sum + (p.status === 'completed' ? p.amount : 0),
            0
        ) || 0
        return selectedInvoiceData.total - totalPaid
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Neue Zahlung" />

            <div className="flex flex-1 flex-col gap-6">
                <div className="flex items-center gap-4">
                    <Link href="/payments">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Zurück
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Neue Zahlung</h1>
                        <p className="text-gray-600">Erfassen Sie eine neue Zahlung für eine Rechnung</p>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="flex flex-1 flex-col gap-6">
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Main Form */}
                        <div className="lg:col-span-2 space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Zahlungsinformationen</CardTitle>
                                    <CardDescription>Grundlegende Informationen zur Zahlung</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="invoice_id">Rechnung *</Label>
                                        <Select
                                            value={data.invoice_id}
                                            onValueChange={(value) => {
                                                setData("invoice_id", value)
                                                const invoice = invoices.find((inv) => inv.id === value)
                                                if (invoice) {
                                                    const totalPaid = invoice.payments?.reduce(
                                                        (sum, p) => sum + (p.status === 'completed' ? p.amount : 0),
                                                        0
                                                    ) || 0
                                                    setData("amount", invoice.total - totalPaid)
                                                }
                                            }}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Rechnung auswählen" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {invoices.map((invoice) => (
                                                    <SelectItem key={invoice.id} value={invoice.id}>
                                                        {invoice.number} - {invoice.customer?.name || "Unbekannt"} - {formatCurrency(invoice.total)}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.invoice_id && (
                                            <p className="text-sm text-red-600">{errors.invoice_id}</p>
                                        )}
                                    </div>

                                    {selectedInvoiceData && (
                                        <div className="p-4 bg-blue-50 rounded-lg">
                                            <div className="text-sm font-medium text-blue-900 mb-2">Rechnungsdetails</div>
                                            <div className="text-sm text-blue-800 space-y-1">
                                                <div>Rechnungsbetrag: {formatCurrency(selectedInvoiceData.total)}</div>
                                                <div>
                                                    Bereits gezahlt:{" "}
                                                    {formatCurrency(
                                                        selectedInvoiceData.payments?.reduce(
                                                            (sum, p) => sum + (p.status === 'completed' ? p.amount : 0),
                                                            0
                                                        ) || 0
                                                    )}
                                                </div>
                                                <div className="font-semibold">
                                                    Verbleibender Betrag: {formatCurrency(calculateRemainingBalance())}
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="amount">Betrag (€) *</Label>
                                            <Input
                                                id="amount"
                                                type="number"
                                                step="0.01"
                                                min="0.01"
                                                value={data.amount}
                                                onChange={(e) => setData("amount", parseFloat(e.target.value) || 0)}
                                                required
                                            />
                                            {errors.amount && (
                                                <p className="text-sm text-red-600">{errors.amount}</p>
                                            )}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="payment_date">Zahlungsdatum *</Label>
                                            <Input
                                                id="payment_date"
                                                type="date"
                                                value={data.payment_date}
                                                onChange={(e) => setData("payment_date", e.target.value)}
                                                required
                                            />
                                            {errors.payment_date && (
                                                <p className="text-sm text-red-600">{errors.payment_date}</p>
                                            )}
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="payment_method">Zahlungsmethode</Label>
                                        <Select
                                            value={data.payment_method}
                                            onValueChange={(value) => setData("payment_method", value)}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Zahlungsmethode auswählen" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {paymentMethods.map((method) => (
                                                    <SelectItem key={method} value={method}>
                                                        {method}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.payment_method && (
                                            <p className="text-sm text-red-600">{errors.payment_method}</p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="reference">Referenz / Verwendungszweck</Label>
                                        <Input
                                            id="reference"
                                            value={data.reference}
                                            onChange={(e) => setData("reference", e.target.value)}
                                            placeholder="z.B. Rechnungsnummer oder Überweisungsreferenz"
                                        />
                                        {errors.reference && (
                                            <p className="text-sm text-red-600">{errors.reference}</p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="status">Status</Label>
                                        <Select
                                            value={data.status}
                                            onValueChange={(value) => setData("status", value as "pending" | "completed" | "cancelled")}
                                        >
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="completed">Abgeschlossen</SelectItem>
                                                <SelectItem value="pending">Ausstehend</SelectItem>
                                                <SelectItem value="cancelled">Storniert</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        {errors.status && (
                                            <p className="text-sm text-red-600">{errors.status}</p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="notes">Notizen</Label>
                                        <Textarea
                                            id="notes"
                                            value={data.notes || ""}
                                            onChange={(e) => setData("notes", e.target.value)}
                                            placeholder="Zusätzliche Informationen zur Zahlung..."
                                            rows={4}
                                        />
                                        {errors.notes && (
                                            <p className="text-sm text-red-600">{errors.notes}</p>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        {/* Summary */}
                        <div className="lg:col-span-1">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Zusammenfassung</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div>
                                        <div className="text-sm text-gray-600">Zahlungsbetrag</div>
                                        <div className="text-2xl font-bold">{formatCurrency(data.amount)}</div>
                                    </div>
                                    <div>
                                        <div className="text-sm text-gray-600">Zahlungsdatum</div>
                                        <div className="font-medium">
                                            {new Date(data.payment_date).toLocaleDateString("de-DE")}
                                        </div>
                                    </div>
                                    {data.payment_method && (
                                        <div>
                                            <div className="text-sm text-gray-600">Zahlungsmethode</div>
                                            <div className="font-medium">{data.payment_method}</div>
                                        </div>
                                    )}
                                    <div className="pt-4 border-t">
                                        <Button type="submit" className="w-full" disabled={processing}>
                                            {processing ? "Wird gespeichert..." : "Zahlung speichern"}
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </form>
            </div>
        </AppLayout>
    )
}




