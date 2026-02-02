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
import { ArrowLeft, Plus, Trash2 } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem, Customer } from "@/types"
import { ProductSelectorDialog } from "@/components/product-selector-dialog"
import { route } from "ziggy-js"

// German standard tax rates (Umsatzsteuer)
const GERMAN_TAX_RATES = [
    { value: 0.19, label: "19% (Regelsteuersatz)" },
    { value: 0.07, label: "7% (Ermäßigter Satz)" },
    { value: 0.00, label: "0% (Steuerfrei)" },
] as const

interface OfferItem {
    id: number | string
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

interface Offer {
    id: string
    number: string
    customer_id: string
    issue_date: string
    valid_until: string
    notes: string | null
    terms_conditions: string | null
    layout_id: string | null
    status: string
    items: OfferItem[]
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

interface OffersEditProps {
    offer: Offer
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

export default function OffersEdit() {
    const { offer, customers, layouts, products, settings } = usePage<OffersEditProps>().props

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Angebote", href: "/offers" },
        { title: offer.number },
    ]

    const { data, setData, put, processing, errors } = useForm({
        customer_id: offer.customer_id.toString(),
        issue_date: offer.issue_date?.split('T')[0] || offer.issue_date,
        valid_until: offer.valid_until?.split('T')[0] || offer.valid_until,
        notes: offer.notes || "",
        terms: offer.terms_conditions || "",
        layout_id: offer.layout_id?.toString() || "",
        status: offer.status,
        items: offer.items.map((item) => ({
            id: item.id,
            product_id: (item as any).product_id,
            product_sku: (item as any).product?.sku,
            product_number: (item as any).product?.number,
            description: item.description,
            quantity: Number(item.quantity) || 0,
            unit_price: Number(item.unit_price) || 0,
            unit: item.unit || "Stk.",
            tax_rate: Number((item as any).tax_rate) || settings.tax_rate || 0.19,
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

    const germanUnits = ["Stk.", "Std.", "Tag", "Monat", "Jahr", "m", "m²", "m³", "kg", "l", "Paket"]

    // Calculate totals whenever items change
    useEffect(() => {
        // Calculate each item's total with discount
        const itemsWithTotals = data.items.map((item) => {
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
        
        const subtotal = itemsWithTotals.reduce((sum, item) => sum + item.total, 0)
        const totalDiscount = itemsWithTotals.reduce((sum, item) => sum + item.discount_amount, 0)
        
        // Calculate tax amount per item (supports mixed tax rates)
        const tax_amount = itemsWithTotals.reduce((sum, item) => {
            const taxRate = typeof item.tax_rate === 'number' ? item.tax_rate : 0
            return sum + (item.total * taxRate)
        }, 0)
        
        const total = subtotal + tax_amount

        setTotals({ 
            subtotal, 
            total_discount: totalDiscount,
            tax_amount, 
            total 
        })
    }, [data.items])

    const addItem = () => {
        const newItem: OfferItem = {
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
        setData("items", [...data.items, newItem])
    }

    const removeItem = (id: number | string) => {
        if (data.items.length > 1) {
            setData(
                "items",
                data.items.filter((item) => item.id !== id),
            )
        }
    }

    const updateItem = (id: number | string, field: keyof OfferItem, value: string | number | null) => {
        const updatedItems = data.items.map((item) => {
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
        put(route("offers.update", offer.id))
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
            accepted: { label: "Angenommen", variant: "default" as const },
            rejected: { label: "Abgelehnt", variant: "destructive" as const },
        }

        const config = statusConfig[status as keyof typeof statusConfig] || statusConfig.draft
        return <Badge variant={config.variant}>{config.label}</Badge>
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Angebot bearbeiten - ${offer.number}`} />

            <div className="flex flex-1 flex-col gap-6">
                {/* Header with Action Buttons */}
                <div className="sticky top-0 z-10 bg-white border-b pb-4">
                    <div className="flex items-center gap-4 mb-4">
                        <Link href="/offers">
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Zurück
                            </Button>
                        </Link>
                        <div className="flex-1">
                            <div className="flex items-center gap-3">
                                <h1 className="text-1xl font-bold text-gray-900">Angebot bearbeiten</h1>
                                {getStatusBadge(offer.status)}
                            </div>
                            <p className="text-gray-600">{offer.number}</p>
                        </div>
                    </div>
                    
                    {/* Action Buttons - Top of Form */}
                    <div className="flex justify-end gap-2">
                        <Link href="/offers">
                            <Button type="button" variant="outline">
                                Abbrechen
                            </Button>
                        </Link>
                        <Button 
                            type="submit" 
                            disabled={processing}
                            onClick={handleSubmit}
                        >
                            {processing ? "Wird gespeichert..." : "Änderungen speichern"}
                        </Button>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Basic Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Angebotsinformationen</CardTitle>
                            <CardDescription>Grundlegende Informationen zum Angebot</CardDescription>
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
                                    <Label htmlFor="status">Status</Label>
                                    <Select value={data.status} onValueChange={(value) => setData("status", value)}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="draft">Entwurf</SelectItem>
                                            <SelectItem value="sent">Versendet</SelectItem>
                                            <SelectItem value="accepted">Angenommen</SelectItem>
                                            <SelectItem value="rejected">Abgelehnt</SelectItem>
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
                                    {errors.layout_id && <p className="text-red-600 text-sm">{errors.layout_id}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="issue_date">Angebotsdatum *</Label>
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
                                    <Label htmlFor="valid_until">Gültig bis *</Label>
                                    <Input
                                        id="valid_until"
                                        type="date"
                                        value={data.valid_until}
                                        onChange={(e) => setData("valid_until", e.target.value)}
                                        required
                                    />
                                    {errors.valid_until && <p className="text-red-600 text-sm">{errors.valid_until}</p>}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Offer Items */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <div>
                                <CardTitle>Angebotspositionen</CardTitle>
                                <CardDescription>Bearbeiten Sie die Positionen Ihres Angebots</CardDescription>
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
                                    setData("items", [...data.items, newItem])
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
                                        {data.items.map((item, index) => (
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
                                                    {errors[`items.${index}.description`] && (
                                                        <p className="text-red-600 text-sm mt-1">{errors[`items.${index}.description`]}</p>
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
                                                    {errors[`items.${index}.quantity`] && (
                                                        <p className="text-red-600 text-sm mt-1">{errors[`items.${index}.quantity`]}</p>
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
                                                        value={item.tax_rate.toString()} 
                                                        onValueChange={(value) => updateItem(item.id, "tax_rate", Number.parseFloat(value))}
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
                                                    />
                                                    {errors[`items.${index}.unit_price`] && (
                                                        <p className="text-red-600 text-sm mt-1">{errors[`items.${index}.unit_price`]}</p>
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
                                    {totals.total_discount > 0 && (
                                        <div className="flex justify-between text-red-600">
                                            <span>Gesamtrabatt:</span>
                                            <span className="font-medium">-{formatCurrency(totals.total_discount)}</span>
                                        </div>
                                    )}
                                    <div className="flex justify-between">
                                        <span>Zwischensumme:</span>
                                        <span className="font-medium">{formatCurrency(totals.subtotal)}</span>
                                    </div>
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

                    {/* Notes and Terms */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Notizen</CardTitle>
                                <CardDescription>Zusätzliche Informationen zum Angebot</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-2">
                                    <Label htmlFor="notes">Notizen</Label>
                                    <Textarea
                                        id="notes"
                                        value={data.notes}
                                        onChange={(e) => setData("notes", e.target.value)}
                                        placeholder="Zusätzliche Informationen..."
                                        rows={4}
                                    />
                                    {errors.notes && <p className="text-red-600 text-sm">{errors.notes}</p>}
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Geschäftsbedingungen</CardTitle>
                                <CardDescription>Bedingungen für dieses Angebot</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-2">
                                    <Label htmlFor="terms">Bedingungen</Label>
                                    <Textarea
                                        id="terms"
                                        value={data.terms}
                                        onChange={(e) => setData("terms", e.target.value)}
                                        placeholder="Geschäftsbedingungen, Zahlungsmodalitäten..."
                                        rows={4}
                                    />
                                    {errors.terms && <p className="text-red-600 text-sm">{errors.terms}</p>}
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                </form>
            </div>
        </AppLayout>
    )
}
