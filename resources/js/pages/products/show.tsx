"use client"

import { Head, Link, router, useForm } from "@inertiajs/react"
import { useState } from "react"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog"
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import {
    ArrowLeft,
    Edit,
    Trash2,
    Package,
    TrendingUp,
    TrendingDown,
    AlertTriangle,
    CheckCircle,
    XCircle,
    Clock,
} from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { User, Product, Category } from "@/types"

interface ProductShowProps {
    user: User
    product: Product & {
        category?: Category
        usage_stats?: {
            total_invoices: number
            total_offers: number
            total_revenue: number
            last_used: string | null
        }
        stock_movements?: Array<{
            id: string
            type: "in" | "out" | "adjustment"
            quantity: number
            reason: string
            notes?: string
            created_at: string
            user_name: string
            warehouse_name?: string
        }>
    }
    warehouses?: Array<{
        id: string
        name: string
        code: string
        is_default: boolean
    }>
    stock_movements?: Array<{
        id: string
        type: "in" | "out" | "adjustment"
        quantity: number
        reason: string
        notes?: string
        created_at: string
        user_name: string
        warehouse_name?: string
    }>
}

export default function ProductShow({ user, product, warehouses = [], stock_movements = [] }: ProductShowProps) {
    const [adjustStockDialogOpen, setAdjustStockDialogOpen] = useState(false)

    // Merge stock_movements from props with product.stock_movements (props takes precedence)
    const allStockMovements = stock_movements.length > 0 ? stock_movements : (product.stock_movements || [])

    const { data: adjustStockData, setData: setAdjustStockData, post: adjustStockPost, processing: adjustingStock, errors: adjustStockErrors, reset: resetAdjustStock } = useForm({
        warehouse_id: warehouses.find(w => w.is_default)?.id || warehouses[0]?.id || "",
        adjustment_type: "set",
        quantity: product.stock_quantity || 0,
        reason: "",
        notes: "",
    })

    const deleteProduct = () => {
        if (
            confirm(
                "Sind Sie sicher, dass Sie dieses Produkt löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.",
            )
        ) {
            router.delete(`/products/${product.id}`)
        }
    }

    const handleAdjustStock = (e: React.FormEvent) => {
        e.preventDefault()
        adjustStockPost(`/products/${product.id}/adjust-stock`, {
            onSuccess: () => {
                setAdjustStockDialogOpen(false)
                resetAdjustStock()
            },
        })
    }

    const getStockStatusBadge = () => {
        if (!product.track_stock) {
            return <Badge variant="outline">Nicht verfolgt</Badge>
        }

        if (product.stock_quantity <= 0) {
            return (
                <Badge variant="destructive">
                    <XCircle className="mr-1 h-3 w-3" />
                    Ausverkauft
                </Badge>
            )
        }

        if (product.stock_quantity <= product.min_stock_level) {
            return (
                <Badge variant="secondary">
                    <AlertTriangle className="mr-1 h-3 w-3" />
                    Niedriger Bestand
                </Badge>
            )
        }

        return (
            <Badge variant="default">
                <CheckCircle className="mr-1 h-3 w-3" />
                Auf Lager
            </Badge>
        )
    }

    const getStatusBadge = () => {
        const variants = {
            active: "default" as const,
            inactive: "secondary" as const,
            discontinued: "destructive" as const,
        }

        const labels = {
            active: "Aktiv",
            inactive: "Inaktiv",
            discontinued: "Eingestellt",
        }

        return (
            <Badge variant={variants[product.status as keyof typeof variants]}>
                {labels[product.status as keyof typeof labels]}
            </Badge>
        )
    }

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat("de-DE", {
            style: "currency",
            currency: "EUR",
        }).format(amount)
    }

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString("de-DE", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        })
    }

    const profitMargin = product.cost_price ? ((product.price - product.cost_price) / product.price) * 100 : 0

    return (
        <AppLayout user={user}>
            <Head title={product.name} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Button variant="ghost" asChild>
                            <Link href="/products">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Zurück
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-1xl font-bold tracking-tight">{product.name}</h1>
                            <p className="text-muted-foreground">
                                {product.number} {product.sku && `• SKU: ${product.sku}`}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-2">
                        <Button variant="outline" asChild>
                            <Link href={`/products/${product.id}/edit`}>
                                <Edit className="mr-2 h-4 w-4" />
                                Bearbeiten
                            </Link>
                        </Button>
                        <Button variant="destructive" onClick={deleteProduct}>
                            <Trash2 className="mr-2 h-4 w-4" />
                            Löschen
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-3">
                    {/* Main Information */}
                    <div className="md:col-span-2 space-y-6">
                        <Tabs defaultValue="details" className="space-y-6">
                            <TabsList>
                                <TabsTrigger value="details">Details</TabsTrigger>
                                <TabsTrigger value="inventory">Lager</TabsTrigger>
                                <TabsTrigger value="usage">Verwendung</TabsTrigger>
                            </TabsList>

                            {/* Product Details */}
                            <TabsContent value="details">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Produktdetails</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-6">
                                        <div className="grid gap-4 md:grid-cols-2">
                                            <div>
                                                <label className="text-sm font-medium text-muted-foreground">Name</label>
                                                <p className="text-lg font-medium">{product.name}</p>
                                            </div>
                                            <div>
                                                <label className="text-sm font-medium text-muted-foreground">Status</label>
                                                <div className="mt-1">{getStatusBadge()}</div>
                                            </div>
                                        </div>

                                        {product.description && (
                                            <div>
                                                <label className="text-sm font-medium text-muted-foreground">Beschreibung</label>
                                                <p className="mt-1 text-sm">{product.description}</p>
                                            </div>
                                        )}

                                        <div className="grid gap-4 md:grid-cols-3">
                                            <div>
                                                <label className="text-sm font-medium text-muted-foreground">Kategorie</label>
                                                <p className="mt-1">
                                                    {product.category ? (
                                                        <Badge variant="outline">{product.category.name}</Badge>
                                                    ) : (
                                                        <span className="text-muted-foreground">Keine Kategorie</span>
                                                    )}
                                                </p>
                                            </div>
                                            <div>
                                                <label className="text-sm font-medium text-muted-foreground">Einheit</label>
                                                <p className="mt-1">{product.unit}</p>
                                            </div>
                                            <div>
                                                <label className="text-sm font-medium text-muted-foreground">Typ</label>
                                                <p className="mt-1">
                                                    <Badge variant={product.is_service ? "secondary" : "default"}>
                                                        {product.is_service ? "Dienstleistung" : "Produkt"}
                                                    </Badge>
                                                </p>
                                            </div>
                                        </div>

                                        {product.barcode && (
                                            <div>
                                                <label className="text-sm font-medium text-muted-foreground">Barcode / EAN</label>
                                                <p className="mt-1 font-mono">{product.barcode}</p>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            </TabsContent>

                            {/* Inventory */}
                            <TabsContent value="inventory">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Lagerbestand</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-6">
                                        <div className="grid gap-4 md:grid-cols-2">
                                            <div>
                                                <label className="text-sm font-medium text-muted-foreground">Bestandsverfolgung</label>
                                                <p className="mt-1">
                                                    <Badge variant={product.track_stock ? "default" : "secondary"}>
                                                        {product.track_stock ? "Aktiviert" : "Deaktiviert"}
                                                    </Badge>
                                                </p>
                                            </div>
                                            <div>
                                                <label className="text-sm font-medium text-muted-foreground">Status</label>
                                                <div className="mt-1">{getStockStatusBadge()}</div>
                                            </div>
                                        </div>

                                        {product.track_stock && (
                                            <>
                                                <div className="grid gap-4 md:grid-cols-2">
                                                    <div>
                                                        <label className="text-sm font-medium text-muted-foreground">Aktueller Bestand</label>
                                                        <p className="text-2xl font-bold">
                                                            {product.stock_quantity} {product.unit}
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <label className="text-sm font-medium text-muted-foreground">Mindestbestand</label>
                                                        <p className="text-2xl font-bold">
                                                            {product.min_stock_level} {product.unit}
                                                        </p>
                                                    </div>
                                                </div>

                                                {warehouses.length > 0 && (
                                                    <div className="flex justify-end">
                                                        <Button onClick={() => setAdjustStockDialogOpen(true)}>
                                                            <Package className="mr-2 h-4 w-4" />
                                                            Bestand anpassen
                                                        </Button>
                                                    </div>
                                                )}

                                                {/* Stock Movements */}
                                                {allStockMovements.length > 0 && (
                                                    <div>
                                                        <h4 className="font-medium mb-3">Letzte Bestandsbewegungen</h4>
                                                        <div className="space-y-2">
                                                            {allStockMovements.slice(0, 10).map((movement) => (
                                                                <div
                                                                    key={movement.id}
                                                                    className="flex items-center justify-between p-3 rounded-lg bg-muted"
                                                                >
                                                                    <div className="flex items-center space-x-3">
                                                                        {movement.type === "in" && <TrendingUp className="h-4 w-4 text-green-600" />}
                                                                        {movement.type === "out" && <TrendingDown className="h-4 w-4 text-red-600" />}
                                                                        {movement.type === "adjustment" && <Package className="h-4 w-4 text-blue-600" />}
                                                                        <div>
                                                                            <p className="text-sm font-medium">
                                                                                {movement.type === "in" ? "+" : movement.type === "out" ? "-" : "±"}
                                                                                {Math.abs(movement.quantity)} {product.unit}
                                                                            </p>
                                                                            <p className="text-xs text-muted-foreground">{movement.reason}</p>
                                                                            {movement.warehouse_name && (
                                                                                <p className="text-xs text-muted-foreground">Lager: {movement.warehouse_name}</p>
                                                                            )}
                                                                        </div>
                                                                    </div>
                                                                    <div className="text-right">
                                                                        <p className="text-xs text-muted-foreground">{formatDate(movement.created_at)}</p>
                                                                        <p className="text-xs text-muted-foreground">{movement.user_name}</p>
                                                                    </div>
                                                                </div>
                                                            ))}
                                                        </div>
                                                    </div>
                                                )}
                                            </>
                                        )}
                                    </CardContent>
                                </Card>

                                {/* Stock Adjustment Dialog */}
                                <Dialog open={adjustStockDialogOpen} onOpenChange={setAdjustStockDialogOpen}>
                                    <DialogContent className="sm:max-w-[500px]">
                                        <DialogHeader>
                                            <DialogTitle>Bestand anpassen</DialogTitle>
                                            <DialogDescription>
                                                Passen Sie den Bestand für {product.name} an.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <form onSubmit={handleAdjustStock}>
                                            <div className="space-y-4 py-4">
                                                <div className="space-y-2">
                                                    <Label htmlFor="warehouse_id">Lager *</Label>
                                                    <Select
                                                        value={adjustStockData.warehouse_id}
                                                        onValueChange={(value) => setAdjustStockData("warehouse_id", value)}
                                                    >
                                                        <SelectTrigger id="warehouse_id">
                                                            <SelectValue placeholder="Lager auswählen" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {warehouses.map((warehouse) => (
                                                                <SelectItem key={warehouse.id} value={warehouse.id}>
                                                                    {warehouse.name} {warehouse.is_default && "(Standard)"}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                    {adjustStockErrors.warehouse_id && (
                                                        <p className="text-sm text-red-500">{adjustStockErrors.warehouse_id}</p>
                                                    )}
                                                </div>

                                                <div className="space-y-2">
                                                    <Label htmlFor="adjustment_type">Anpassungstyp *</Label>
                                                    <Select
                                                        value={adjustStockData.adjustment_type}
                                                        onValueChange={(value) => setAdjustStockData("adjustment_type", value as "set" | "add" | "subtract")}
                                                    >
                                                        <SelectTrigger id="adjustment_type">
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="set">Bestand setzen</SelectItem>
                                                            <SelectItem value="add">Bestand hinzufügen</SelectItem>
                                                            <SelectItem value="subtract">Bestand abziehen</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                    {adjustStockErrors.adjustment_type && (
                                                        <p className="text-sm text-red-500">{adjustStockErrors.adjustment_type}</p>
                                                    )}
                                                </div>

                                                <div className="space-y-2">
                                                    <Label htmlFor="quantity">Menge *</Label>
                                                    <Input
                                                        id="quantity"
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        value={adjustStockData.quantity}
                                                        onChange={(e) => setAdjustStockData("quantity", parseFloat(e.target.value) || 0)}
                                                    />
                                                    <p className="text-xs text-muted-foreground">Einheit: {product.unit}</p>
                                                    {adjustStockErrors.quantity && (
                                                        <p className="text-sm text-red-500">{adjustStockErrors.quantity}</p>
                                                    )}
                                                </div>

                                                <div className="space-y-2">
                                                    <Label htmlFor="reason">Grund *</Label>
                                                    <Input
                                                        id="reason"
                                                        value={adjustStockData.reason}
                                                        onChange={(e) => setAdjustStockData("reason", e.target.value)}
                                                        placeholder="z.B. Inventur, Defekt, Lieferung..."
                                                    />
                                                    {adjustStockErrors.reason && (
                                                        <p className="text-sm text-red-500">{adjustStockErrors.reason}</p>
                                                    )}
                                                </div>

                                                <div className="space-y-2">
                                                    <Label htmlFor="notes">Notizen (optional)</Label>
                                                    <Textarea
                                                        id="notes"
                                                        value={adjustStockData.notes}
                                                        onChange={(e) => setAdjustStockData("notes", e.target.value)}
                                                        placeholder="Zusätzliche Informationen..."
                                                        rows={3}
                                                    />
                                                    {adjustStockErrors.notes && (
                                                        <p className="text-sm text-red-500">{adjustStockErrors.notes}</p>
                                                    )}
                                                </div>
                                            </div>
                                            <DialogFooter>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    onClick={() => {
                                                        setAdjustStockDialogOpen(false)
                                                        resetAdjustStock()
                                                    }}
                                                >
                                                    Abbrechen
                                                </Button>
                                                <Button type="submit" disabled={adjustingStock}>
                                                    {adjustingStock ? "Speichern..." : "Speichern"}
                                                </Button>
                                            </DialogFooter>
                                        </form>
                                    </DialogContent>
                                </Dialog>
                            </TabsContent>

                            {/* Usage Statistics */}
                            <TabsContent value="usage">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Verwendungsstatistiken</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        {product.usage_stats ? (
                                            <div className="space-y-6">
                                                <div className="grid gap-4 md:grid-cols-3">
                                                    <div className="text-center">
                                                        <p className="text-2xl font-bold">{product.usage_stats.total_invoices}</p>
                                                        <p className="text-sm text-muted-foreground">Rechnungen</p>
                                                    </div>
                                                    <div className="text-center">
                                                        <p className="text-2xl font-bold">{product.usage_stats.total_offers}</p>
                                                        <p className="text-sm text-muted-foreground">Angebote</p>
                                                    </div>
                                                    <div className="text-center">
                                                        <p className="text-2xl font-bold">{formatCurrency(product.usage_stats.total_revenue)}</p>
                                                        <p className="text-sm text-muted-foreground">Gesamtumsatz</p>
                                                    </div>
                                                </div>
                                                {product.usage_stats.last_used && (
                                                    <div>
                                                        <label className="text-sm font-medium text-muted-foreground">Zuletzt verwendet</label>
                                                        <p className="mt-1">{formatDate(product.usage_stats.last_used)}</p>
                                                    </div>
                                                )}
                                            </div>
                                        ) : (
                                            <div className="text-center py-8 text-muted-foreground">
                                                <Clock className="mx-auto h-12 w-12 mb-4" />
                                                <p>Dieses Produkt wurde noch nicht verwendet.</p>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            </TabsContent>
                        </Tabs>
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        {/* Pricing Information */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Preise & Steuern</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">Verkaufspreis (netto)</label>
                                    <p className="text-xl font-bold">{formatCurrency(product.price)}</p>
                                </div>

                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">MwSt. ({product.tax_rate}%)</label>
                                    <p className="font-medium">{formatCurrency((product.price * product.tax_rate) / 100)}</p>
                                </div>

                                <div className="border-t pt-2">
                                    <label className="text-sm font-medium text-muted-foreground">Verkaufspreis (brutto)</label>
                                    <p className="text-xl font-bold">{formatCurrency(product.price * (1 + product.tax_rate / 100))}</p>
                                </div>

                                {product.cost_price > 0 && (
                                    <>
                                        <div className="border-t pt-2">
                                            <label className="text-sm font-medium text-muted-foreground">Einkaufspreis</label>
                                            <p className="font-medium">{formatCurrency(product.cost_price)}</p>
                                        </div>

                                        <div>
                                            <label className="text-sm font-medium text-muted-foreground">Gewinnmarge</label>
                                            <p className="font-medium text-green-600">
                                                {formatCurrency(product.price - product.cost_price)} ({Math.round(profitMargin)}%)
                                            </p>
                                        </div>
                                    </>
                                )}
                            </CardContent>
                        </Card>

                        {/* Quick Actions */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Schnellaktionen</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                <Button className="w-full" asChild>
                                    <Link href={`/invoices/create?product=${product.id}`}>Rechnung erstellen</Link>
                                </Button>
                                <Button variant="outline" className="w-full bg-transparent" asChild>
                                    <Link href={`/offers/create?product=${product.id}`}>Angebot erstellen</Link>
                                </Button>
                            </CardContent>
                        </Card>

                        {/* Product Information */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Produktinformationen</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Erstellt:</span>
                                    <span>{formatDate(product.created_at)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Aktualisiert:</span>
                                    <span>{formatDate(product.updated_at)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Produkt-ID:</span>
                                    <span className="font-mono text-xs">{product.id}</span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    )
}
