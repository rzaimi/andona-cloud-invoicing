"use client"

import type React from "react"
import { useState } from "react"
import { Head, Link, router, usePage } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Plus, Edit, Trash2, Search, EuroIcon, Download, Eye } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem } from "@/types"
import { Pagination } from "@/components/pagination"
import { formatCurrency as formatCurrencyUtil } from "@/utils/formatting"

interface Expense {
    id: string
    company_id: string
    user_id: string
    category_id?: string
    title: string
    description?: string
    amount: number
    vat_rate: number
    vat_amount: number
    net_amount: number
    expense_date: string
    payment_method?: string
    reference?: string
    receipt_path?: string
    category?: {
        id: string
        name: string
    }
    user?: {
        id: string
        name: string
    }
    created_at: string
    updated_at: string
}

interface ExpensesIndexProps {
    expenses: {
        data: Expense[]
        links: any[]
        meta: any
    }
    categories: Array<{
        id: string
        name: string
    }>
    filters: {
        start_date?: string
        end_date?: string
        category_id?: string
        search?: string
    }
    totals: {
        net_amount: number
        vat_amount: number
        total_amount: number
    }
}

const breadcrumbs: BreadcrumbItem[] = [{ title: "Dashboard", href: "/dashboard" }, { title: "Ausgaben" }]

export default function ExpensesIndex() {
    // @ts-ignore
    const { expenses, categories, filters, totals } = usePage<ExpensesIndexProps>().props
    const settings = (usePage().props as any).auth?.user?.company?.settings ?? {}
    const [search, setSearch] = useState(filters.search || "")
    const [startDate, setStartDate] = useState(filters.start_date || "")
    const [endDate, setEndDate] = useState(filters.end_date || "")
    const [categoryId, setCategoryId] = useState(filters.category_id || "all")

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault()
        const params: any = {}
        if (search) params.search = search
        if (startDate) params.start_date = startDate
        if (endDate) params.end_date = endDate
        if (categoryId && categoryId !== "all") params.category_id = categoryId
        router.get("/expenses", params, { preserveScroll: true })
    }

    const handleDelete = (expense: Expense) => {
        if (confirm(`Möchten Sie die Ausgabe "${expense.title}" wirklich löschen?`)) {
            router.delete(`/expenses/${expense.id}`)
        }
    }

    const formatCurrency = (amount: number | null | undefined) =>
        formatCurrencyUtil(Number(amount) || 0, settings)

    const formatDate = (date: string) => {
        return new Date(date).toLocaleDateString("de-DE")
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Ausgaben" />

            <div className="flex flex-1 flex-col gap-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-1xl font-bold text-gray-900 dark:text-gray-100">Ausgabenverwaltung</h1>
                        <p className="text-gray-600">Verwalten Sie alle Geschäftsausgaben</p>
                    </div>

                    <Link href="/expenses/create">
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            Neue Ausgabe
                        </Button>
                    </Link>
                </div>

                {/* Statistics Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Netto-Betrag</CardTitle>
                            <EuroIcon className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(totals?.net_amount ?? 0)}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">MwSt.</CardTitle>
                            <EuroIcon className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(totals?.vat_amount ?? 0)}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Gesamtbetrag</CardTitle>
                            <EuroIcon className="h-4 w-4 text-blue-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600">
                                {formatCurrency(totals?.total_amount ?? 0)}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle>Ausgaben filtern</CardTitle>
                        <CardDescription>Suchen und filtern Sie Ihre Ausgaben</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSearch} className="flex flex-col gap-4">
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                                    <Input
                                        placeholder="Nach Titel, Beschreibung oder Referenz suchen..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="pl-10"
                                    />
                                </div>
                                <Input
                                    type="date"
                                    placeholder="Von Datum"
                                    value={startDate}
                                    onChange={(e) => setStartDate(e.target.value)}
                                />
                                <Input
                                    type="date"
                                    placeholder="Bis Datum"
                                    value={endDate}
                                    onChange={(e) => setEndDate(e.target.value)}
                                />
                                <Select value={categoryId} onValueChange={setCategoryId}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Kategorie wählen" />
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
                            <div className="flex gap-2">
                                <Button type="submit">Suchen</Button>
                                {(filters.search || filters.start_date || filters.end_date || filters.category_id) && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => {
                                            setSearch("")
                                            setStartDate("")
                                            setEndDate("")
                                            setCategoryId("all")
                                            router.get("/expenses")
                                        }}
                                    >
                                        Zurücksetzen
                                    </Button>
                                )}
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Expenses Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Ausgaben</CardTitle>
                        <CardDescription>Alle Ausgaben in Ihrem System</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Datum</TableHead>
                                    <TableHead>Titel</TableHead>
                                    <TableHead>Kategorie</TableHead>
                                    <TableHead>Netto</TableHead>
                                    <TableHead>MwSt.</TableHead>
                                    <TableHead>Gesamt</TableHead>
                                    <TableHead>Beleg</TableHead>
                                    <TableHead>Aktionen</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {expenses.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={8} className="text-center text-gray-500 py-8">
                                            Keine Ausgaben gefunden
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    expenses.data.map((expense) => (
                                        <TableRow key={expense.id}>
                                            <TableCell>{formatDate(expense.expense_date)}</TableCell>
                                            <TableCell className="font-medium">{expense.title}</TableCell>
                                            <TableCell>{expense.category?.name || "-"}</TableCell>
                                            <TableCell>{formatCurrency(expense.net_amount)}</TableCell>
                                            <TableCell>{formatCurrency(expense.vat_amount)}</TableCell>
                                            <TableCell className="font-medium">
                                                {formatCurrency(expense.amount)}
                                            </TableCell>
                                            <TableCell>
                                                {expense.receipt_path ? (
                                                    <Link href={`/expenses/${expense.id}/receipt`}>
                                                        <Button variant="ghost" size="sm">
                                                            <Download className="h-4 w-4" />
                                                        </Button>
                                                    </Link>
                                                ) : (
                                                    "-"
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex gap-2">
                                                    <Link href={`/expenses/${expense.id}`}>
                                                        <Button variant="ghost" size="sm">
                                                            <Eye className="h-4 w-4" />
                                                        </Button>
                                                    </Link>
                                                    <Link href={`/expenses/${expense.id}/edit`}>
                                                        <Button variant="ghost" size="sm">
                                                            <Edit className="h-4 w-4" />
                                                        </Button>
                                                    </Link>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleDelete(expense)}
                                                    >
                                                        <Trash2 className="h-4 w-4 text-red-600" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>

                        {/* Pagination */}
                        <Pagination links={expenses.links || []} variant="minimal" className="mt-4" />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    )
}

