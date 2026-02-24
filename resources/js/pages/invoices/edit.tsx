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
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Badge } from "@/components/ui/badge"
import { ArrowLeft, Plus, Trash2, FileText, FileCheck, ChevronDown, XCircle, Download, ExternalLink } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem, Customer, Invoice, InvoiceItem } from "@/types"
import { ProductSelectorDialog } from "@/components/product-selector-dialog"
import { InvoiceCorrectionDialog } from "@/components/invoice-correction-dialog"
import { InvoiceAuditLogDialog } from "@/components/invoice-audit-log-dialog"
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu"

// German standard tax rates (Umsatzsteuer)
const GERMAN_TAX_RATES = [
    { value: 0.19, label: "19% (Regelsteuersatz)" },
    { value: 0.07, label: "7% (Ermäßigter Satz)" },
    { value: 0.00, label: "0% (Steuerfrei)" },
] as const

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

interface Document {
    id: string
    name: string
    original_filename: string
    file_size: number
    mime_type: string
    category: string
    description?: string
    tags?: string[]
    link_type?: string
    created_at: string
}

interface InvoicesEditProps {
    invoice: Invoice & { items: InvoiceItem[]; documents?: Document[] }
    customers: Customer[]
    layouts: any[]
    products: Product[]
    settings: {
        currency: string
        tax_rate: number
        decimal_separator: string
        thousands_separator: string
    }
}

export default function InvoicesEdit() {
    // @ts-ignore
    const { invoice, customers, layouts, products, settings } = usePage().props as unknown as InvoicesEditProps

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Rechnungen", href: "/invoices" },
        { title: invoice.number },
    ]

    const { data, setData, put, processing, errors } = useForm<Record<string, any>>({
        customer_id: invoice.customer_id.toString(),
        issue_date: invoice.issue_date?.split('T')[0] || invoice.issue_date,
        service_date: invoice.service_date?.split('T')[0] || invoice.service_date || "",
        service_period_start: invoice.service_period_start?.split('T')[0] || invoice.service_period_start || "",
        service_period_end: invoice.service_period_end?.split('T')[0] || invoice.service_period_end || "",
        due_date: invoice.due_date?.split('T')[0] || invoice.due_date,
        notes: invoice.notes || "",
        layout_id: invoice.layout_id?.toString() || "",
        status: invoice.status,
        vat_regime: invoice.vat_regime || "standard",
        invoice_type: (invoice as any).invoice_type || "standard",
        sequence_number: (invoice as any).sequence_number?.toString() || "",
        skonto_percent: (invoice as any).skonto_percent ? String(Math.round(Number((invoice as any).skonto_percent))) : "",
        skonto_days: (invoice as any).skonto_days ? String(Math.round(Number((invoice as any).skonto_days))) : "",
        items: invoice.items.map((item) => ({
            id: item.id,
            product_id: item.product_id,
            product_sku: item.product?.sku,
            product_number: item.product?.number,
            description: item.description,
            quantity: Number(item.quantity) || 0,
            unit_price: Number(item.unit_price) || 0,
            unit: item.unit || "Stk.",
            tax_rate: Number(item.tax_rate) || settings.tax_rate || 0.19,
            total: Number(item.total) || 0,
            discount_type: item.discount_type || null,
            discount_value: item.discount_value ? Number(item.discount_value) : null,
            discount_amount: item.discount_amount ? Number(item.discount_amount) : 0,
        })),
    })

    const [totals, setTotals] = useState({
        subtotal: 0,
        total_discount: 0,
        tax_amount: 0,
        total: 0,
    })

    // Skonto live preview
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

    const [correctionDialogOpen, setCorrectionDialogOpen] = useState(false)

    const germanUnits = ["Stk.", "Std.", "Tag", "Monat", "Jahr", "m", "m²", "m³", "kg", "l", "Paket"]
    const formErrors = errors as Record<string, string>

    // Calculate totals whenever items change
    useEffect(() => {
        // Calculate each item's total with discount
        const itemsWithTotals = (data.items as any[]).map((item: any) => {
            const baseTotal = item.quantity * item.unit_price
            let discountAmount = 0
            if (item.discount_type && item.discount_value !== null) {
                if (item.discount_type === 'percentage') {
                    discountAmount = baseTotal * (item.discount_value / 100)
                } else {
                    discountAmount = Math.min(item.discount_value, baseTotal)
                }
            }
            return {
                ...item,
                discount_amount: discountAmount,
                total: baseTotal - discountAmount,
            }
        })
        
        const subtotal = itemsWithTotals.reduce((sum: number, item: any) => sum + item.total, 0)
        const totalDiscount = itemsWithTotals.reduce((sum: number, item: any) => sum + (item.discount_amount || 0), 0)
        
        // Calculate tax based on VAT regime (item-level tax rates)
        let tax_amount = 0
        if (data.vat_regime === 'standard') {
            tax_amount = itemsWithTotals.reduce((sum: number, item: any) => {
                const taxRate = typeof item.tax_rate === 'number' ? item.tax_rate : 0
                return sum + (item.total * taxRate)
            }, 0)
        } else {
            // All other regimes are tax-exempt or handled by buyer
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
        const newItem = {
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
        setData("items", [...(data.items as any[]), newItem])
    }

    const removeItem = (id: number | string) => {
        if ((data.items as any[]).length > 1) {
            setData(
                "items",
                (data.items as any[]).filter((item: any) => item.id !== id),
            )
        }
    }

    const updateItem = (id: number | string, field: string, value: string | number | null) => {
        const updatedItems = (data.items as any[]).map((item: any) => {
            if (item.id === id) {
                const updatedItem = { ...item, [field]: value }
                // Recalculate total when quantity, unit_price, or discount changes
                if (field === "quantity" || field === "unit_price" || field === "discount_type" || field === "discount_value") {
                    const baseTotal = Number(updatedItem.quantity) * Number(updatedItem.unit_price)
                    let discountAmount = 0
                    if (updatedItem.discount_type && updatedItem.discount_value !== null) {
                        if (updatedItem.discount_type === 'percentage') {
                            discountAmount = baseTotal * (updatedItem.discount_value / 100)
                        } else {
                            discountAmount = Math.min(updatedItem.discount_value, baseTotal)
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
        put(`/invoices/${invoice.id}`)
    }

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat("de-DE", {
            style: "currency",
            currency: settings.currency || "EUR",
        }).format(amount)
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

    // Determine if invoice can be edited based on German accounting standards (GoBD)
    const canEdit = invoice.status === 'draft'
    const editWarning = !canEdit && invoice.status !== 'cancelled' 
        ? "Gemäß GoBD-Richtlinien können versendete, bezahlte oder überfällige Rechnungen nicht mehr bearbeitet werden. Bitte erstellen Sie stattdessen eine Stornorechnung." 
        : invoice.status === 'cancelled' 
        ? "Stornierte Rechnungen können nicht bearbeitet werden." 
        : null

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Rechnung bearbeiten - ${invoice.number}`} />

            <div className="flex flex-1 flex-col gap-6">
                {/* Header with Action Buttons */}
                <div className="sticky top-0 z-10 bg-white border-b pb-4">
                    <div className="flex items-center gap-4 mb-4">
                        <Link href="/invoices">
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Zurück
                            </Button>
                        </Link>
                        <div className="flex-1">
                            <div className="flex items-center gap-3">
                                <h1 className="text-1xl font-bold text-gray-900">
                                    {invoice.is_correction ? "Stornorechnung bearbeiten" : "Rechnung bearbeiten"}
                                </h1>
                                {getStatusBadge(invoice.status)}
                                {invoice.is_correction && (
                                    <Badge variant="destructive">Stornorechnung</Badge>
                                )}
                            </div>
                            <div className="flex items-center gap-2">
                                <p className="text-gray-600">{invoice.number}</p>
                                {invoice.is_correction && invoice.correctsInvoice && (
                                    <>
                                        <span className="text-gray-400">•</span>
                                        <Link href={`/invoices/${invoice.correctsInvoice.id}/edit`} className="text-blue-600 hover:underline">
                                            Original: {invoice.correctsInvoice.number}
                                        </Link>
                                    </>
                                )}
                                {invoice.correctedByInvoice && (
                                    <>
                                        <span className="text-gray-400">•</span>
                                        <Link href={`/invoices/${invoice.correctedByInvoice.id}/edit`} className="text-red-600 hover:underline">
                                            Storniert durch: {invoice.correctedByInvoice.number}
                                        </Link>
                                    </>
                                )}
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => window.open(`/invoices/${invoice.id}/pdf`, "_blank")}
                            >
                                <FileText className="mr-2 h-4 w-4" />
                                PDF
                            </Button>
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="outline" size="sm">
                                        <FileCheck className="mr-2 h-4 w-4" />
                                        E-Rechnung
                                        <ChevronDown className="ml-2 h-3 w-3" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem
                                        onClick={() => window.open(`/invoices/${invoice.id}/xrechnung`, "_blank")}
                                    >
                                        <FileText className="mr-2 h-4 w-4" />
                                        XRechnung (XML)
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        onClick={() => window.open(`/invoices/${invoice.id}/zugferd`, "_blank")}
                                    >
                                        <FileCheck className="mr-2 h-4 w-4" />
                                        ZUGFeRD (PDF+XML)
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                            {/* Audit Log Button - GoBD Compliance */}
                            <InvoiceAuditLogDialog invoiceId={invoice.id} />
                            
                            {/* Correction Button - Only show for sent/paid invoices that haven't been corrected */}
                            {(invoice.status === 'sent' || invoice.status === 'paid' || invoice.status === 'overdue') && 
                             !invoice.corrected_by_invoice_id && 
                             !invoice.is_correction && (
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    onClick={() => setCorrectionDialogOpen(true)}
                                    type="button"
                                >
                                    <XCircle className="mr-2 h-4 w-4" />
                                    Stornieren
                                </Button>
                            )}
                        </div>
                    </div>
                    
                    {/* Action Buttons - Top of Form */}
                    <div className="flex justify-between items-center">
                        <div className="flex-1">
                            {editWarning && (
                                <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                                    <p className="text-sm text-yellow-800">
                                        <strong>Hinweis:</strong> {editWarning}
                                    </p>
                                </div>
                            )}
                        </div>
                        <div className="flex gap-2 ml-4">
                            <Link href="/invoices">
                                <Button type="button" variant="outline">
                                    Abbrechen
                                </Button>
                            </Link>
                            <Button 
                                type="submit" 
                                disabled={processing || !canEdit}
                                onClick={handleSubmit}
                                title={!canEdit ? "Rechnung kann im aktuellen Status nicht bearbeitet werden" : ""}
                            >
                                {processing ? "Wird gespeichert..." : "Änderungen speichern"}
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Correction Dialog */}
                <InvoiceCorrectionDialog
                    open={correctionDialogOpen}
                    onOpenChange={setCorrectionDialogOpen}
                    invoice={invoice}
                />

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Basic Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Rechnungsinformationen</CardTitle>
                            <CardDescription>Grundlegende Informationen zur Rechnung</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="customer_id">Kunde *</Label>
                                    <Select value={data.customer_id} onValueChange={(value) => setData("customer_id", value)} disabled={!canEdit}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Kunde auswählen" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {customers.map((customer) => (
                                                <SelectItem key={customer.id} value={customer.id.toString()}>
                                                    {customer.name} ({customer.email})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {formErrors.customer_id && <p className="text-red-600 text-sm">{formErrors.customer_id}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="status">Status</Label>
                                    <Select value={data.status} onValueChange={(value) => setData("status", value)} disabled={!canEdit}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="draft">Entwurf</SelectItem>
                                            <SelectItem value="sent">Versendet</SelectItem>
                                            <SelectItem value="paid">Bezahlt</SelectItem>
                                            <SelectItem value="overdue">Überfällig</SelectItem>
                                            <SelectItem value="cancelled">Storniert</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.status && <p className="text-red-600 text-sm">{errors.status}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="layout_id">Layout</Label>
                                    <Select value={data.layout_id} onValueChange={(value) => setData("layout_id", value)} disabled={!canEdit}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Layout auswählen" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {layouts.map((layout) => (
                                                <SelectItem key={layout.id} value={layout.id.toString()}>
                                                    {layout.name} {layout.is_default && "(Standard)"}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {formErrors.layout_id && <p className="text-red-600 text-sm">{formErrors.layout_id}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="issue_date">Rechnungsdatum *</Label>
                                    <Input
                                        id="issue_date"
                                        type="date"
                                        value={data.issue_date}
                                        onChange={(e) => setData("issue_date", e.target.value)}
                                        required
                                        disabled={!canEdit}
                                    />
                                    {formErrors.issue_date && <p className="text-red-600 text-sm">{formErrors.issue_date}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="due_date">Fälligkeitsdatum *</Label>
                                    <Input
                                        id="due_date"
                                        type="date"
                                        value={data.due_date}
                                        onChange={(e) => setData("due_date", e.target.value)}
                                        required
                                        disabled={!canEdit}
                                    />
                                    {errors.due_date && <p className="text-red-600 text-sm">{errors.due_date}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="vat_regime">Umsatzsteuer-Regelung *</Label>
                                    <Select value={data.vat_regime} onValueChange={(value) => setData("vat_regime", value)} disabled={!canEdit}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Regelung auswählen" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="standard">Regelbesteuerung (19% / 7%)</SelectItem>
                                            <SelectItem value="small_business">Kleinunternehmerregelung (§ 19 UStG)</SelectItem>
                                            <SelectItem value="reverse_charge">Reverse Charge (§ 13b UStG)</SelectItem>
                                            <SelectItem value="intra_community">Innergemeinschaftliche Lieferung (§ 4 Nr. 1b UStG)</SelectItem>
                                            <SelectItem value="export">Ausfuhrlieferung (§ 4 Nr. 1a UStG)</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.vat_regime && <p className="text-red-600 text-sm">{errors.vat_regime}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="service_date">Leistungsdatum (einzelner Tag)</Label>
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
                                        disabled={!canEdit}
                                    />
                                    {errors.service_date && <p className="text-red-600 text-sm">{errors.service_date}</p>}
                                </div>

                                <div className="grid grid-cols-2 gap-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="service_period_start">Leistungszeitraum von</Label>
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
                                            disabled={!canEdit}
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
                                            disabled={!canEdit}
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
                            <CardTitle>Rechnungstyp &amp; Zahlungsbedingungen</CardTitle>
                            <CardDescription>Typ, Skonto und erweiterte Zahlungsoptionen</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="invoice_type">Rechnungstyp</Label>
                                    <Select
                                        value={data.invoice_type || "standard"}
                                        onValueChange={(v) => {
                                            setData((prev: any) => ({
                                                ...prev,
                                                invoice_type: v,
                                                sequence_number: v !== "abschlagsrechnung" ? "" : prev.sequence_number,
                                            }))
                                        }}
                                        disabled={!canEdit}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="standard">Rechnung</SelectItem>
                                            <SelectItem value="abschlagsrechnung">Abschlagsrechnung</SelectItem>
                                            <SelectItem value="schlussrechnung">Schlussrechnung</SelectItem>
                                            <SelectItem value="nachtragsrechnung">Nachtragsrechnung</SelectItem>
                                            <SelectItem value="korrekturrechnung">Korrekturrechnung</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {formErrors.invoice_type && <p className="text-red-600 text-sm">{formErrors.invoice_type}</p>}
                                </div>
                                {data.invoice_type === "abschlagsrechnung" && (
                                    <div className="space-y-2">
                                        <Label htmlFor="sequence_number">Abschlagsnummer (1–20) *</Label>
                                        <Select
                                            value={data.sequence_number?.toString() || ""}
                                            onValueChange={(v) => setData("sequence_number", v)}
                                            disabled={!canEdit}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Abschlag wählen" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {Array.from({ length: 20 }, (_, i) => i + 1).map((n) => (
                                                    <SelectItem key={n} value={n.toString()}>
                                                        Abschlag {n}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {formErrors.sequence_number && <p className="text-red-600 text-sm">{formErrors.sequence_number}</p>}
                                    </div>
                                )}
                            </div>
                            <div className="border rounded-lg p-4 space-y-3 bg-muted/30">
                                <div>
                                    <p className="font-medium text-sm">Skonto (Zahlungsrabatt)</p>
                                    <p className="text-xs text-muted-foreground">Optionaler Rabatt bei frühzeitiger Zahlung</p>
                                </div>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label>Skonto-Prozentsatz</Label>
                                        <Select
                                            value={data.skonto_percent?.toString() || "none"}
                                            onValueChange={(v) => setData("skonto_percent", v === "none" ? "" : v)}
                                            disabled={!canEdit}
                                        >
                                            <SelectTrigger><SelectValue placeholder="Kein Skonto" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="none">Kein Skonto</SelectItem>
                                                <SelectItem value="2">2 %</SelectItem>
                                                <SelectItem value="3">3 %</SelectItem>
                                                <SelectItem value="4">4 %</SelectItem>
                                                <SelectItem value="5">5 %</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        {formErrors.skonto_percent && <p className="text-red-600 text-sm">{formErrors.skonto_percent}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Skonto-Frist (Tage)</Label>
                                        <Select
                                            value={data.skonto_days?.toString() || "none"}
                                            onValueChange={(v) => setData("skonto_days", v === "none" ? "" : v)}
                                            disabled={!canEdit}
                                        >
                                            <SelectTrigger><SelectValue placeholder="Frist wählen" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="none">Keine Frist</SelectItem>
                                                <SelectItem value="7">7 Tage</SelectItem>
                                                <SelectItem value="10">10 Tage</SelectItem>
                                                <SelectItem value="14">14 Tage</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        {formErrors.skonto_days && <p className="text-red-600 text-sm">{formErrors.skonto_days}</p>}
                                    </div>
                                </div>
                                {skontoAmount !== null && skontoDate && (
                                    <div className="rounded bg-green-50 border border-green-200 px-3 py-2 text-sm text-green-800 flex gap-6 flex-wrap">
                                        <span><span className="font-medium">Skonto-Betrag: </span>{formatCurrency(skontoAmount)}</span>
                                        <span><span className="font-medium">Skonto bis: </span>{skontoDate}</span>
                                        <span><span className="font-medium">Bei Skonto zahlen: </span>{formatCurrency(totals.total - skontoAmount)}</span>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Invoice Items */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <div>
                                <CardTitle>Rechnungspositionen</CardTitle>
                                <CardDescription>Bearbeiten Sie die Positionen Ihrer Rechnung</CardDescription>
                            </div>
                            {canEdit && (
                                <ProductSelectorDialog 
                                    products={products || []} 
                                    onSelect={(item) => {
                                        // Check if product already exists in items
                                        const existingItemIndex = (data.items as any[]).findIndex(
                                            (i) => i.product_id && i.product_id === item.product_id
                                        )
                                        
                                        if (existingItemIndex !== -1) {
                                            // Product exists, increase quantity
                                            const updatedItems = [...(data.items as any[])]
                                            const existingItem = updatedItems[existingItemIndex]
                                            updatedItems[existingItemIndex] = {
                                                ...existingItem,
                                                quantity: existingItem.quantity + item.quantity,
                                                total: (existingItem.quantity + item.quantity) * existingItem.unit_price,
                                            }
                                            setData("items", updatedItems)
                                        } else {
                                            // New product, add to list
                                            const newItem = {
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
                                            setData("items", [...(data.items as any[]), newItem])
                                        }
                                    }}
                                />
                            )}
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-[12%]">Produkt-Nr.</TableHead>
                                            <TableHead className="w-[26%]">Beschreibung</TableHead>
                                            <TableHead className="w-[8%]">Menge</TableHead>
                                            <TableHead className="w-[8%]">Einheit</TableHead>
                                            <TableHead className="w-[6%]">USt.</TableHead>
                                            <TableHead className="w-[12%]">Einzelpreis</TableHead>
                                            <TableHead className="w-[10%]">Rabatt</TableHead>
                                            <TableHead className="w-[10%]">Rabatt-Wert</TableHead>
                                            <TableHead className="w-[12%]">Gesamtpreis</TableHead>
                                            <TableHead className="w-[10%]">Aktionen</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {(data.items as any[]).map((item: any, index: number) => (
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
                                                        disabled={!canEdit}
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
                                                        disabled={!canEdit}
                                                    />
                                                    {formErrors[`items.${index}.quantity`] && (
                                                        <p className="text-red-600 text-sm mt-1">{formErrors[`items.${index}.quantity`]}</p>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <Select value={item.unit} onValueChange={(value) => updateItem(item.id, "unit", value)} disabled={!canEdit}>
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
                                                        disabled={!canEdit || data.vat_regime !== 'standard'}
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {GERMAN_TAX_RATES.map((rate) => (
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
                                                        disabled={!canEdit}
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
                                                        disabled={!canEdit}
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
                                                            disabled={!canEdit}
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
                                                        disabled={!canEdit || data.items.length === 1}
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
                            <CardTitle>Zusätzliche Informationen</CardTitle>
                            <CardDescription>Notizen und Bemerkungen zur Rechnung</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                <Label htmlFor="notes">Notizen</Label>
                            <Textarea
                                id="notes"
                                value={data.notes}
                                onChange={(e) => setData("notes", e.target.value)}
                                    placeholder="Zusätzliche Informationen, Zahlungsbedingungen, etc..."
                                    rows={4}
                                    disabled={!canEdit}
                                />
                            {formErrors.notes && <p className="text-red-600 text-sm">{formErrors.notes}</p>}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Documents */}
                    {invoice.documents && invoice.documents.length > 0 && (
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle>Verknüpfte Dokumente</CardTitle>
                                        <CardDescription>Mit dieser Rechnung verknüpfte Dokumente</CardDescription>
                                    </div>
                                    <Link href="/settings/documents">
                                        <Button variant="outline" size="sm">
                                            <FileText className="mr-2 h-4 w-4" />
                                            Alle Dokumente
                                        </Button>
                                    </Link>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-2">
                                    {invoice.documents.map((document) => (
                                        <div
                                            key={document.id}
                                            className="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50"
                                        >
                                            <div className="flex items-center gap-3 flex-1 min-w-0">
                                                <FileText className="h-5 w-5 text-gray-400 shrink-0" />
                                                <div className="flex-1 min-w-0">
                                                    <p className="font-medium text-sm truncate">{document.name}</p>
                                                    <p className="text-xs text-gray-500">
                                                        {new Date(document.created_at).toLocaleDateString('de-DE')}
                                                        {document.link_type && (
                                                            <> • {document.link_type}</>
                                                        )}
                                                    </p>
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-2 shrink-0">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => window.open(`/documents/${document.id}/download`, '_blank')}
                                                >
                                                    <Download className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                </form>
            </div>
        </AppLayout>
    )
}
