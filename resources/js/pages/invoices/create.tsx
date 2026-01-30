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
import { ArrowLeft, Plus, Trash2 } from "lucide-react"
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
    total: number
    discount_type?: "percentage" | "fixed" | null
    discount_value?: number | null
    discount_amount?: number
}

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
    settings: {
        currency: string
        tax_rate: number
        invoice_prefix: string
        decimal_separator: string
        thousands_separator: string
        payment_terms: number
    }
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Rechnungen", href: "/invoices" },
    { title: "Neue Rechnung" },
]

export default function InvoicesCreate() {
    const { customers, layouts, products, settings } = usePage().props as unknown as InvoicesCreateProps

    const { data, setData, post, processing, errors } = useForm<Record<string, any>>({
        customer_id: "",
        issue_date: new Date().toISOString().split("T")[0],
        service_date: "",
        due_date: new Date(Date.now() + settings.payment_terms * 24 * 60 * 60 * 1000).toISOString().split("T")[0],
        notes: "",
        layout_id: layouts.find((l) => l.is_default)?.id || "",
        is_reverse_charge: false,
        buyer_vat_id: "",
        vat_exemption_type: "none",
        vat_exemption_reason: "",
        items: [
            {
                id: 1,
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
            },
        ] as InvoiceItem[],
    })

    const [totals, setTotals] = useState({
        subtotal: 0,
        total_discount: 0,
        tax_amount: 0,
        total: 0,
    })

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
        const isVatFree = Boolean(data.is_reverse_charge) || (data.vat_exemption_type ?? "none") !== "none"
        const tax_amount = isVatFree ? 0 : subtotal * settings.tax_rate
        const total = subtotal + tax_amount

        setTotals({ 
            subtotal, 
            total_discount: totalDiscount,
            tax_amount, 
            total 
        })
    }, [data.items, settings.tax_rate])

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
            total: 0,
            discount_type: null,
            discount_value: null,
            discount_amount: 0,
        }
        setData("items", [...(data.items as InvoiceItem[]), newItem])
    }

    const removeItem = (id: number) => {
        if ((data.items as InvoiceItem[]).length > 1) {
            setData(
                "items",
                (data.items as InvoiceItem[]).filter((item: InvoiceItem) => item.id !== id),
            )
        }
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
            <Head title="Neue Rechnung" />

            <div className="flex flex-1 flex-col gap-6">
                {/* Header */}
                <div className="flex items-center gap-4">
                    <Link href="/invoices">
                        <Button variant="outline" size="sm">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Zurück
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-1xl font-bold text-gray-900">Neue Rechnung erstellen</h1>
                        <p className="text-gray-600">Erstellen Sie eine neue Rechnung für Ihre Kunden</p>
                    </div>
                </div>

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
                                    {errors.customer_id && <p className="text-red-600 text-sm">{errors.customer_id}</p>}
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
                                    {errors.layout_id && <p className="text-red-600 text-sm">{errors.layout_id}</p>}
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
                                    {errors.issue_date && <p className="text-red-600 text-sm">{errors.issue_date}</p>}
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
                                    {errors.service_date && <p className="text-red-600 text-sm">{errors.service_date}</p>}
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
                                    {errors.due_date && <p className="text-red-600 text-sm">{errors.due_date}</p>}
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
                                    {errors.is_reverse_charge && <p className="text-red-600 text-sm">{errors.is_reverse_charge}</p>}
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
                                        {errors.buyer_vat_id && <p className="text-red-600 text-sm">{errors.buyer_vat_id}</p>}
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
                                    {errors.vat_exemption_type && <p className="text-red-600 text-sm">{errors.vat_exemption_type}</p>}
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
                                        {errors.vat_exemption_reason && <p className="text-red-600 text-sm">{errors.vat_exemption_reason}</p>}
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
                                <CardDescription>Fügen Sie Positionen zu Ihrer Rechnung hinzu</CardDescription>
                            </div>
                            <ProductSelectorDialog 
                                products={products} 
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
                                    setData("items", [...(data.items as InvoiceItem[]), newItem])
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
                                        {(data.items as InvoiceItem[]).map((item: InvoiceItem, index: number) => (
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
                                                    <div className="text-sm">{(settings.tax_rate * 100).toFixed(0)}%</div>
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
                                        <span>MwSt. ({(settings.tax_rate * 100).toFixed(0)}%):</span>
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
                                {errors.notes && <p className="text-red-600 text-sm">{errors.notes}</p>}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Actions */}
                    <div className="flex justify-end space-x-2">
                        <Link href="/invoices">
                            <Button type="button" variant="outline">
                                Abbrechen
                            </Button>
                        </Link>
                        <Button type="submit" disabled={processing}>
                            {processing ? "Wird erstellt..." : "Rechnung erstellen"}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    )
}
