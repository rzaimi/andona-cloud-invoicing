"use client"

import type React from "react"
import { Head, Link, useForm, usePage } from "@inertiajs/react"
import { useTranslation } from "react-i18next"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { ArrowLeft } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem, Invoice } from "@/types"

interface Payment {
    id: string
    invoice_id: string
    amount: number
    payment_date: string
    payment_method?: string
    reference?: string
    notes?: string
    status: "pending" | "completed" | "cancelled"
    invoice?: Invoice
}

interface PaymentsEditProps {
    payment: Payment
    invoices: Invoice[]
}

export default function PaymentsEdit() {
    const { t } = useTranslation()
    // @ts-ignore
    const { payment, invoices } = usePage<PaymentsEditProps>().props
    // @ts-ignore
    const settingsPaymentMethods: string[] = (usePage().props.auth as any)?.user?.company?.settings?.payment_methods ?? []

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Zahlungen", href: "/payments" },
        { title: `Zahlung bearbeiten` },
    ]

    const { data, setData, put, processing, errors } = useForm({
        invoice_id: payment.invoice_id,
        amount: typeof payment.amount === "number" ? payment.amount : parseFloat(String(payment.amount ?? "0")) || 0,
        payment_date: payment.payment_date.split("T")[0],
        payment_method: payment.payment_method || "",
        reference: payment.reference || "",
        notes: payment.notes || "",
        status: payment.status,
    })

    const paymentMethods = settingsPaymentMethods.length > 0 ? settingsPaymentMethods : [
        t('pages.payments.bankTransfer'),
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
        put(`/payments/${payment.id}`)
    }

    const selectedInvoiceData = invoices.find((inv) => inv.id === data.invoice_id)

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Zahlung bearbeiten" />

            <div className="flex flex-1 flex-col gap-6">
                <div className="flex items-center gap-4">
                    <Link href="/payments">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            {t('common.back')}
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-1xl font-bold text-gray-900">{t('pages.payments.edit')}</h1>
                        <p className="text-gray-600">{t('pages.payments.editDesc')}</p>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="flex flex-1 flex-col gap-6">
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Main Form */}
                        <div className="lg:col-span-2 space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle>{t('pages.payments.info')}</CardTitle>
                                    <CardDescription>{t('pages.payments.infoDesc')}</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="invoice_id">Rechnung *</Label>
                                        <Select
                                            value={data.invoice_id}
                                            onValueChange={(value) => setData("invoice_id", value)}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder={t('pages.payments.selectInvoice')} />
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
                                            <div className="text-sm font-medium text-blue-900 mb-2">{t('pages.invoices.details')}</div>
                                            <div className="text-sm text-blue-800">
                                                <div>Rechnungsbetrag: {formatCurrency(selectedInvoiceData.total)}</div>
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
                                        <Label htmlFor="payment_method">{t('pages.payments.method')}</Label>
                                        <Select
                                            value={data.payment_method}
                                            onValueChange={(value) => setData("payment_method", value)}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder={t('pages.payments.selectPaymentMethod')} />
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
                                        <Label htmlFor="reference">{t('pages.payments.referenceLabel')}</Label>
                                        <Input
                                            id="reference"
                                            value={data.reference}
                                            onChange={(e) => setData("reference", e.target.value)}
                                            placeholder={t('pages.expenses.referencePlaceholder')}
                                        />
                                        {errors.reference && (
                                            <p className="text-sm text-red-600">{errors.reference}</p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="status">{t('common.status')}</Label>
                                        <Select
                                            value={data.status}
                                            onValueChange={(value) => setData("status", value as "pending" | "completed" | "cancelled")}
                                        >
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="completed">{t('common.completed')}</SelectItem>
                                                <SelectItem value="pending">{t('common.pending')}</SelectItem>
                                                <SelectItem value="cancelled">{t('common.cancelled')}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        {errors.status && (
                                            <p className="text-sm text-red-600">{errors.status}</p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="notes">{t('common.notes')}</Label>
                                        <Textarea
                                            id="notes"
                                            value={data.notes || ""}
                                            onChange={(e) => setData("notes", e.target.value)}
                                            placeholder={t('pages.payments.notesPlaceholder')}
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
                                        <div className="text-sm text-gray-600">{t('pages.payments.paymentAmount')}</div>
                                        <div className="text-2xl font-bold">{formatCurrency(data.amount)}</div>
                                    </div>
                                    <div>
                                        <div className="text-sm text-gray-600">{t('pages.payments.date')}</div>
                                        <div className="font-medium">
                                            {new Date(data.payment_date).toLocaleDateString("de-DE")}
                                        </div>
                                    </div>
                                    {data.payment_method && (
                                        <div>
                                            <div className="text-sm text-gray-600">{t('pages.payments.method')}</div>
                                            <div className="font-medium">{data.payment_method}</div>
                                        </div>
                                    )}
                                    <div className="pt-4 border-t">
                                        <Button type="submit" className="w-full" disabled={processing}>
                                            {processing ? t('common.saving') : t('pages.invoices.saveChanges')}
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




