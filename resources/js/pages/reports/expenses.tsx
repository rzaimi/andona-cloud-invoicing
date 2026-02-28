"use client"

import { useState } from "react"
import { Head, Link, router, usePage } from "@inertiajs/react"
import { useTranslation } from "react-i18next"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { ArrowLeft, Download, EuroIcon, Receipt } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem } from "@/types"
import { route } from "ziggy-js"
import { formatCurrency as formatCurrencyUtil } from "@/utils/formatting"
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from "recharts"

interface ExpenseData {
    category_id?: string
    category_name: string
    net_amount: number
    vat_amount: number
    total_amount: number
}

interface ExpensesReportsProps {
    expenses: ExpenseData[]
    totals: {
        net_amount: number
        vat_amount: number
        total_amount: number
    }
    filters: {
        start_date: string
        end_date: string
    }
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Berichte", href: "/reports" },
    { title: "Ausgabenberichte" },
]

export default function ExpensesReports({
    expenses, totals, filters }: ExpensesReportsProps) {
    const { t } = useTranslation()
    const { auth } = usePage<{ auth: { user: { company?: { settings?: Record<string, string> } } } }>().props
    const settings = auth?.user?.company?.settings

    const [startDate, setStartDate] = useState(filters.start_date)
    const [endDate, setEndDate] = useState(filters.end_date)

    const handleFilter = () => {
        router.get(route("reports.expenses"), { start_date: startDate, end_date: endDate })
    }

    const formatCurrency = (amount: number) => formatCurrencyUtil(amount, settings)

    const chartData = expenses.map((exp) => ({
        name: exp.category_name,
        Netto: exp.net_amount,
        MwSt: exp.vat_amount,
        Gesamt: exp.total_amount,
    }))

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Ausgabenberichte" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href={route("reports.index")}>
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                {t('common.back')}
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-1xl font-bold text-gray-900">{t('nav.reportsExpenses')}</h1>
                            <p className="text-gray-600">{t('pages.reports.expensesByCategory')}</p>
                        </div>
                    </div>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle>Zeitraum</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex gap-4">
                            <div className="flex-1">
                                <label className="text-sm font-medium mb-2 block">Von</label>
                                <Input
                                    type="date"
                                    value={startDate}
                                    onChange={(e) => setStartDate(e.target.value)}
                                />
                            </div>
                            <div className="flex-1">
                                <label className="text-sm font-medium mb-2 block">Bis</label>
                                <Input
                                    type="date"
                                    value={endDate}
                                    onChange={(e) => setEndDate(e.target.value)}
                                />
                            </div>
                            <div className="flex items-end">
                                <Button onClick={handleFilter}>Filtern</Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Summary Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Netto-Betrag</CardTitle>
                            <EuroIcon className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(totals.net_amount)}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">MwSt.</CardTitle>
                            <EuroIcon className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(totals.vat_amount)}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Gesamtbetrag</CardTitle>
                            <EuroIcon className="h-4 w-4 text-blue-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600">{formatCurrency(totals.total_amount)}</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Chart */}
                {expenses.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('pages.reports.expensesByCategory')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={400}>
                                <BarChart data={chartData}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="name" />
                                    <YAxis />
                                    <Tooltip formatter={(value: number) => formatCurrency(value)} />
                                    <Legend />
                                    <Bar dataKey="Netto" fill="#8884d8" />
                                    <Bar dataKey="MwSt" fill="#82ca9d" />
                                    <Bar dataKey="Gesamt" fill="#ffc658" />
                                </BarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                )}

                {/* Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Detaillierte Aufstellung</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Kategorie</TableHead>
                                    <TableHead className="text-right">Netto-Betrag</TableHead>
                                    <TableHead className="text-right">MwSt.</TableHead>
                                    <TableHead className="text-right">Gesamtbetrag</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {expenses.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={4} className="text-center text-gray-500 py-8">
                                            {t('pages.reports.noExpenses')}
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    <>
                                        {expenses.map((expense, index) => (
                                            <TableRow key={index}>
                                                <TableCell className="font-medium">{expense.category_name}</TableCell>
                                                <TableCell className="text-right">{formatCurrency(expense.net_amount)}</TableCell>
                                                <TableCell className="text-right">{formatCurrency(expense.vat_amount)}</TableCell>
                                                <TableCell className="text-right font-medium">
                                                    {formatCurrency(expense.total_amount)}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                        <TableRow className="font-bold">
                                            <TableCell>{t('common.total')}</TableCell>
                                            <TableCell className="text-right">{formatCurrency(totals.net_amount)}</TableCell>
                                            <TableCell className="text-right">{formatCurrency(totals.vat_amount)}</TableCell>
                                            <TableCell className="text-right">{formatCurrency(totals.total_amount)}</TableCell>
                                        </TableRow>
                                    </>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    )
}



