"use client"

import { Head, Link, router } from "@inertiajs/react"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { ArrowLeft, Edit, Trash2, Folder, FolderOpen, Package, TrendingUp, Eye, Plus } from 'lucide-react'
import AppLayout from "@/layouts/app-layout"
import type { User, Category, Product } from "@/types"

interface CategoryShowProps {
    user: User
    category: Category & {
        parent?: Category
        children?: Category[]
        products?: Product[]
    }
    stats: {
        total_products: number
        active_products: number
        total_value: number
        low_stock_products: number
    }
}

export default function CategoryShow({ user, category, stats }: CategoryShowProps) {
    const deleteCategory = () => {
        if (
            confirm(
                "Sind Sie sicher, dass Sie diese Kategorie löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.",
            )
        ) {
            router.delete(`/categories/${category.id}`)
        }
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
        })
    }

    const getStatusBadge = (status: string) => {
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

        return <Badge variant={variants[status as keyof typeof variants]}>{labels[status as keyof typeof labels]}</Badge>
    }

    return (
        <AppLayout user={user}>
            <Head title={category.name} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Button variant="ghost" asChild>
                            <Link href="/categories">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Zurück
                            </Link>
                        </Button>
                        <div className="flex items-center space-x-3">
                            {category.color && (
                                <div className="w-6 h-6 rounded-full border" style={{ backgroundColor: category.color }} />
                            )}
                            <div>
                                <h1 className="text-3xl font-bold tracking-tight">{category.name}</h1>
                                <p className="text-muted-foreground">
                                    {category.parent ? `Unterkategorie von ${category.parent.name}` : "Hauptkategorie"}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div className="flex items-center space-x-2">
                        <Button variant="outline" asChild>
                            <Link href={`/categories/${category.id}/edit`}>
                                <Edit className="mr-2 h-4 w-4" />
                                Bearbeiten
                            </Link>
                        </Button>
                        <Button variant="destructive" onClick={deleteCategory}>
                            <Trash2 className="mr-2 h-4 w-4" />
                            Löschen
                        </Button>
                    </div>
                </div>

                {/* Statistics Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Gesamt Produkte</CardTitle>
                            <Package className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_products}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Aktive Produkte</CardTitle>
                            <TrendingUp className="h-4 w-4 text-green-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.active_products}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Gesamtwert</CardTitle>
                            <Package className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(stats.total_value)}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Niedriger Bestand</CardTitle>
                            <Package className="h-4 w-4 text-orange-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.low_stock_products}</div>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-6 md:grid-cols-3">
                    {/* Main Content */}
                    <div className="md:col-span-2 space-y-6">
                        <Tabs defaultValue="products" className="space-y-6">
                            <TabsList>
                                <TabsTrigger value="products">Produkte</TabsTrigger>
                                <TabsTrigger value="subcategories">Unterkategorien</TabsTrigger>
                            </TabsList>

                            {/* Products */}
                            <TabsContent value="products">
                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between">
                                        <CardTitle>Produkte in dieser Kategorie</CardTitle>
                                        <Button size="sm" asChild>
                                            <Link href={`/products/create?category=${category.id}`}>
                                                <Plus className="mr-2 h-4 w-4" />
                                                Neues Produkt
                                            </Link>
                                        </Button>
                                    </CardHeader>
                                    <CardContent>
                                        {category.products && category.products.length > 0 ? (
                                            <div className="overflow-x-auto">
                                                <Table>
                                                    <TableHeader>
                                                        <TableRow>
                                                            <TableHead>Name</TableHead>
                                                            <TableHead>Preis</TableHead>
                                                            <TableHead>Bestand</TableHead>
                                                            <TableHead>Status</TableHead>
                                                            <TableHead className="text-right">Aktionen</TableHead>
                                                        </TableRow>
                                                    </TableHeader>
                                                    <TableBody>
                                                        {category.products.map((product) => (
                                                            <TableRow key={product.id}>
                                                                <TableCell>
                                                                    <div>
                                                                        <div className="font-medium">{product.name}</div>
                                                                        <div className="text-sm text-muted-foreground">{product.number}</div>
                                                                    </div>
                                                                </TableCell>
                                                                <TableCell>{formatCurrency(product.price)}</TableCell>
                                                                <TableCell>
                                                                    {product.track_stock ? (
                                                                        <span>
                                      {product.stock_quantity} {product.unit}
                                    </span>
                                                                    ) : (
                                                                        <span className="text-muted-foreground">Nicht verfolgt</span>
                                                                    )}
                                                                </TableCell>
                                                                <TableCell>{getStatusBadge(product.status)}</TableCell>
                                                                <TableCell className="text-right">
                                                                    <Button variant="ghost" size="sm" asChild>
                                                                        <Link href={`/products/${product.id}`}>
                                                                            <Eye className="h-4 w-4" />
                                                                        </Link>
                                                                    </Button>
                                                                </TableCell>
                                                            </TableRow>
                                                        ))}
                                                    </TableBody>
                                                </Table>
                                            </div>
                                        ) : (
                                            <div className="text-center py-8 text-muted-foreground">
                                                <Package className="mx-auto h-12 w-12 mb-4" />
                                                <p>Keine Produkte in dieser Kategorie.</p>
                                                <Button className="mt-4" asChild>
                                                    <Link href={`/products/create?category=${category.id}`}>
                                                        <Plus className="mr-2 h-4 w-4" />
                                                        Erstes Produkt hinzufügen
                                                    </Link>
                                                </Button>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            </TabsContent>

                            {/* Subcategories */}
                            <TabsContent value="subcategories">
                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between">
                                        <CardTitle>Unterkategorien</CardTitle>
                                        <Button size="sm" asChild>
                                            <Link href={`/categories/create?parent=${category.id}`}>
                                                <Plus className="mr-2 h-4 w-4" />
                                                Neue Unterkategorie
                                            </Link>
                                        </Button>
                                    </CardHeader>
                                    <CardContent>
                                        {category.children && category.children.length > 0 ? (
                                            <div className="space-y-3">
                                                {category.children.map((child) => (
                                                    <div key={child.id} className="flex items-center justify-between p-3 rounded-lg border">
                                                        <div className="flex items-center space-x-3">
                                                            {child.color && (
                                                                <div className="w-4 h-4 rounded-full border" style={{ backgroundColor: child.color }} />
                                                            )}
                                                            <FolderOpen className="h-4 w-4 text-blue-600" />
                                                            <div>
                                                                <div className="font-medium">{child.name}</div>
                                                                {child.description && (
                                                                    <div className="text-sm text-muted-foreground">{child.description}</div>
                                                                )}
                                                            </div>
                                                        </div>
                                                        <div className="flex items-center space-x-2">
                                                            <Badge variant={child.is_active ? "default" : "secondary"}>
                                                                {child.is_active ? "Aktiv" : "Inaktiv"}
                                                            </Badge>
                                                            <Button variant="ghost" size="sm" asChild>
                                                                <Link href={`/categories/${child.id}`}>
                                                                    <Eye className="h-4 w-4" />
                                                                </Link>
                                                            </Button>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        ) : (
                                            <div className="text-center py-8 text-muted-foreground">
                                                <Folder className="mx-auto h-12 w-12 mb-4" />
                                                <p>Keine Unterkategorien vorhanden.</p>
                                                <Button className="mt-4" asChild>
                                                    <Link href={`/categories/create?parent=${category.id}`}>
                                                        <Plus className="mr-2 h-4 w-4" />
                                                        Erste Unterkategorie hinzufügen
                                                    </Link>
                                                </Button>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            </TabsContent>
                        </Tabs>
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        {/* Category Information */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Kategorieinformationen</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">Name</label>
                                    <p className="font-medium">{category.name}</p>
                                </div>

                                {category.description && (
                                    <div>
                                        <label className="text-sm font-medium text-muted-foreground">Beschreibung</label>
                                        <p className="text-sm">{category.description}</p>
                                    </div>
                                )}

                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">Status</label>
                                    <div className="mt-1">
                                        <Badge variant={category.is_active ? "default" : "secondary"}>
                                            {category.is_active ? "Aktiv" : "Inaktiv"}
                                        </Badge>
                                    </div>
                                </div>

                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">Reihenfolge</label>
                                    <p>{category.sort_order}</p>
                                </div>

                                {category.color && (
                                    <div>
                                        <label className="text-sm font-medium text-muted-foreground">Farbe</label>
                                        <div className="flex items-center space-x-2 mt-1">
                                            <div className="w-4 h-4 rounded-full border" style={{ backgroundColor: category.color }} />
                                            <span className="text-sm font-mono">{category.color}</span>
                                        </div>
                                    </div>
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
                                    <Link href={`/products/create?category=${category.id}`}>
                                        <Plus className="mr-2 h-4 w-4" />
                                        Produkt hinzufügen
                                    </Link>
                                </Button>
                                <Button variant="outline" className="w-full bg-transparent" asChild>
                                    <Link href={`/categories/create?parent=${category.id}`}>
                                        <Plus className="mr-2 h-4 w-4" />
                                        Unterkategorie hinzufügen
                                    </Link>
                                </Button>
                                <Button variant="outline" className="w-full bg-transparent" asChild>
                                    <Link href={`/products?category=${category.id}`}>
                                        <Package className="mr-2 h-4 w-4" />
                                        Alle Produkte anzeigen
                                    </Link>
                                </Button>
                            </CardContent>
                        </Card>

                        {/* Category Details */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Details</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Erstellt:</span>
                                    <span>{formatDate(category.created_at)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Aktualisiert:</span>
                                    <span>{formatDate(category.updated_at)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Kategorie-ID:</span>
                                    <span className="font-mono text-xs">{category.id}</span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    )
}
