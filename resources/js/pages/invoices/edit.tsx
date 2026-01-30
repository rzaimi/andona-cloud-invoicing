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
import { Checkbox } from "@/components/ui/checkbox"
import { Badge } from "@/components/ui/badge"
import { ArrowLeft, Plus, Trash2, FileText, FileCheck, ChevronDown, XCircle, Download, ExternalLink } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem, Customer, Invoice, InvoiceItem } from "@/types"
import { ProductSelectorDialog } from "@/components/product-selector-dialog"
import { InvoiceCorrectionDialog } from "@/components/invoice-correction-dialog"
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu"

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
        service_date: (invoice as any).service_date?.split('T')[0] || (invoice as any).service_date || "",
        due_date: invoice.due_date?.split('T')[0] || invoice.due_date,
        notes: invoice.notes || "",
        layout_id: invoice.layout_id?.toString() || "",
        status: invoice.status,
        is_reverse_charge: (invoice as any).is_reverse_charge || false,
        buyer_vat_id: (invoice as any).buyer_vat_id || "",
        vat_exemption_type: (invoice as any).vat_exemption_type || "none",
        vat_exemption_reason: (invoice as any).vat_exemption_reason || "",
        items: invoice.items.map((item) => ({
            id: item.id,
            product_id: item.product_id,
            product_sku: item.product?.sku,
            product_number: item.product?.number,
            description: item.description,
            quantity: Number(item.quantity) || 0,
            unit_price: Number(item.unit_price) || 0,
            unit: item.unit || "Stk.",
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
        const isVatFree = Boolean(data.is_reverse_charge) || (data.vat_exemption_type ?? "none") !== "none"
        const tax_amount = isVatFree
            ? 0
            : itemsWithTotals.reduce((sum: number, item: any) => {
                  const productRate =
                      item.product_id
                          ? (products.find((p) => p.id.toString() === item.product_id?.toString())?.tax_rate ?? settings.tax_rate)
                          : settings.tax_rate
                  return sum + item.total * productRate
              }, 0)
        const total = subtotal + tax_amount

        setTotals({ 
            subtotal, 
            total_discount: totalDiscount,
            tax_amount, 
            total 
        })
    }, [data.items, settings.tax_rate])

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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Rechnung bearbeiten - ${invoice.number}`} />

            <div className="flex flex-1 flex-col gap-6">
                {/* Header */}
                <div className="flex items-center gap-4">
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
                                    <Select value={data.customer_id} onValueChange={(value) => setData("customer_id", value)}>
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
                                    <Select value={data.status} onValueChange={(value) => setData("status", value)}>
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
                                    <Select value={data.layout_id} onValueChange={(value) => setData("layout_id", value)}>
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
                                    />
                                    {formErrors.issue_date && <p className="text-red-600 text-sm">{formErrors.issue_date}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="service_date">Leistungsdatum</Label>
                                    <Input
                                        id="service_date"
                                        type="date"
                                        value={data.service_date}
                                        onChange={(e) => setData("service_date", e.target.value)}
                                        max={data.issue_date}
                                    />
                                    {formErrors.service_date && <p className="text-red-600 text-sm">{formErrors.service_date}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="due_date">Fälligkeitsdatum *</Label>
                                    <Input
                                        id="due_date"
                                        type="date"
                                        value={data.due_date}
                                        onChange={(e) => setData("due_date", e.target.value)}
                                        required
                                    />
                                    {formErrors.due_date && <p className="text-red-600 text-sm">{formErrors.due_date}</p>}
                                </div>
                            </div>

                            <div className="space-y-4 pt-4 border-t">
                                <div className="space-y-2">
                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="is_reverse_charge"
                                            checked={Boolean(data.is_reverse_charge)}
                                            onCheckedChange={(checked) => setData("is_reverse_charge", checked === true)}
                                        />
                                        <Label htmlFor="is_reverse_charge" className="cursor-pointer">
                                            Reverse Charge (§13b UStG)
                                        </Label>
                                    </div>
                                    {formErrors.is_reverse_charge && <p className="text-red-600 text-sm">{formErrors.is_reverse_charge}</p>}
                                </div>

                                {data.is_reverse_charge && (
                                    <div className="space-y-2">
                                        <Label htmlFor="buyer_vat_id">USt-IdNr. des Leistungsempfängers *</Label>
                                        <Input
                                            id="buyer_vat_id"
                                            type="text"
                                            value={data.buyer_vat_id}
                                            onChange={(e) => setData("buyer_vat_id", e.target.value)}
                                            placeholder="DE123456789"
                                            required={Boolean(data.is_reverse_charge)}
                                        />
                                        {formErrors.buyer_vat_id && <p className="text-red-600 text-sm">{formErrors.buyer_vat_id}</p>}
                                    </div>
                                )}

                                <div className="space-y-2">
                                    <Label htmlFor="vat_exemption_type">Umsatzsteuerbefreiung</Label>
                                    <Select value={data.vat_exemption_type} onValueChange={(value) => setData("vat_exemption_type", value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Auswählen" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none">Keine</SelectItem>
                                            <SelectItem value="eu_intracommunity">Innergemeinschaftliche Lieferung</SelectItem>
                                            <SelectItem value="export">Ausfuhrlieferung</SelectItem>
                                            <SelectItem value="other">Sonstige</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {formErrors.vat_exemption_type && <p className="text-red-600 text-sm">{formErrors.vat_exemption_type}</p>}
                                </div>

                                {data.vat_exemption_type === "other" && (
                                    <div className="space-y-2">
                                        <Label htmlFor="vat_exemption_reason">Grund der Befreiung *</Label>
                                        <Textarea
                                            id="vat_exemption_reason"
                                            value={data.vat_exemption_reason}
                                            onChange={(e) => setData("vat_exemption_reason", e.target.value)}
                                            placeholder="z.B. Gemäß §4 UStG"
                                            required={data.vat_exemption_type === "other"}
                                        />
                                        {formErrors.vat_exemption_reason && <p className="text-red-600 text-sm">{formErrors.vat_exemption_reason}</p>}
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
                            <ProductSelectorDialog 
                                products={products || []} 
                                onSelect={(item) => {
                                    const newItem = {
                                        id: Date.now(),
                                        product_id: item.product_id,
                                        product_sku: item.product_sku,
                                        product_number: item.product_number,
                                        description: item.description,
                                        quantity: item.quantity,
                                        unit_price: item.unit_price,
                                        unit: item.unit,
                                        total: item.quantity * item.unit_price,
                                        discount_type: null,
                                        discount_value: null,
                                        discount_amount: 0,
                                    }
                                    setData("items", [...(data.items as any[]), newItem])
                                }}
                            />
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
                                                <TableCell className="align-top">
                                                    <div className="text-sm">
                                                        {(() => {
                                                            const productRate =
                                                                item.product_id
                                                                    ? (products.find((p) => p.id.toString() === item.product_id?.toString())?.tax_rate ??
                                                                          settings.tax_rate)
                                                                    : settings.tax_rate
                                                            const isVatFree = Boolean(data.is_reverse_charge) || (data.vat_exemption_type ?? "none") !== "none"
                                                            return `${((isVatFree ? 0 : productRate) * 100).toFixed(0)}%`
                                                        })()}
                                                    </div>
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
                                                        disabled={data.items.length === 1}
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
                                        <span>MwSt.:</span>
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

                    {/* Actions */}
                    <div className="flex justify-end space-x-2">
                        <Link href="/invoices">
                            <Button type="button" variant="outline">
                                Abbrechen
                            </Button>
                        </Link>
                        <Button type="submit" disabled={processing}>
                            {processing ? "Wird gespeichert..." : "Änderungen speichern"}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    )
}
