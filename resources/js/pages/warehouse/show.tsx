"use client"

import { Head, Link, router, usePage } from "@inertiajs/react"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { ArrowLeft, Edit, Package, TrendingUp, AlertTriangle, CheckCircle, XCircle, Warehouse as WarehouseIcon } from "lucide-react"
import AppLayout from "@/layouts/app-layout"

interface Warehouse {
    id: string
    code: string
    name: string
    description?: string
    street?: string
    street_number?: string
    postal_code?: string
    city?: string
    state?: string
    country?: string
    contact_person?: string
    phone?: string
    email?: string
    is_default: boolean
    is_active: boolean
    created_at: string
    updated_at: string
    warehouse_stocks?: Array<{
        id: string
        product: {
            id: string
            name: string
            number: string
            unit: string
        }
        quantity: number
        reserved_quantity: number
        average_cost: number
    }>
}

interface StockMovement {
    id: string
    type: string
    quantity: number
    reason?: string
    created_at: string
    product?: {
        id: string
        name: string
        number: string
    }
    user?: {
        id: string
        name: string
    }
}

interface WarehouseShowProps {
    warehouse: Warehouse
    stats: {
        total_products: number
        total_stock_value: number
        low_stock_items: number
        out_of_stock_items: number
    }
    recentMovements?: StockMovement[]
    lowStockItems?: Array<{
        id: string
        quantity: number
        product: {
            id: string
            name: string
            number: string
            unit: string
            min_stock_level: number
        }
    }>
}

export default function WarehouseShow() {
    const { props } = usePage<WarehouseShowProps>()
    const { warehouse, stats, recentMovements = [], lowStockItems = [] } = props
    const user = (props as any).auth?.user || (props as any).user

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

    const getMovementIcon = (type: string) => {
        switch (type) {
            case "in":
                return <TrendingUp className="h-4 w-4 text-green-600" />
            case "out":
                return <TrendingUp className="h-4 w-4 text-red-600 rotate-180" />
            case "adjustment":
                return <Package className="h-4 w-4 text-blue-600" />
            default:
                return <Package className="h-4 w-4 text-gray-600" />
        }
    }

    const getMovementLabel = (type: string) => {
        switch (type) {
            case "in":
                return "Eingang"
            case "out":
                return "Ausgang"
            case "adjustment":
                return "Korrektur"
            default:
                return type
        }
    }

    return (
        <AppLayout user={user}>
            <Head title={`Lager: ${warehouse.name}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Button variant="ghost" asChild>
                            <Link href="/warehouses">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Zurück
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">{warehouse.name}</h1>
                            <p className="text-muted-foreground">
                                {warehouse.code} {warehouse.is_default && <Badge variant="outline" className="ml-2">Standard</Badge>}
                            </p>
                        </div>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={`/warehouses/${warehouse.id}/edit`}>
                            <Edit className="mr-2 h-4 w-4" />
                            Bearbeiten
                        </Link>
                    </Button>
                </div>

                {/* Stats Cards */}
                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Produkte</CardTitle>
                            <Package className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_products}</div>
                            <p className="text-xs text-muted-foreground">Mit Bestand</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Lagerwert</CardTitle>
                            <TrendingUp className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(stats.total_stock_value || 0)}</div>
                            <p className="text-xs text-muted-foreground">Gesamtwert</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Niedrige Bestände</CardTitle>
                            <AlertTriangle className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.low_stock_items}</div>
                            <p className="text-xs text-muted-foreground">Artikel</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Ausverkauft</CardTitle>
                            <XCircle className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.out_of_stock_items}</div>
                            <p className="text-xs text-muted-foreground">Artikel</p>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    {/* Warehouse Details */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Lagereinstellungen</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <label className="text-sm font-medium text-muted-foreground">Status</label>
                                <div className="mt-1">
                                    {warehouse.is_active ? (
                                        <Badge variant="default">
                                            <CheckCircle className="mr-1 h-3 w-3" />
                                            Aktiv
                                        </Badge>
                                    ) : (
                                        <Badge variant="secondary">
                                            <XCircle className="mr-1 h-3 w-3" />
                                            Inaktiv
                                        </Badge>
                                    )}
                                </div>
                            </div>

                            {warehouse.description && (
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">Beschreibung</label>
                                    <p className="mt-1 text-sm">{warehouse.description}</p>
                                </div>
                            )}

                            {(warehouse.street || warehouse.city) && (
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">Adresse</label>
                                    <p className="mt-1 text-sm">
                                        {warehouse.street} {warehouse.street_number}
                                        {warehouse.postal_code && warehouse.city && (
                                            <>
                                                <br />
                                                {warehouse.postal_code} {warehouse.city}
                                            </>
                                        )}
                                        {warehouse.country && (
                                            <>
                                                <br />
                                                {warehouse.country}
                                            </>
                                        )}
                                    </p>
                                </div>
                            )}

                            {(warehouse.contact_person || warehouse.phone || warehouse.email) && (
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">Kontakt</label>
                                    <div className="mt-1 space-y-1 text-sm">
                                        {warehouse.contact_person && <p>{warehouse.contact_person}</p>}
                                        {warehouse.phone && <p>{warehouse.phone}</p>}
                                        {warehouse.email && <p>{warehouse.email}</p>}
                                    </div>
                                </div>
                            )}

                            <div className="grid gap-2 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Erstellt:</span>
                                    <span>{formatDate(warehouse.created_at)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Aktualisiert:</span>
                                    <span>{formatDate(warehouse.updated_at)}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Low Stock Items */}
                    {lowStockItems.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Niedrige Bestände</CardTitle>
                                <CardDescription>Artikel die nachbestellt werden sollten</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-2">
                                    {lowStockItems.map((item) => (
                                        <div
                                            key={item.id}
                                            className="flex items-center justify-between p-3 rounded-lg bg-muted"
                                        >
                                            <div>
                                                <p className="text-sm font-medium">{item.product.name}</p>
                                                <p className="text-xs text-muted-foreground">
                                                    {item.product.number}
                                                </p>
                                            </div>
                                            <div className="text-right">
                                                <p className="text-sm font-bold text-orange-600">
                                                    {item.quantity} {item.product.unit}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    Min: {item.product.min_stock_level}
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>

                {/* Recent Stock Movements */}
                {recentMovements.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Letzte Bestandsbewegungen</CardTitle>
                            <CardDescription>Die letzten 10 Bewegungen in diesem Lager</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Typ</TableHead>
                                        <TableHead>Produkt</TableHead>
                                        <TableHead>Menge</TableHead>
                                        <TableHead>Grund</TableHead>
                                        <TableHead>Benutzer</TableHead>
                                        <TableHead>Datum</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {recentMovements.map((movement) => (
                                        <TableRow key={movement.id}>
                                            <TableCell>
                                                <div className="flex items-center space-x-2">
                                                    {getMovementIcon(movement.type)}
                                                    <span className="text-sm">{getMovementLabel(movement.type)}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {movement.product ? (
                                                    <div>
                                                        <p className="text-sm font-medium">{movement.product.name}</p>
                                                        <p className="text-xs text-muted-foreground">
                                                            {movement.product.number}
                                                        </p>
                                                    </div>
                                                ) : (
                                                    <span className="text-muted-foreground">-</span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <span className={movement.quantity > 0 ? "text-green-600" : "text-red-600"}>
                                                    {movement.quantity > 0 ? "+" : ""}
                                                    {movement.quantity}
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-sm">{movement.reason || "-"}</TableCell>
                                            <TableCell className="text-sm">
                                                {movement.user?.name || "Unbekannt"}
                                            </TableCell>
                                            <TableCell className="text-sm text-muted-foreground">
                                                {formatDate(movement.created_at)}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    )
}
