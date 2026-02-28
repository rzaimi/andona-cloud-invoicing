"use client"

import type React from "react"
import { useState, useEffect } from "react"
import { Head, Link, useForm, usePage } from "@inertiajs/react"
import { useTranslation } from "react-i18next"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { ArrowLeft, Plus, Trash2, PackagePlus, Hash } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem, Customer } from "@/types"
import { ProductSelectorDialog } from "@/components/product-selector-dialog"

interface InvoiceItem {
    id: number
    product_id?: string
    product_sku?: string
    product_number?: string
    description: string
    quantity: number
    unit_price: number
    unit: string
    tax_rate: number
    total: number
    discount_type?: "percentage" | "fixed" | null
    discount_value?: number | null
    discount_amount?: number
}

// Tax rates are built dynamically from company settings (see buildTaxRates helper below)

interface Product {
    id: string
    name: string
    description?: string
    price: number
    unit: string
    tax_rate: number
    sku?: string
    number?: string
}

interface InvoicesCreateProps {
    customers: Customer[]
    layouts: any[]
    products: Product[]
    nextNumber: string
    settings: {
        currency: string
        tax_rate: number
        reduced_tax_rate?: number
        invoice_prefix: string
        decimal_separator: string
        thousands_separator: string
        payment_terms: number
    }
}

function buildTaxRates(taxRate: number, reducedTaxRate: number | undefined, t: (key: string) => string) {
    const standard = Math.round(taxRate * 100)
    const reduced  = Math.round((reducedTaxRate ?? 0.07) * 100)
    const rates: { value: number; label: string }[] = [
        { value: taxRate, label: `${standard}% (${t('pages.invoices.standardRate')})` },
    ]
    if (reduced !== standard) {
        rates.push({ value: reducedTaxRate ?? 0.07, label: `${reduced}% (${t('pages.invoices.reducedRate')})` })
    }
    rates.push({ value: 0.00, label: `0% (${t('pages.invoices.taxFree')})` })
    return rates
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Rechnungen", href: "/invoices" },
    { title: "Neue Rechnung" },
]

export default function InvoicesCreate() {
    const { t } = useTranslation()
    const { customers, layouts, products, settings, nextNumber } = usePage().props as unknown as InvoicesCreateProps
    const germanTaxRates = buildTaxRates(settings.tax_rate ?? 0.19, settings.reduced_tax_rate, t)

    const { data, setData, post, processing, errors } = useForm<Record<string, any>>({
        customer_id: "",
        issue_date: new Date().toISOString().split("T")[0],
        service_date: "",
        service_period_start: "",
        service_period_end: "",
        due_date: new Date(Date.now() + settings.payment_terms * 24 * 60 * 60 * 1000).toISOString().split("T")[0],
        notes: "",
        bauvorhaben: "",
        layout_id: layouts.find((l) => l.is_default)?.id || "",
        vat_regime: "standard",
        invoice_type: "standard",
        sequence_number: "",
        skonto_percent: "",
        skonto_days: "",
        items: [] as InvoiceItem[],
    })

    const [totals, setTotals] = useState({
        subtotal: 0,
        total_discount: 0,
        tax_amount: 0,
        total: 0,
    })

    // Skonto live preview (client-side calculation — backend is source of truth on save)
    const skontoAmount = data.skonto_percent && totals.total
        ? Math.round(totals.total * (Number(data.skonto_percent) / 100) * 100) / 100
        : null
    const skontoDate = data.skonto_days && data.issue_date
        ? (() => {
            const d = new Date(data.issue_date)
            d.setDate(d.getDate() + Number(data.skonto_days))
            return d.toLocaleDateString("de-DE")
          })()
        : null

    const germanUnits = ["Stk.", "Std.", "Tag", "Monat", "Jahr", "m", "m²", "m³", "kg", "l", "Paket"]
    const formErrors = errors as Record<string, string>

    // Calculate totals whenever items change
    useEffect(() => {
        // Calculate each item's total with discount
        const itemsWithTotals = (data.items as InvoiceItem[]).map((item: InvoiceItem) => {
            const baseTotal = item.quantity * item.unit_price
            let discountAmount = 0
            if (item.discount_type && item.discount_value !== null) {
                const dv = typeof item.discount_value === "number" ? item.discount_value : 0
                if (item.discount_type === 'percentage') {
                    discountAmount = baseTotal * (dv / 100)
                } else {
                    discountAmount = Math.min(dv, baseTotal)
                }
            }
            return {
                ...item,
                discount_amount: discountAmount,
                total: baseTotal - discountAmount,
            }
        })
        
        const subtotal = itemsWithTotals.reduce((sum: number, item: InvoiceItem) => sum + item.total, 0)
        const totalDiscount = itemsWithTotals.reduce((sum: number, item: InvoiceItem) => sum + (item.discount_amount || 0), 0)
        
        // Calculate tax based on VAT regime (item-level tax rates)
        let tax_amount = 0
        if (data.vat_regime === 'standard') {
            tax_amount = itemsWithTotals.reduce((sum: number, item: InvoiceItem) => {
                const taxRate = typeof item.tax_rate === 'number' ? item.tax_rate : 0
                return sum + (item.total * taxRate)
            }, 0)
        } else {
            // All other regimes (small_business, reverse_charge, reverse_charge_domestic, intra_community, export) are tax-exempt or handled by buyer
            tax_amount = 0
        }
        
        const total = subtotal + tax_amount

        setTotals({ 
            subtotal, 
            total_discount: totalDiscount,
            tax_amount, 
            total 
        })
    }, [data.items, data.vat_regime])

    const addItem = () => {
        const newItem: InvoiceItem = {
            id: Date.now(),
            product_id: undefined,
            product_sku: undefined,
            product_number: undefined,
            description: "",
            quantity: 1,
            unit_price: 0,
            unit: "Stk.",
            tax_rate: settings.tax_rate || 0.19,
            total: 0,
            discount_type: null,
            discount_value: null,
            discount_amount: 0,
        }
        setData("items", [...(data.items as InvoiceItem[]), newItem])
    }

    const removeItem = (id: number) => {
        setData(
            "items",
            (data.items as InvoiceItem[]).filter((item: InvoiceItem) => item.id !== id),
        )
    }

    const updateItem = (id: number, field: keyof InvoiceItem, value: string | number | null) => {
        const updatedItems = (data.items as InvoiceItem[]).map((item: InvoiceItem) => {
            if (item.id === id) {
                const updatedItem = { ...item, [field]: value }
                // Recalculate total when quantity, unit_price, or discount changes
                if (field === "quantity" || field === "unit_price" || field === "discount_type" || field === "discount_value") {
                    const baseTotal = Number(updatedItem.quantity) * Number(updatedItem.unit_price)
                    let discountAmount = 0
                    if (updatedItem.discount_type && updatedItem.discount_value !== null) {
                        const dv = typeof updatedItem.discount_value === "number" ? updatedItem.discount_value : 0
                        if (updatedItem.discount_type === 'percentage') {
                            discountAmount = baseTotal * (dv / 100)
                        } else {
                            discountAmount = Math.min(dv, baseTotal)
                        }
                    }
                    updatedItem.discount_amount = discountAmount
                    updatedItem.total = baseTotal - discountAmount
                }
                return updatedItem
            }
            return item
        })
        setData("items", updatedItems)
    }

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        post("/invoices")
    }

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat("de-DE", {
            style: "currency",
            currency: settings.currency || "EUR",
        }).format(amount)
    }

    const formatNumber = (value: number) => {
        return value.toLocaleString("de-DE", { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('pages.invoices.new')} />

            <div className="flex flex-1 flex-col gap-6">
                {/* Header with Action Buttons */}
                <div className="sticky top-0 z-10 bg-white border-b pb-4">
                    <div className="flex items-center gap-4 mb-4">
                        <Link href="/invoices">
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                {t('common.back')}
                            </Button>
                        </Link>
                        <div className="flex-1">
                            <div className="flex items-center gap-3">
                                <h1 className="text-1xl font-bold text-gray-900">{t('pages.invoices.new')}</h1>
                                {nextNumber && (
                                    <span className="inline-flex items-center gap-1 rounded-md border border-dashed border-blue-300 bg-blue-50 px-2.5 py-0.5 text-xs font-mono font-medium text-blue-700" title="Voraussichtliche Rechnungsnummer">
                                        <Hash className="h-3 w-3" />
                                        {nextNumber}
                                    </span>
                                )}
                            </div>
                            <p className="text-gray-600">{t('pages.invoices.createDesc')}</p>
                        </div>
                    </div>
                    
                    {/* Action Buttons - Top of Form */}
                    <div className="flex justify-end gap-2">
                        <Link href="/invoices">
                            <Button type="button" variant="outline">
                                {t('common.cancel')}
                            </Button>
                        </Link>
                        <Button 
                            type="submit" 
                            disabled={processing}
                            onClick={handleSubmit}
                        >
                            {processing ? "Wird erstellt..." : "Rechnung erstellen"}
                        </Button>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Basic Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('pages.invoices.infoTitle')}</CardTitle>
                            <CardDescription>{t('pages.invoices.infoDesc')}</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="customer_id">{t('pages.invoices.customer')} *</Label>
                                    <Select value={data.customer_id} onValueChange={(value) => setData("customer_id", value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('pages.invoices.selectCustomer')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {customers.map((customer) => (
                                                <SelectItem key={customer.id} value={customer.id.toString()}>
                                                    {customer.name} ({customer.email})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.customer_id && <p className="text-red-600 text-sm">{errors.customer_id}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="layout_id">Layout</Label>
                                    <Select value={data.layout_id} onValueChange={(value) => setData("layout_id", value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select layout" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {layouts.map((layout) => (
                                                <SelectItem key={layout.id} value={layout.id.toString()}>
                                                    {layout.name} {layout.is_default && "(Standard)"}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.layout_id && <p className="text-red-600 text-sm">{errors.layout_id}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="issue_date">{t('pages.invoices.issueDate')} *</Label>
                                    <Input
                                        id="issue_date"
                                        type="date"
                                        value={data.issue_date}
                                        onChange={(e) => setData("issue_date", e.target.value)}
                                        required
                                    />
                                    {errors.issue_date && <p className="text-red-600 text-sm">{errors.issue_date}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="due_date">{t('pages.invoices.dueDate')} *</Label>
                                    <Input
                                        id="due_date"
                                        type="date"
                                        value={data.due_date}
                                        onChange={(e) => setData("due_date", e.target.value)}
                                        required
                                    />
                                    {errors.due_date && <p className="text-red-600 text-sm">{errors.due_date}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="bauvorhaben">BV (Bauvorhaben)</Label>
                                    <Input
                                        id="bauvorhaben"
                                        type="text"
                                        value={data.bauvorhaben}
                                        onChange={(e) => setData("bauvorhaben", e.target.value)}
                                        placeholder={t('pages.invoices.projectPlaceholder')}
                                    />
                                    {errors.bauvorhaben && <p className="text-red-600 text-sm">{errors.bauvorhaben}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="vat_regime">{t('pages.invoices.vatRegime')} *</Label>
                                    <Select value={data.vat_regime} onValueChange={(value) => setData("vat_regime", value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('pages.invoices.selectRegulation')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="standard">Regelbesteuerung (19% / 7%)</SelectItem>
                                            <SelectItem value="small_business">Kleinunternehmerregelung (§ 19 UStG)</SelectItem>
                                            <SelectItem value="reverse_charge">Reverse Charge – Ausland (§ 13b UStG)</SelectItem>
                                            <SelectItem value="reverse_charge_domestic">{t('pages.invoices.reverseChargeDomestic')}</SelectItem>
                                            <SelectItem value="intra_community">Innergemeinschaftliche Lieferung (§ 4 Nr. 1b UStG)</SelectItem>
                                            <SelectItem value="export">Ausfuhrlieferung (§ 4 Nr. 1a UStG)</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.vat_regime && <p className="text-red-600 text-sm">{errors.vat_regime}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="service_date">{t('pages.invoices.serviceDate')}</Label>
                                    <Input
                                        id="service_date"
                                        type="date"
                                        value={data.service_date}
                                        onChange={(e) => {
                                            setData((prev: any) => ({
                                                ...prev,
                                                service_date: e.target.value,
                                                service_period_start: "",
                                                service_period_end: ""
                                            }))
                                        }}
                                    />
                                    {errors.service_date && <p className="text-red-600 text-sm">{errors.service_date}</p>}
                                </div>

                                <div className="grid grid-cols-2 gap-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="service_period_start">{t('pages.invoices.servicePeriodFrom')}</Label>
                                        <Input
                                            id="service_period_start"
                                            type="date"
                                            value={data.service_period_start}
                                            onChange={(e) => {
                                                setData((prev: any) => ({
                                                    ...prev,
                                                    service_period_start: e.target.value,
                                                    service_date: ""
                                                }))
                                            }}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="service_period_end">bis</Label>
                                        <Input
                                            id="service_period_end"
                                            type="date"
                                            value={data.service_period_end}
                                            onChange={(e) => {
                                                setData((prev: any) => ({
                                                    ...prev,
                                                    service_period_end: e.target.value,
                                                    service_date: ""
                                                }))
                                            }}
                                        />
                                    </div>
                                    {(errors.service_period_start || errors.service_period_end) && (
                                        <p className="text-red-600 text-sm col-span-2">
                                            {errors.service_period_start || errors.service_period_end}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Rechnungstyp & Skonto */}
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('pages.invoices.invoiceType')}</CardTitle>
                            <CardDescription>{t('pages.invoices.invoiceTypeDesc')}</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {/* Rechnungstyp */}
                                <div className="space-y-2">
                                    <Label htmlFor="invoice_type">{t('pages.invoices.invoiceType')}</Label>
                                    <Select
                                        value={data.invoice_type || "standard"}
                                        onValueChange={(v) => {
                                            setData((prev: any) => ({
                                                ...prev,
                                                invoice_type: v,
                                                sequence_number: v !== "abschlagsrechnung" ? "" : prev.sequence_number,
                                            }))
                                        }}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="standard">{t('nav.invoices')}</SelectItem>
                                            <SelectItem value="abschlagsrechnung">Abschlagsrechnung</SelectItem>
                                            <SelectItem value="schlussrechnung">Schlussrechnung</SelectItem>
                                            <SelectItem value="nachtragsrechnung">Nachtragsrechnung</SelectItem>
                                            <SelectItem value="korrekturrechnung">Korrekturrechnung</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.invoice_type && <p className="text-red-600 text-sm">{errors.invoice_type}</p>}
                                </div>

                                {/* Sequence Number – only for Abschlagsrechnung */}
                                {data.invoice_type === "abschlagsrechnung" && (
                                    <div className="space-y-2">
                                        <Label htmlFor="sequence_number">Abschlagsnummer (1–20) *</Label>
                                        <Select
                                            value={data.sequence_number?.toString() || ""}
                                            onValueChange={(v) => setData("sequence_number", v)}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder={t('pages.invoices.selectDiscount')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {Array.from({ length: 20 }, (_, i) => i + 1).map((n) => (
                                                    <SelectItem key={n} value={n.toString()}>
                                                        Abschlag {n}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.sequence_number && <p className="text-red-600 text-sm">{errors.sequence_number}</p>}
                                    </div>
                                )}
                            </div>

                            {/* Skonto */}
                            <div className="border rounded-lg p-4 space-y-3 bg-muted/30">
                                <div>
                                    <p className="font-medium text-sm">{t('pages.invoices.skonto')}</p>
                                    <p className="text-xs text-muted-foreground">{t('pages.invoices.skontoDesc')}</p>
                                </div>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="skonto_percent">{t('pages.invoices.skontoPercent')}</Label>
                                        <Select
                                            value={data.skonto_percent?.toString() || "none"}
                                            onValueChange={(v) => setData("skonto_percent", v === "none" ? "" : v)}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Kein Skonto" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="none">Kein Skonto</SelectItem>
                                                <SelectItem value="2">2 %</SelectItem>
                                                <SelectItem value="3">3 %</SelectItem>
                                                <SelectItem value="4">4 %</SelectItem>
                                                <SelectItem value="5">5 %</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        {errors.skonto_percent && <p className="text-red-600 text-sm">{errors.skonto_percent}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="skonto_days">{t('pages.invoices.skontoDays')}</Label>
                                        <Select
                                            value={data.skonto_days?.toString() || "none"}
                                            onValueChange={(v) => setData("skonto_days", v === "none" ? "" : v)}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder={t('pages.invoices.selectDeadline')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="none">Keine Frist</SelectItem>
                                                <SelectItem value="7">7 Tage</SelectItem>
                                                <SelectItem value="10">10 Tage</SelectItem>
                                                <SelectItem value="14">14 Tage</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        {errors.skonto_days && <p className="text-red-600 text-sm">{errors.skonto_days}</p>}
                                    </div>
                                </div>
                                {/* Live preview */}
                                {skontoAmount !== null && skontoDate && (
                                    <div className="rounded bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-800 flex gap-6">
                                        <span>
                                            <span className="font-medium">Skonto-Betrag: </span>
                                            {formatCurrency(skontoAmount)}
                                        </span>
                                        <span>
                                            <span className="font-medium">Skonto bis: </span>
                                            {skontoDate}
                                        </span>
                                        <span>
                                            <span className="font-medium">Bei Skonto zahlen: </span>
                                            {formatCurrency(totals.total - skontoAmount)}
                                        </span>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Invoice Items */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <div>
                                <CardTitle>{t('pages.invoices.items')}</CardTitle>
                                <CardDescription>{t('pages.invoices.addItems')}</CardDescription>
                            </div>
                            <ProductSelectorDialog 
                                products={products} 
                                onSelect={(item) => {
                                    // Check if product already exists in items
                                    const existingItemIndex = (data.items as InvoiceItem[]).findIndex(
                                        (i) => i.product_id && i.product_id === item.product_id
                                    )
                                    
                                    if (existingItemIndex !== -1) {
                                        // Product exists, increase quantity
                                        const updatedItems = [...(data.items as InvoiceItem[])]
                                        const existingItem = updatedItems[existingItemIndex]
                                        updatedItems[existingItemIndex] = {
                                            ...existingItem,
                                            quantity: existingItem.quantity + item.quantity,
                                            total: (existingItem.quantity + item.quantity) * existingItem.unit_price,
                                        }
                                        setData("items", updatedItems)
                                    } else {
                                        // New product, add to list
                                        const newItem: InvoiceItem = {
                                            id: Date.now(),
                                            product_id: item.product_id,
                                            product_sku: item.product_sku,
                                            product_number: item.product_number,
                                            description: item.description,
                                            quantity: item.quantity,
                                            unit_price: item.unit_price,
                                            unit: item.unit,
                                            tax_rate: item.tax_rate || settings.tax_rate || 0.19,
                                            total: item.quantity * item.unit_price,
                                            discount_type: null,
                                            discount_value: null,
                                            discount_amount: 0,
                                        }
                                        setData("items", [...(data.items as InvoiceItem[]), newItem])
                                    }
                                }}
                            />
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-[12%]">Produkt-Nr.</TableHead>
                                            <TableHead className="w-[26%]">{t('common.description')}</TableHead>
                                            <TableHead className="w-[8%]">{t('common.quantity')}</TableHead>
                                            <TableHead className="w-[8%]">Einheit</TableHead>
                                            <TableHead className="w-[6%]">USt.</TableHead>
                                            <TableHead className="w-[12%]">Einzelpreis</TableHead>
                                            <TableHead className="w-[10%]">Rabatt</TableHead>
                                            <TableHead className="w-[10%]">Rabatt-Wert</TableHead>
                                            <TableHead className="w-[12%]">Gesamtpreis</TableHead>
                                            <TableHead className="w-[10%]">{t('common.actions')}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {(data.items as InvoiceItem[]).length === 0 ? (
                                            <TableRow>
                                                <TableCell colSpan={10} className="py-12 text-center">
                                                    <div className="flex flex-col items-center gap-3 text-muted-foreground">
                                                        <PackagePlus className="h-10 w-10 opacity-30" />
                                                        <p className="text-sm font-medium">{t('pages.invoices.noItems')}</p>
                                                        <p className="text-xs">{t('pages.invoices.noItemsHint')}</p>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ) : (data.items as InvoiceItem[]).map((item: InvoiceItem, index: number) => (
                                            <TableRow key={item.id}>
                                                <TableCell className="align-top">
                                                    <div className="text-sm">
                                                        {item.product_number || item.product_sku || (
                                                            <span className="text-muted-foreground">-</span>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <Textarea
                                                        value={item.description}
                                                        onChange={(e) => updateItem(item.id, "description", e.target.value)}
                                                        placeholder="Beschreibung der Leistung..."
                                                        className="min-h-[60px]"
                                                        required
                                                    />
                                                    {formErrors[`items.${index}.description`] && (
                                                        <p className="text-red-600 text-sm mt-1">{formErrors[`items.${index}.description`]}</p>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <Input
                                                        type="number"
                                                        min="0.01"
                                                        step="0.01"
                                                        value={item.quantity}
                                                        onChange={(e) => updateItem(item.id, "quantity", Number.parseFloat(e.target.value) || 0)}
                                                        required
                                                    />
                                                    {formErrors[`items.${index}.quantity`] && (
                                                        <p className="text-red-600 text-sm mt-1">{formErrors[`items.${index}.quantity`]}</p>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <Select value={item.unit} onValueChange={(value) => updateItem(item.id, "unit", value)}>
                                                        <SelectTrigger>
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {germanUnits.map((unit) => (
                                                                <SelectItem key={unit} value={unit}>
                                                                    {unit}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                </TableCell>
                                                <TableCell>
                                                    <Select 
                                                        value={(item.tax_rate ?? settings.tax_rate ?? 0.19).toString()} 
                                                        onValueChange={(value) => updateItem(item.id, "tax_rate", Number.parseFloat(value))}
                                                        disabled={data.vat_regime !== 'standard'}
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {germanTaxRates.map((rate) => (
                                                                <SelectItem key={rate.value} value={rate.value.toString()}>
                                                                    {rate.label}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                </TableCell>
                                                <TableCell>
                                                    <Input
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        value={item.unit_price}
                                                        onChange={(e) => updateItem(item.id, "unit_price", Number.parseFloat(e.target.value) || 0)}
                                                        required
                                                    />
                                                    {formErrors[`items.${index}.unit_price`] && (
                                                        <p className="text-red-600 text-sm mt-1">{formErrors[`items.${index}.unit_price`]}</p>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <Select 
                                                        value={item.discount_type || "none"} 
                                                        onValueChange={(value) => {
                                                            if (value === "none") {
                                                                updateItem(item.id, "discount_type", null)
                                                                updateItem(item.id, "discount_value", null)
                                                            } else {
                                                                updateItem(item.id, "discount_type", value as "percentage" | "fixed")
                                                            }
                                                        }}
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="Kein Rabatt" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="none">Kein Rabatt</SelectItem>
                                                            <SelectItem value="percentage">%</SelectItem>
                                                            <SelectItem value="fixed">€</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </TableCell>
                                                <TableCell>
                                                    {item.discount_type && (
                                                        <Input
                                                            type="number"
                                                            min="0"
                                                            step="0.01"
                                                            max={item.discount_type === 'percentage' ? "100" : undefined}
                                                            value={item.discount_value ?? ""}
                                                            onChange={(e) => updateItem(item.id, "discount_value", e.target.value ? Number.parseFloat(e.target.value) : null)}
                                                            placeholder={item.discount_type === 'percentage' ? "10" : "50.00"}
                                                        />
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="space-y-1">
                                                        <div className="font-medium">{formatCurrency(item.total)}</div>
                                                        {item.discount_amount && item.discount_amount > 0 && (
                                                            <div className="text-xs text-muted-foreground">
                                                                Rabatt: -{formatCurrency(item.discount_amount)}
                                                            </div>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => removeItem(item.id)}
                                                        className="text-red-600 hover:text-red-700"
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>

                            {/* Totals */}
                            <div className="mt-6 flex justify-end">
                                <div className="w-80 space-y-2">
                                    <div className="flex justify-between">
                                        <span>Zwischensumme:</span>
                                        <span className="font-medium">{formatCurrency(totals.subtotal)}</span>
                                    </div>
                                    {totals.total_discount > 0 && (
                                        <div className="flex justify-between text-red-600">
                                            <span>Gesamtrabatt:</span>
                                            <span className="font-medium">-{formatCurrency(totals.total_discount)}</span>
                                        </div>
                                    )}
                                    <div className="flex justify-between">
                                        <span>MwSt. ({data.vat_regime === 'standard' ? (settings.tax_rate * 100).toFixed(0) : 0}%):</span>
                                        <span className="font-medium">{formatCurrency(totals.tax_amount)}</span>
                                    </div>
                                    <div className="flex justify-between text-lg font-bold border-t pt-2">
                                        <span>Gesamtbetrag:</span>
                                        <span>{formatCurrency(totals.total)}</span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Notes */}
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('pages.invoices.notes')}</CardTitle>
                            <CardDescription>{t('pages.invoices.notesDesc')}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                <Label htmlFor="notes">{t('common.notes')}</Label>
                                <Textarea
                                    id="notes"
                                    value={data.notes}
                                    onChange={(e) => setData("notes", e.target.value)}
                                    placeholder={t('pages.invoices.notesPlaceholder')}
                                    rows={4}
                                />
                                {errors.notes && <p className="text-red-600 text-sm">{errors.notes}</p>}
                            </div>
                        </CardContent>
                    </Card>

                </form>
            </div>
        </AppLayout>
    )
}
