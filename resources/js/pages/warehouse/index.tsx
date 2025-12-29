"use client"

import { useState } from "react"
import { Head, Link, router, usePage } from "@inertiajs/react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Badge } from "@/components/ui/badge"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Warehouse, Plus, Search, Filter, Edit, Eye, Trash2, Package, TrendingUp, AlertTriangle } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { PaginatedResponse } from "@/types"

interface Warehouse {
    id: string
    code: string
    name: string
    description?: string
    city?: string
    country?: string
    is_default: boolean
    is_active: boolean
    warehouse_stocks_count?: number
    stock_movements_count?: number
    created_at: string
    updated_at: string
}

interface WarehousesIndexProps {
    warehouses: PaginatedResponse<Warehouse>
    stats: {
        total_warehouses: number
        active_warehouses: number
        total_stock_value: number
        low_stock_items: number
    }
    filters: {
        search?: string
        status?: string
    }
}

export default function WarehousesIndex({ warehouses, stats, filters }: WarehousesIndexProps) {
    const { props } = usePage()
    const user = (props as any).auth?.user || (props as any).user
    const [search, setSearch] = useState(filters.search || "")
    const [selectedStatus, setSelectedStatus] = useState(filters.status || "all")

    const handleSearch = () => {
        router.get(
            "/warehouses",
            {
                search,
                status: selectedStatus !== "all" ? selectedStatus : undefined,
            },
            { preserveState: true },
        )
    }

    const handleReset = () => {
        setSearch("")
        setSelectedStatus("all")
        router.get("/warehouses")
    }

    const deleteWarehouse = (id: string, name: string) => {
        if (confirm(`Sind Sie sicher, dass Sie das Lager "${name}" löschen möchten?`)) {
            router.delete(`/warehouses/${id}`)
        }
    }

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat("de-DE", {
            style: "currency",
            currency: "EUR",
        }).format(amount)
    }

    const getStatusBadge = (isActive: boolean) => {
        if (isActive) {
            return <Badge variant="default">Aktiv</Badge>
        }
        return <Badge variant="secondary">Inaktiv</Badge>
    }

    return (
        <AppLayout user={user}>
            <Head title="Lagerbestand" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight dark:text-gray-100">Lagerbestand</h1>
                        <p className="text-muted-foreground">Verwalten Sie Ihre Lager und Bestände</p>
                    </div>
                    <Button asChild>
                        <Link href="/warehouses/create">
                            <Plus className="mr-2 h-4 w-4" />
                            Neues Lager
                        </Link>
                    </Button>
                </div>

                {/* Stats Cards */}
                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Gesamt Lager</CardTitle>
                            <Warehouse className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_warehouses}</div>
                            <p className="text-xs text-muted-foreground">
                                {stats.active_warehouses} aktiv
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Aktive Lager</CardTitle>
                            <Package className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.active_warehouses}</div>
                            <p className="text-xs text-muted-foreground">In Betrieb</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Gesamtwert</CardTitle>
                            <TrendingUp className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(stats.total_stock_value || 0)}</div>
                            <p className="text-xs text-muted-foreground">Lagerwert</p>
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
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle>Filter</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-col gap-4 md:flex-row md:items-end">
                            <div className="flex-1">
                                <label className="text-sm font-medium mb-2 block">Suche</label>
                                <div className="relative">
                                    <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        placeholder="Name oder Stadt suchen..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        onKeyDown={(e) => {
                                            if (e.key === "Enter") {
                                                handleSearch()
                                            }
                                        }}
                                        className="pl-8"
                                    />
                                </div>
                            </div>

                            <div className="w-full md:w-[200px]">
                                <label className="text-sm font-medium mb-2 block">Status</label>
                                <Select value={selectedStatus} onValueChange={setSelectedStatus}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Alle</SelectItem>
                                        <SelectItem value="active">Aktiv</SelectItem>
                                        <SelectItem value="inactive">Inaktiv</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex gap-2">
                                <Button onClick={handleSearch}>
                                    <Search className="mr-2 h-4 w-4" />
                                    Suchen
                                </Button>
                                <Button variant="outline" onClick={handleReset}>
                                    <Filter className="mr-2 h-4 w-4" />
                                    Zurücksetzen
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Warehouses Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Lager ({warehouses.total})</CardTitle>
                        <CardDescription>Übersicht aller Lager</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {warehouses.data.length === 0 ? (
                            <div className="text-center py-12">
                                <Warehouse className="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                                <h3 className="text-lg font-semibold mb-2">Keine Lager gefunden</h3>
                                <p className="text-muted-foreground mb-4">
                                    {search || selectedStatus !== "all"
                                        ? "Versuchen Sie, die Filter anzupassen."
                                        : "Erstellen Sie Ihr erstes Lager, um zu beginnen."}
                                </p>
                                {!search && selectedStatus === "all" && (
                                    <Button asChild>
                                        <Link href="/warehouses/create">
                                            <Plus className="mr-2 h-4 w-4" />
                                            Neues Lager erstellen
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        ) : (
                            <>
                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Code</TableHead>
                                                <TableHead>Name</TableHead>
                                                <TableHead>Stadt</TableHead>
                                                <TableHead>Produkte</TableHead>
                                                <TableHead>Bewegungen</TableHead>
                                                <TableHead>Status</TableHead>
                                                <TableHead className="text-right">Aktionen</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {warehouses.data.map((warehouse) => (
                                                <TableRow key={warehouse.id}>
                                                    <TableCell className="font-mono text-sm">
                                                        {warehouse.code}
                                                        {warehouse.is_default && (
                                                            <Badge variant="outline" className="ml-2">
                                                                Standard
                                                            </Badge>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="font-medium">{warehouse.name}</TableCell>
                                                    <TableCell>{warehouse.city || "-"}</TableCell>
                                                    <TableCell>{warehouse.warehouse_stocks_count || 0}</TableCell>
                                                    <TableCell>{warehouse.stock_movements_count || 0}</TableCell>
                                                    <TableCell>{getStatusBadge(warehouse.is_active)}</TableCell>
                                                    <TableCell className="text-right">
                                                        <div className="flex justify-end gap-2">
                                                            <Button variant="ghost" size="sm" asChild>
                                                                <Link href={`/warehouses/${warehouse.id}`}>
                                                                    <Eye className="h-4 w-4" />
                                                                </Link>
                                                            </Button>
                                                            <Button variant="ghost" size="sm" asChild>
                                                                <Link href={`/warehouses/${warehouse.id}/edit`}>
                                                                    <Edit className="h-4 w-4" />
                                                                </Link>
                                                            </Button>
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => deleteWarehouse(warehouse.id, warehouse.name)}
                                                            >
                                                                <Trash2 className="h-4 w-4 text-destructive" />
                                                            </Button>
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>

                                {/* Pagination */}
                                {warehouses.last_page > 1 && (
                                    <div className="flex items-center justify-between mt-4">
                                        <div className="text-sm text-muted-foreground">
                                            Zeige {warehouses.from} bis {warehouses.to} von {warehouses.total} Lagern
                                        </div>
                                        <div className="flex gap-2">
                                            {warehouses.current_page > 1 && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => router.get(warehouses.prev_page_url || "/warehouses")}
                                                >
                                                    Zurück
                                                </Button>
                                            )}
                                            {warehouses.current_page < warehouses.last_page && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => router.get(warehouses.next_page_url || "/warehouses")}
                                                >
                                                    Weiter
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    )
}
