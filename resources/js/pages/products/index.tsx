"use client"

import { useState } from "react"
import { Head, Link, router, usePage } from "@inertiajs/react"
import { useTranslation } from "react-i18next"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Badge } from "@/components/ui/badge"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Package, Plus, Search, Filter, Edit, Eye, Trash2, AlertTriangle, TrendingUp, TrendingDown, Download } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { Product, Category, PaginatedResponse, BreadcrumbItem } from "@/types"
import { route } from "ziggy-js"
import { formatCurrency as formatCurrencyUtil } from "@/utils/formatting"

interface ProductsIndexProps {
    user: User
    products: PaginatedResponse<Product>
    categories: Category[]
    stats: {
        total_products: number
        active_products: number
        low_stock_products: number
        out_of_stock_products: number
        total_value: number
    }
    filters: {
        search?: string
        category?: string
        status?: string
        stock_status?: string
    }
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Produkte" },
]

export default function ProductsIndex({ products, categories, stats, filters }: Omit<ProductsIndexProps, 'user'>) {
    const { t } = useTranslation()
    const { auth } = usePage<{ auth: { user: { company?: { settings?: Record<string, string> } } } }>().props
    const settings = auth?.user?.company?.settings
    const [search, setSearch] = useState(filters.search || "")
    const [selectedCategory, setSelectedCategory] = useState(filters.category || "all")
    const [selectedStatus, setSelectedStatus] = useState(filters.status || "all")
    const [selectedStockStatus, setSelectedStockStatus] = useState(filters.stock_status || "all")

    const handleSearch = () => {
        router.get(
            "/products",
            {
                search,
                category: selectedCategory,
                status: selectedStatus,
                stock_status: selectedStockStatus,
            },
            { preserveState: true },
        )
    }

    const handleReset = () => {
        setSearch("")
        setSelectedCategory("all")
        setSelectedStatus("all")
        setSelectedStockStatus("all")
        router.get("/products")
    }

    const deleteProduct = (id: string) => {
        if (confirm(t('pages.products.deleteConfirm'))) {
            router.delete(`/products/${id}`)
        }
    }

    const getStockStatusBadge = (product: Product) => {
        if (!product.track_stock) {
            return <Badge variant="outline">Nicht verfolgt</Badge>
        }

        if (product.stock_quantity <= 0) {
            return <Badge variant="destructive">Ausverkauft</Badge>
        }

        if (product.stock_quantity <= product.min_stock_level) {
            return <Badge variant="secondary">Niedriger Bestand</Badge>
        }

        return <Badge variant="default">Auf Lager</Badge>
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

    const formatCurrency = (amount: number) => formatCurrencyUtil(amount, settings)

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('pages.products.title')} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-1xl font-bold tracking-tight dark:text-gray-100">{t('pages.products.title')}</h1>
                        <p className="text-muted-foreground">{t('pages.products.subtitle')}</p>
                    </div>
                    <div className="flex items-center space-x-2">
                        <Button
                            variant="outline"
                            onClick={() => {
                                const params = new URLSearchParams()
                                if (filters.search) params.append('search', filters.search)
                                if (filters.category && filters.category !== 'all') params.append('category', filters.category)
                                if (filters.status && filters.status !== 'all') params.append('status', filters.status)
                                window.location.href = route('export.products') + (params.toString() ? '?' + params.toString() : '')
                            }}
                        >
                            <Download className="mr-2 h-4 w-4" />
                            {t('common.export')}
                        </Button>
                        <Button asChild>
                            <Link href="/products/create">
                                <Plus className="mr-2 h-4 w-4" />
                                {t('pages.products.new')}
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Statistics Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{t('pages.products.statsTotal')}</CardTitle>
                            <Package className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_products}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{t('pages.products.statsActive')}</CardTitle>
                            <TrendingUp className="h-4 w-4 text-green-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.active_products}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Niedriger Bestand</CardTitle>
                            <AlertTriangle className="h-4 w-4 text-yellow-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.low_stock_products}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Ausverkauft</CardTitle>
                            <TrendingDown className="h-4 w-4 text-red-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.out_of_stock_products}</div>
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
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle>{t('common.filters')}</CardTitle>
                        <CardDescription>{t('pages.products.filterDesc')}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Suche</label>
                                <div className="relative">
                                    <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        placeholder="Produktname, SKU..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="pl-8"
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">Kategorie</label>
                                <Select value={selectedCategory} onValueChange={setSelectedCategory}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Alle Kategorien" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Alle Kategorien</SelectItem>
                                        {categories.map((category) => (
                                            <SelectItem key={category.id} value={category.id}>
                                                {category.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">{t('common.status')}</label>
                                <Select value={selectedStatus} onValueChange={setSelectedStatus}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Alle Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Alle Status</SelectItem>
                                        <SelectItem value="active">{t('common.active')}</SelectItem>
                                        <SelectItem value="inactive">{t('common.inactive')}</SelectItem>
                                        <SelectItem value="discontinued">Eingestellt</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">{t('pages.products.tabInventory')}</label>
                                <Select value={selectedStockStatus} onValueChange={setSelectedStockStatus}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('pages.products.allStocks')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{t('pages.products.allStocks')}</SelectItem>
                                        <SelectItem value="in_stock">Auf Lager</SelectItem>
                                        <SelectItem value="low_stock">Niedriger Bestand</SelectItem>
                                        <SelectItem value="out_of_stock">Ausverkauft</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex items-end space-x-2">
                                <Button onClick={handleSearch} className="flex-1">
                                    <Filter className="mr-2 h-4 w-4" />
                                    Filtern
                                </Button>
                                <Button variant="outline" onClick={handleReset}>
                                    {t('common.reset')}
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Products Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Produkte ({products.total})</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>{t('common.number')}</TableHead>
                                        <TableHead>{t('common.name')}</TableHead>
                                        <TableHead>Kategorie</TableHead>
                                        <TableHead>{t('common.price')}</TableHead>
                                        <TableHead>Bestand</TableHead>
                                        <TableHead>{t('common.status')}</TableHead>
                                        <TableHead>Lager-Status</TableHead>
                                        <TableHead className="text-right">{t('common.actions')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {products.data.map((product) => (
                                        <TableRow key={product.id}>
                                            <TableCell className="font-medium">{product.number}</TableCell>
                                            <TableCell>
                                                <div>
                                                    <div className="font-medium">{product.name}</div>
                                                    {product.sku && <div className="text-sm text-muted-foreground">SKU: {product.sku}</div>}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {product.category ? (
                                                    <Badge variant="outline">{product.category.name}</Badge>
                                                ) : (
                                                    <span className="text-muted-foreground">-</span>
                                                )}
                                            </TableCell>
                                            <TableCell>{formatCurrency(product.price)}</TableCell>
                                            <TableCell>
                                                {product.track_stock ? (
                                                    <div className="text-sm">
                                                        <div>
                                                            {product.stock_quantity} {product.unit}
                                                        </div>
                                                        <div className="text-muted-foreground">Min: {product.min_stock_level}</div>
                                                    </div>
                                                ) : (
                                                    <span className="text-muted-foreground">Nicht verfolgt</span>
                                                )}
                                            </TableCell>
                                            <TableCell>{getStatusBadge(product.status)}</TableCell>
                                            <TableCell>{getStockStatusBadge(product)}</TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end space-x-2">
                                                    <Button variant="ghost" size="sm" asChild>
                                                        <Link href={`/products/${product.id}`}>
                                                            <Eye className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    <Button variant="ghost" size="sm" asChild>
                                                        <Link href={`/products/${product.id}/edit`}>
                                                            <Edit className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    <Button variant="ghost" size="sm" onClick={() => deleteProduct(product.id)}>
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        {/* Pagination */}
                        {products.last_page > 1 && (
                            <div className="flex items-center justify-between space-x-2 py-4">
                                <div className="text-sm text-muted-foreground">
                                    Zeige {products.from} bis {products.to} von {products.total} Produkten
                                </div>
                                <div className="flex items-center space-x-2">
                                    {products.links.map((link, index) => (
                                        <Button
                                            key={`${link.label}-${index}`}
                                            variant={link.active ? "default" : "outline"}
                                            size="sm"
                                            disabled={!link.url}
                                            onClick={() => link.url && router.get(link.url)}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    )
}
