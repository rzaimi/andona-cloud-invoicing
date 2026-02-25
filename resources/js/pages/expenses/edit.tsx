"use client"

import type React from "react"
import { Head, Link, useForm, usePage } from "@inertiajs/react"
import { formatCurrency as formatCurrencyUtil } from "@/utils/formatting"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { ArrowLeft } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem } from "@/types"

interface Expense {
    id: string
    category_id?: string
    title: string
    description?: string
    amount: number
    vat_rate: number
    expense_date: string
    payment_method?: string
    reference?: string
    receipt_path?: string
}

interface ExpensesEditProps {
    expense: Expense
    categories: Array<{
        id: string
        name: string
    }>
}

const VAT_OPTIONS = [
    { value: "0.19", label: "19% (Regelsteuersatz)" },
    { value: "0.07", label: "7% (ermäßigter Steuersatz)" },
    { value: "0",    label: "0% (steuerfrei)" },
]

export default function ExpensesEdit() {
    // @ts-ignore
    const { expense, categories } = usePage<ExpensesEditProps>().props
    const pageProps = usePage().props as any
    const settings = pageProps.auth?.user?.company?.settings ?? {}
    const settingsPaymentMethods: string[] = settings.payment_methods ?? []

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Ausgaben", href: "/expenses" },
        { title: "Ausgabe bearbeiten" },
    ]

    // Normalize stored vat_rate to a string matching one of the VAT_OPTIONS values
    const normalizeVatRate = (rate: number): string => {
        const rounded = Math.round(rate * 100) / 100
        if (rounded === 0.19) return "0.19"
        if (rounded === 0.07) return "0.07"
        return "0"
    }

    const { data, setData, post, processing, errors, transform } = useForm({
        category_id: expense.category_id || "none",
        title: expense.title,
        description: expense.description || "",
        amount: expense.amount,
        vat_rate: normalizeVatRate(expense.vat_rate),
        expense_date: expense.expense_date.split("T")[0],
        payment_method: expense.payment_method || "",
        reference: expense.reference || "",
        receipt: null as File | null,
    })

    const paymentMethods = settingsPaymentMethods.length > 0 ? settingsPaymentMethods : [
        "Überweisung",
        "SEPA-Lastschrift",
        "Bar",
        "Kreditkarte",
        "PayPal",
        "Stripe",
        "Andere",
    ]

    const formatCurrency = (amount: number) => formatCurrencyUtil(amount, settings)

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        // Convert "none" to null before submitting
        setData("category_id", data.category_id === "none" ? null : data.category_id)
        // Use POST + method spoofing for reliable file uploads across browsers/servers
        transform((data) => ({ ...data, _method: "PUT" }))
        post(`/expenses/${expense.id}`, {
            forceFormData: true,
            onBefore: () => {
                // Restore "none" if submission fails
                if (data.category_id === null) {
                    setData("category_id", "none")
                }
            },
        })
    }

    // amount is the gross total (including VAT)
    const vatRate = parseFloat(data.vat_rate) || 0
    const vatAmount = Math.round(data.amount * vatRate * 100) / 100
    const netAmount = Math.round((data.amount - vatAmount) * 100) / 100
    const totalAmount = data.amount

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Ausgabe bearbeiten" />

            <div className="flex flex-1 flex-col gap-6">
                <div className="flex items-center gap-4">
                    <Link href="/expenses">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Zurück
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-1xl font-bold text-gray-900">Ausgabe bearbeiten</h1>
                        <p className="text-gray-600">Bearbeiten Sie die Ausgabeninformationen</p>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="flex flex-1 flex-col gap-6">
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Main Form */}
                        <div className="lg:col-span-2 space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Ausgabeninformationen</CardTitle>
                                    <CardDescription>Grundlegende Informationen zur Ausgabe</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="title">Titel *</Label>
                                        <Input
                                            id="title"
                                            value={data.title}
                                            onChange={(e) => setData("title", e.target.value)}
                                            required
                                        />
                                        {errors.title && <p className="text-sm text-red-600">{errors.title}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="category_id">Kategorie</Label>
                                        <Select
                                            value={data.category_id || "none"}
                                            onValueChange={(value) => setData("category_id", value === "none" ? "none" : value)}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Kategorie auswählen (optional)" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="none">Keine Kategorie</SelectItem>
                                                {categories.map((category) => (
                                                    <SelectItem key={category.id} value={category.id}>
                                                        {category.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.category_id && (
                                            <p className="text-sm text-red-600">{errors.category_id}</p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="description">Beschreibung</Label>
                                        <Textarea
                                            id="description"
                                            value={data.description}
                                            onChange={(e) => setData("description", e.target.value)}
                                            rows={4}
                                        />
                                        {errors.description && (
                                            <p className="text-sm text-red-600">{errors.description}</p>
                                        )}
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="amount">Bruttobetrag (inkl. MwSt.) *</Label>
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
                                            <Label htmlFor="vat_rate">MwSt.-Satz *</Label>
                                            <Select
                                                value={data.vat_rate}
                                                onValueChange={(value) => setData("vat_rate", value)}
                                            >
                                                <SelectTrigger id="vat_rate">
                                                    <SelectValue placeholder="MwSt.-Satz wählen" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {VAT_OPTIONS.map((opt) => (
                                                        <SelectItem key={opt.value} value={opt.value}>
                                                            {opt.label}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            {errors.vat_rate && (
                                                <p className="text-sm text-red-600">{errors.vat_rate}</p>
                                            )}
                                        </div>
                                    </div>

                                    <div className="p-4 bg-muted/40 rounded-lg">
                                        <div className="text-sm font-medium mb-2">Berechnung</div>
                                        <div className="text-sm space-y-1">
                                            <div className="flex justify-between">
                                                <span>Netto-Betrag:</span>
                                                <span>{formatCurrency(netAmount)}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>MwSt. ({Math.round(vatRate * 100)}%):</span>
                                                <span>{formatCurrency(vatAmount)}</span>
                                            </div>
                                            <div className="flex justify-between font-semibold pt-2 border-t">
                                                <span>Bruttobetrag:</span>
                                                <span>{formatCurrency(totalAmount)}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="expense_date">Ausgabedatum *</Label>
                                            <Input
                                                id="expense_date"
                                                type="date"
                                                value={data.expense_date}
                                                onChange={(e) => setData("expense_date", e.target.value)}
                                                required
                                            />
                                            {errors.expense_date && (
                                                <p className="text-sm text-red-600">{errors.expense_date}</p>
                                            )}
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
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="reference">Referenz</Label>
                                        <Input
                                            id="reference"
                                            value={data.reference}
                                            onChange={(e) => setData("reference", e.target.value)}
                                        />
                                        {errors.reference && (
                                            <p className="text-sm text-red-600">{errors.reference}</p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="receipt">Beleg hochladen</Label>
                                        {expense.receipt_path && (
                                            <div className="text-sm text-gray-600 mb-2">
                                                Aktueller Beleg vorhanden
                                            </div>
                                        )}
                                        <Input
                                            id="receipt"
                                            type="file"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                            onChange={(e) => {
                                                const file = e.target.files?.[0]
                                                if (file) {
                                                    setData("receipt", file)
                                                }
                                            }}
                                        />
                                        <p className="text-xs text-gray-500">
                                            Erlaubte Formate: PDF, JPG, PNG (max. 10MB)
                                        </p>
                                        {errors.receipt && <p className="text-sm text-red-600">{errors.receipt}</p>}
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
                                        <div className="text-sm text-gray-600">Netto-Betrag</div>
                                        <div className="text-2xl font-bold">{formatCurrency(netAmount)}</div>
                                    </div>
                                    <div>
                                        <div className="text-sm text-gray-600">MwSt.</div>
                                        <div className="font-medium">{formatCurrency(vatAmount)}</div>
                                    </div>
                                    <div className="pt-2 border-t">
                                        <div className="text-sm text-gray-600">Bruttobetrag</div>
                                        <div className="text-xl font-bold">{formatCurrency(totalAmount)}</div>
                                    </div>
                                    <div className="pt-4 border-t">
                                        <Button type="submit" className="w-full" disabled={processing}>
                                            {processing ? "Wird gespeichert..." : "Änderungen speichern"}
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

