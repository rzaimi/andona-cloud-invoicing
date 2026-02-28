"use client"

import type React from "react"
import { Head, Link, useForm, router } from "@inertiajs/react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Checkbox } from "@/components/ui/checkbox"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { ArrowLeft, Save, Package, Trash2, AlertCircle } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { User, Product, Category } from "@/types"

interface ProductEditProps {
    user: User
    product: Product
    categories: Category[]
}

interface ProductFormData {
    name: string
    description: string
    unit: string
    price: number
    cost_price: number
    category_id: string
    sku: string
    barcode: string
    tax_rate: number
    stock_quantity: number
    min_stock_level: number
    track_stock: boolean
    is_service: boolean
    status: string
    custom_fields: Record<string, any>
}

export default function ProductEdit({ user, product, categories }: ProductEditProps) {
    const { data, setData, put, processing, errors } = useForm<ProductFormData>({
        name: product.name,
        description: product.description || "",
        unit: product.unit,
        price: product.price,
        cost_price: product.cost_price || 0,
        category_id: product.category_id || "none",
        sku: product.sku || "",
        barcode: product.barcode || "",
        tax_rate: product.tax_rate, // Already in decimal format from backend
        stock_quantity: product.stock_quantity,
        min_stock_level: product.min_stock_level,
        track_stock: product.track_stock,
        is_service: product.is_service,
        status: product.status,
        custom_fields: product.custom_fields || {},
    })

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        put(`/products/${product.id}`)
    }

    const deleteProduct = () => {
        if (
            confirm(
                t('pages.products.deleteConfirmFull')
            )
        ) {
            router.delete(`/products/${product.id}`)
        }
    }

    const units = ["Stk.", "Std.", "Tag", "Woche", "Monat", "Jahr", "m", "m²", "m³", "kg", "g", "l", "ml", "Paket", "Set"]

    const taxRates = [
        { value: 0, label: "0% (Steuerbefreit)" },
        { value: 0.07, label: `7% (${t('pages.products.reducedRate')})` },
        { value: 0.19, label: "19% (Standard)" },
    ]

    // Check if there are any errors
    const hasErrors = Object.keys(errors).length > 0

    return (
        <AppLayout user={user}>
            <Head title={`${product.name} bearbeiten`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Button variant="ghost" asChild>
                            <Link href="/products">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                {t('common.back')}
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-1xl font-bold tracking-tight">{t('pages.products.edit')}</h1>
                            <p className="text-muted-foreground">
                                {product.number} - {product.name}
                            </p>
                        </div>
                    </div>
                    <Button variant="destructive" onClick={deleteProduct}>
                        <Trash2 className="mr-2 h-4 w-4" />
                        {t('common.delete')}
                    </Button>
                </div>

                {/* Error Alert */}
                {hasErrors && (
                    <Alert variant="destructive">
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>
                            <div className="font-medium mb-2">Bitte korrigieren Sie die folgenden Fehler:</div>
                            <ul className="list-disc list-inside space-y-1">
                                {Object.entries(errors).map(([field, message]) => (
                                    <li key={field} className="text-sm">
                                        <strong>{getFieldLabel(field)}:</strong> {message}
                                    </li>
                                ))}
                            </ul>
                        </AlertDescription>
                    </Alert>
                )}

                <form onSubmit={handleSubmit} className="space-y-6">
                    <Tabs defaultValue="basic" className="space-y-6">
                        <TabsList>
                            <TabsTrigger value="basic">Grunddaten</TabsTrigger>
                            <TabsTrigger value="pricing">Preise & Steuern</TabsTrigger>
                            <TabsTrigger value="inventory">{t('pages.products.tabInventory')}</TabsTrigger>
                            <TabsTrigger value="advanced">Erweitert</TabsTrigger>
                        </TabsList>

                        {/* Basic Information */}
                        <TabsContent value="basic">
                            <Card>
                                <CardHeader>
                                    <CardTitle>{t('pages.products.basicInfo')}</CardTitle>
                                    <CardDescription>{t('pages.products.basicInfoDesc')}</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-6">
                                    <div className="grid gap-6 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="name">Produktname *</Label>
                                            <Input
                                                id="name"
                                                value={data.name}
                                                onChange={(e) => setData("name", e.target.value)}
                                                placeholder="z.B. Beratungsstunde"
                                                required
                                                className={errors.name ? "border-red-500" : ""}
                                            />
                                            {errors.name && <p className="text-sm text-red-600">{errors.name}</p>}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="sku">SKU / Artikelnummer</Label>
                                            <Input
                                                id="sku"
                                                value={data.sku}
                                                onChange={(e) => setData("sku", e.target.value)}
                                                placeholder="z.B. BERAT-001"
                                                className={errors.sku ? "border-red-500" : ""}
                                            />
                                            {errors.sku && <p className="text-sm text-red-600">{errors.sku}</p>}
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="description">{t('common.description')}</Label>
                                        <Textarea
                                            id="description"
                                            value={data.description}
                                            onChange={(e) => setData("description", e.target.value)}
                                            placeholder="Detaillierte Beschreibung des Produkts..."
                                            rows={4}
                                            className={errors.description ? "border-red-500" : ""}
                                        />
                                        {errors.description && <p className="text-sm text-red-600">{errors.description}</p>}
                                    </div>

                                    <div className="grid gap-6 md:grid-cols-3">
                                        <div className="space-y-2">
                                            <Label htmlFor="category_id">Kategorie</Label>
                                            <Select value={data.category_id} onValueChange={(value) => setData("category_id", value)}>
                                                <SelectTrigger className={errors.category_id ? "border-red-500" : ""}>
                                                    <SelectValue placeholder={t('pages.products.selectCategory')} />
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
                                            {errors.category_id && <p className="text-sm text-red-600">{errors.category_id}</p>}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="unit">Einheit *</Label>
                                            <Select value={data.unit} onValueChange={(value) => setData("unit", value)}>
                                                <SelectTrigger className={errors.unit ? "border-red-500" : ""}>
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {units.map((unit) => (
                                                        <SelectItem key={unit} value={unit}>
                                                            {unit}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            {errors.unit && <p className="text-sm text-red-600">{errors.unit}</p>}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="status">{t('common.status')}</Label>
                                            <Select value={data.status} onValueChange={(value) => setData("status", value)}>
                                                <SelectTrigger className={errors.status ? "border-red-500" : ""}>
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="active">{t('common.active')}</SelectItem>
                                                    <SelectItem value="inactive">{t('common.inactive')}</SelectItem>
                                                    <SelectItem value="discontinued">Eingestellt</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            {errors.status && <p className="text-sm text-red-600">{errors.status}</p>}
                                        </div>
                                    </div>

                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="is_service"
                                            checked={data.is_service}
                                            onCheckedChange={(checked) => setData("is_service", !!checked)}
                                        />
                                        <Label htmlFor="is_service">Dies ist eine Dienstleistung (kein physisches Produkt)</Label>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* Pricing & Tax */}
                        <TabsContent value="pricing">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Preise & Steuern</CardTitle>
                                    <CardDescription>{t('pages.products.pricingDesc')}</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-6">
                                    <div className="grid gap-6 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="price">Verkaufspreis (netto) *</Label>
                                            <div className="relative">
                                                <Input
                                                    id="price"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    value={data.price}
                                                    onChange={(e) => setData("price", Number.parseFloat(e.target.value) || 0)}
                                                    placeholder="0,00"
                                                    className={`pr-12 ${errors.price ? "border-red-500" : ""}`}
                                                    required
                                                />
                                                <span className="absolute right-3 top-2.5 text-sm text-muted-foreground">€</span>
                                            </div>
                                            {errors.price && <p className="text-sm text-red-600">{errors.price}</p>}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="cost_price">Einkaufspreis (optional)</Label>
                                            <div className="relative">
                                                <Input
                                                    id="cost_price"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    value={data.cost_price}
                                                    onChange={(e) => setData("cost_price", Number.parseFloat(e.target.value) || 0)}
                                                    placeholder="0,00"
                                                    className={`pr-12 ${errors.cost_price ? "border-red-500" : ""}`}
                                                />
                                                <span className="absolute right-3 top-2.5 text-sm text-muted-foreground">€</span>
                                            </div>
                                            {errors.cost_price && <p className="text-sm text-red-600">{errors.cost_price}</p>}
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="tax_rate">Steuersatz</Label>
                                        <Select
                                            value={data.tax_rate.toString()}
                                            onValueChange={(value) => setData("tax_rate", Number.parseFloat(value))}
                                        >
                                            <SelectTrigger className={errors.tax_rate ? "border-red-500" : ""}>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {taxRates.map((rate) => (
                                                    <SelectItem key={rate.value} value={rate.value.toString()}>
                                                        {rate.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.tax_rate && <p className="text-sm text-red-600">{errors.tax_rate}</p>}
                                    </div>

                                    {data.price > 0 && (
                                        <div className="rounded-lg bg-muted p-4">
                                            <h4 className="font-medium mb-2">{t('pages.products.priceOverview')}</h4>
                                            <div className="space-y-1 text-sm">
                                                <div className="flex justify-between">
                                                    <span>Netto-Preis:</span>
                                                    <span>
                            {new Intl.NumberFormat("de-DE", { style: "currency", currency: "EUR" }).format(data.price)}
                          </span>
                                                </div>
                                                <div className="flex justify-between">
                                                    <span>MwSt. ({Math.round(data.tax_rate * 100)}%):</span>
                                                    <span>
                            {new Intl.NumberFormat("de-DE", { style: "currency", currency: "EUR" }).format(
                                data.price * data.tax_rate,
                            )}
                          </span>
                                                </div>
                                                <div className="flex justify-between font-medium border-t pt-1">
                                                    <span>Brutto-Preis:</span>
                                                    <span>
                            {new Intl.NumberFormat("de-DE", { style: "currency", currency: "EUR" }).format(
                                data.price * (1 + data.tax_rate),
                            )}
                          </span>
                                                </div>
                                                {data.cost_price > 0 && (
                                                    <div className="flex justify-between text-green-600">
                                                        <span>Gewinnmarge:</span>
                                                        <span>
                              {new Intl.NumberFormat("de-DE", { style: "currency", currency: "EUR" }).format(
                                  data.price - data.cost_price,
                              )}{" "}
                                                            ({Math.round(((data.price - data.cost_price) / data.price) * 100)}%)
                            </span>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* Inventory */}
                        <TabsContent value="inventory">
                            <Card>
                                <CardHeader>
                                    <CardTitle>{t('pages.products.tabInventory')}</CardTitle>
                                    <CardDescription>{t('pages.products.inventoryDesc')}</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-6">
                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="track_stock"
                                            checked={data.track_stock}
                                            onCheckedChange={(checked) => setData("track_stock", !!checked)}
                                        />
                                        <Label htmlFor="track_stock">Lagerbestand verfolgen</Label>
                                    </div>

                                    {data.track_stock && (
                                        <div className="grid gap-6 md:grid-cols-2">
                                            <div className="space-y-2">
                                                <Label htmlFor="stock_quantity">Aktueller Bestand</Label>
                                                <div className="relative">
                                                    <Input
                                                        id="stock_quantity"
                                                        type="number"
                                                        min="0"
                                                        value={data.stock_quantity}
                                                        onChange={(e) => setData("stock_quantity", Number.parseInt(e.target.value) || 0)}
                                                        placeholder="0"
                                                        className={`pr-16 ${errors.stock_quantity ? "border-red-500" : ""}`}
                                                    />
                                                    <span className="absolute right-3 top-2.5 text-sm text-muted-foreground">{data.unit}</span>
                                                </div>
                                                {errors.stock_quantity && <p className="text-sm text-red-600">{errors.stock_quantity}</p>}
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="min_stock_level">Mindestbestand</Label>
                                                <div className="relative">
                                                    <Input
                                                        id="min_stock_level"
                                                        type="number"
                                                        min="0"
                                                        value={data.min_stock_level}
                                                        onChange={(e) => setData("min_stock_level", Number.parseInt(e.target.value) || 0)}
                                                        placeholder="0"
                                                        className={`pr-16 ${errors.min_stock_level ? "border-red-500" : ""}`}
                                                    />
                                                    <span className="absolute right-3 top-2.5 text-sm text-muted-foreground">{data.unit}</span>
                                                </div>
                                                <p className="text-sm text-muted-foreground">
                                                    {t('pages.products.minStockHint')}
                                                </p>
                                                {errors.min_stock_level && <p className="text-sm text-red-600">{errors.min_stock_level}</p>}
                                            </div>
                                        </div>
                                    )}

                                    <div className="space-y-2">
                                        <Label htmlFor="barcode">Barcode / EAN</Label>
                                        <Input
                                            id="barcode"
                                            value={data.barcode}
                                            onChange={(e) => setData("barcode", e.target.value)}
                                            placeholder="z.B. 4012345678901"
                                            className={errors.barcode ? "border-red-500" : ""}
                                        />
                                        {errors.barcode && <p className="text-sm text-red-600">{errors.barcode}</p>}
                                    </div>

                                    {/* Stock History */}
                                    <div className="rounded-lg bg-muted p-4">
                                        <h4 className="font-medium mb-2">Bestandshistorie</h4>
                                        <p className="text-sm text-muted-foreground">
                                            {t('pages.products.stockHistoryFuture')}
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* Advanced */}
                        <TabsContent value="advanced">
                            <Card>
                                <CardHeader>
                                    <CardTitle>{t('settings.advancedSettings')}</CardTitle>
                                    <CardDescription>{t('pages.products.advancedDesc')}</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-6">
                                    <div className="text-center py-8 text-muted-foreground">
                                        <Package className="mx-auto h-12 w-12 mb-4" />
                                        <p>{t('pages.products.advancedFuture')}</p>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </Tabs>

                    {/* Actions */}
                    <div className="flex items-center justify-end space-x-4">
                        <Button variant="outline" asChild>
                            <Link href="/products">{t('common.cancel')}</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Save className="mr-2 h-4 w-4" />
                            {processing ? t('common.saving') : t('pages.invoices.saveChanges')}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    )
}

// Helper function to get field labels in German
function getFieldLabel(field: string): string {
    const labels: Record<string, string> = {
        name: "Produktname",
        description: "Beschreibung",
        unit: "Einheit",
        price: "Verkaufspreis",
        cost_price: "Einkaufspreis",
        category_id: "Kategorie",
        sku: "SKU/Artikelnummer",
        barcode: "Barcode",
        tax_rate: "Steuersatz",
        stock_quantity: "Lagerbestand",
        min_stock_level: "Mindestbestand",
        track_stock: "Lagerbestand verfolgen",
        is_service: "Dienstleistung",
        status: "Status",
    }
    return labels[field] || field
}
