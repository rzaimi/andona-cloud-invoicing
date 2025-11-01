"use client"

import { useState } from "react"
import { Head, Link, router } from "@inertiajs/react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Badge } from "@/components/ui/badge"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Plus, Search, Filter, Edit, Eye, Trash2, FolderOpen, Folder, Package, TrendingUp } from 'lucide-react'
import AppLayout from "@/layouts/app-layout"
import type { Category, PaginatedResponse, User } from "@/types"

interface CategoriesIndexProps {
    user: User
    categories: PaginatedResponse<Category & { products_count: number; parent?: Category; children?: Category[] }>
    stats: {
        total_categories: number
        active_categories: number
        root_categories: number
        categories_with_products: number
    }
    parentCategories: Category[]
    filters: {
        search?: string
        status?: string
        parent?: string
    }
}

export default function CategoriesIndex({ user, categories, stats, parentCategories, filters }: CategoriesIndexProps) {
    const [search, setSearch] = useState(filters.search || "")
    const [selectedStatus, setSelectedStatus] = useState(filters.status || "all")
    const [selectedParent, setSelectedParent] = useState(filters.parent || "all")

    const handleSearch = () => {
        router.get(
            "/categories",
            {
                search,
                status: selectedStatus,
                parent: selectedParent,
            },
            { preserveState: true },
        )
    }

    const handleReset = () => {
        setSearch("")
        setSelectedStatus("all")
        setSelectedParent("all")
        router.get("/categories")
    }

    const deleteCategory = (id: string) => {
        if (confirm("Sind Sie sicher, dass Sie diese Kategorie löschen möchten?")) {
            router.delete(`/categories/${id}`)
        }
    }

    const getStatusBadge = (isActive: boolean) => {
        return <Badge variant={isActive ? "default" : "secondary"}>{isActive ? "Aktiv" : "Inaktiv"}</Badge>
    }

    const getCategoryIcon = (category: Category & { children?: Category[] }) => {
        if (category.children && category.children.length > 0) {
            return <FolderOpen className="h-4 w-4 text-blue-600" />
        }
        return <Folder className="h-4 w-4 text-gray-600" />
    }

    return (
        <AppLayout user={user}>
            <Head title="Kategorieverwaltung" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Kategorieverwaltung</h1>
                        <p className="text-muted-foreground">Organisieren Sie Ihre Produkte in Kategorien</p>
                    </div>
                    <div className="flex items-center space-x-2">
                        <Button asChild>
                            <Link href="/categories/create">
                                <Plus className="mr-2 h-4 w-4" />
                                Neue Kategorie
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Statistics Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Gesamt Kategorien</CardTitle>
                            <Folder className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_categories}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Aktive Kategorien</CardTitle>
                            <TrendingUp className="h-4 w-4 text-green-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.active_categories}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Hauptkategorien</CardTitle>
                            <FolderOpen className="h-4 w-4 text-blue-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.root_categories}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Mit Produkten</CardTitle>
                            <Package className="h-4 w-4 text-purple-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.categories_with_products}</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle>Filter</CardTitle>
                        <CardDescription>Filtern Sie Ihre Kategorien nach verschiedenen Kriterien</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Suche</label>
                                <div className="relative">
                                    <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        placeholder="Kategoriename..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="pl-8"
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">Status</label>
                                <Select value={selectedStatus} onValueChange={setSelectedStatus}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Alle Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Alle Status</SelectItem>
                                        <SelectItem value="active">Aktiv</SelectItem>
                                        <SelectItem value="inactive">Inaktiv</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">Übergeordnete Kategorie</label>
                                <Select value={selectedParent} onValueChange={setSelectedParent}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Alle Kategorien" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Alle Kategorien</SelectItem>
                                        <SelectItem value="root">Nur Hauptkategorien</SelectItem>
                                        {parentCategories.map((category) => (
                                            <SelectItem key={category.id} value={category.id}>
                                                {category.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex items-end space-x-2">
                                <Button onClick={handleSearch} className="flex-1">
                                    <Filter className="mr-2 h-4 w-4" />
                                    Filtern
                                </Button>
                                <Button variant="outline" onClick={handleReset}>
                                    Zurücksetzen
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Categories Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Kategorien ({categories.total})</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Übergeordnete Kategorie</TableHead>
                                        <TableHead>Beschreibung</TableHead>
                                        <TableHead>Produkte</TableHead>
                                        <TableHead>Reihenfolge</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Aktionen</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {categories.data.map((category) => (
                                        <TableRow key={category.id}>
                                            <TableCell>
                                                <div className="flex items-center space-x-2">
                                                    {getCategoryIcon(category)}
                                                    <div>
                                                        <div className="font-medium">{category.name}</div>
                                                        {category.color && (
                                                            <div className="flex items-center space-x-1 mt-1">
                                                                <div
                                                                    className="w-3 h-3 rounded-full border"
                                                                    style={{ backgroundColor: category.color }}
                                                                />
                                                                <span className="text-xs text-muted-foreground">{category.color}</span>
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {category.parent ? (
                                                    <Badge variant="outline">{category.parent.name}</Badge>
                                                ) : (
                                                    <span className="text-muted-foreground">Hauptkategorie</span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {category.description ? (
                                                    <span className="text-sm">{category.description.substring(0, 50)}...</span>
                                                ) : (
                                                    <span className="text-muted-foreground">-</span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center space-x-1">
                                                    <Package className="h-4 w-4 text-muted-foreground" />
                                                    <span>{category.products_count}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline">{category.sort_order}</Badge>
                                            </TableCell>
                                            <TableCell>{getStatusBadge(category.is_active)}</TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end space-x-2">
                                                    <Button variant="ghost" size="sm" asChild>
                                                        <Link href={`/categories/${category.id}`}>
                                                            <Eye className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    <Button variant="ghost" size="sm" asChild>
                                                        <Link href={`/categories/${category.id}/edit`}>
                                                            <Edit className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    <Button variant="ghost" size="sm" onClick={() => deleteCategory(category.id)}>
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
                        {categories.last_page > 1 && (
                            <div className="flex items-center justify-between space-x-2 py-4">
                                <div className="text-sm text-muted-foreground">
                                    Zeige {categories.from} bis {categories.to} von {categories.total} Kategorien
                                </div>
                                <div className="flex items-center space-x-2">
                                    {categories.links.map((link, index) => (
                                        <Button
                                            key={index}
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
