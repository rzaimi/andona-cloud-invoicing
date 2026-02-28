"use client"

import { useState } from "react"
import { Head, Link, router, usePage } from "@inertiajs/react"
import { useTranslation } from "react-i18next"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Euro, ArrowLeft, Download } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem } from "@/types"
import { route } from "ziggy-js"
import { formatCurrency as formatCurrencyUtil } from "@/utils/formatting"
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, LineChart, Line } from "recharts"

interface RevenueData {
    period: string
    revenue: number
    invoices: number
}

interface RevenueReportsProps {
    period: string
    revenueData: RevenueData[]
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Berichte", href: "/reports" },
    { title: "Umsatzberichte" },
]

export default function RevenueReports({
    period, revenueData }: RevenueReportsProps) {
    const { t } = useTranslation()
    const { auth } = usePage<{ auth: { user: { company?: { settings?: Record<string, string> } } } }>().props
    const settings = auth?.user?.company?.settings

    const [selectedPeriod, setSelectedPeriod] = useState(period)

    const handlePeriodChange = (newPeriod: string) => {
        setSelectedPeriod(newPeriod)
        router.get(route("reports.revenue"), { period: newPeriod })
    }

    const formatCurrency = (amount: number) => formatCurrencyUtil(amount, settings)

    const totalRevenue = revenueData.reduce((sum, item) => sum + item.revenue, 0)
    const totalInvoices = revenueData.reduce((sum, item) => sum + item.invoices, 0)
    const avgRevenue = revenueData.length > 0 ? totalRevenue / revenueData.length : 0

    // Calculate growth percentage
    const prevRevenue = revenueData.length >= 2 ? revenueData[revenueData.length - 2].revenue : 0
    const growth = prevRevenue !== 0
        ? ((revenueData[revenueData.length - 1].revenue - prevRevenue) / prevRevenue) * 100
        : revenueData.length >= 1 && revenueData[revenueData.length - 1].revenue > 0 ? 100 : 0

    const handleExport = () => {
        // Simple CSV export
        const headers = ["Zeitraum", "Anzahl Rechnungen", "Umsatz"]
        const rows = revenueData.map(item => [
            item.period,
            item.invoices.toString(),
            formatCurrency(item.revenue)
        ])
        const csvContent = [
            headers.join(","),
            ...rows.map(row => row.join(",")),
            `Gesamt,${totalInvoices},${formatCurrency(totalRevenue)}`
        ].join("\n")

        const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" })
        const link = document.createElement("a")
        const url = URL.createObjectURL(blob)
        link.setAttribute("href", url)
        link.setAttribute("download", `umsatzbericht-${selectedPeriod}-${new Date().toISOString().split('T')}.csv`)
        link.style.visibility = "hidden"
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Umsatzberichte" />

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
                            <h1 className="text-1xl font-bold text-gray-900">Umsatzberichte</h1>
                            <p className="text-gray-600">Detaillierte Umsatzanalysen und Trends</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Select value={selectedPeriod} onValueChange={handlePeriodChange}>
                            <SelectTrigger className="w-40">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="month">Monat</SelectItem>
                                <SelectItem value="quarter">Quartal</SelectItem>
                                <SelectItem value="year">Jahr</SelectItem>
                            </SelectContent>
                        </Select>
                        <Button variant="outline" onClick={handleExport}>
                            <Download className="mr-2 h-4 w-4" />
                            {t('common.export')}
                        </Button>
                    </div>
                </div>

                {/* Summary Cards */}
                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Gesamtumsatz</CardTitle>
                            <Euro className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(totalRevenue)}</div>
                            <p className="text-xs text-muted-foreground mt-1">
                                {totalInvoices} Rechnungen
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Durchschnitt</CardTitle>
                            <Euro className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(avgRevenue)}</div>
                            <p className="text-xs text-muted-foreground mt-1">Pro Periode</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{t('pages.reports.totalInvoices')}</CardTitle>
                            <Euro className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{totalInvoices}</div>
                            <p className="text-xs text-muted-foreground mt-1">
                                {formatCurrency(totalInvoices > 0 ? totalRevenue / totalInvoices : 0)} Durchschnitt
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Wachstum</CardTitle>
                            <Euro className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className={`text-2xl font-bold ${growth >= 0 ? "text-green-600" : "text-red-600"}`}>
                                {growth >= 0 ? "+" : ""}{growth.toFixed(1)}%
                            </div>
                            <p className="text-xs text-muted-foreground mt-1">vs. Vorperiode</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Chart */}
                <Card>
                    <CardHeader>
                        <CardTitle>Umsatzentwicklung</CardTitle>
                        <CardDescription>
                            {selectedPeriod === "month" && t('pages.reports.monthlyRevenue')}
                            {selectedPeriod === "quarter" && t('pages.reports.quarterlyRevenue')}
                            {selectedPeriod === "year" && t('pages.reports.yearlyRevenue')}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ResponsiveContainer width="100%" height={350}>
                            <BarChart data={revenueData}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis 
                                    dataKey="period" 
                                    tick={{ fontSize: 12 }}
                                    angle={-45}
                                    textAnchor="end"
                                    height={80}
                                />
                                <YAxis 
                                    tick={{ fontSize: 12 }}
                                    tickFormatter={(value) => formatCurrency(value)}
                                />
                                <Tooltip 
                                    formatter={(value: number) => formatCurrency(value)}
                                    contentStyle={{ backgroundColor: "white", border: "1px solid #e5e7eb" }}
                                />
                                <Legend />
                                <Bar dataKey="revenue" fill="oklch(0.646 0.222 41.116)" name="Umsatz" />
                            </BarChart>
                        </ResponsiveContainer>
                    </CardContent>
                </Card>

                {/* Line Chart for Trend */}
                <Card>
                    <CardHeader>
                        <CardTitle>Umsatztrend</CardTitle>
                        <CardDescription>{t('pages.reports.revenueHistory')}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ResponsiveContainer width="100%" height={300}>
                            <LineChart data={revenueData}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis 
                                    dataKey="period" 
                                    tick={{ fontSize: 12 }}
                                    angle={-45}
                                    textAnchor="end"
                                    height={80}
                                />
                                <YAxis 
                                    tick={{ fontSize: 12 }}
                                    tickFormatter={(value) => formatCurrency(value)}
                                />
                                <Tooltip 
                                    formatter={(value: number) => formatCurrency(value)}
                                    contentStyle={{ backgroundColor: "white", border: "1px solid #e5e7eb" }}
                                />
                                <Legend />
                                <Line 
                                    type="monotone" 
                                    dataKey="revenue" 
                                    stroke="oklch(0.6 0.118 184.704)" 
                                    strokeWidth={2}
                                    name="Umsatz"
                                    dot={{ r: 4 }}
                                    activeDot={{ r: 6 }}
                                />
                            </LineChart>
                        </ResponsiveContainer>
                    </CardContent>
                </Card>

                {/* Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>{t('pages.reports.detailedOverview')}</CardTitle>
                        <CardDescription>
                            {selectedPeriod === "month" && t('pages.reports.monthlyRevenue')}
                            {selectedPeriod === "quarter" && t('pages.reports.quarterlyRevenue')}
                            {selectedPeriod === "year" && t('pages.reports.yearlyRevenue')}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Zeitraum</TableHead>
                                    <TableHead className="text-right">{t('pages.reports.invoiceCount')}</TableHead>
                                    <TableHead className="text-right">Umsatz</TableHead>
                                    <TableHead className="text-right">Ø pro Rechnung</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {revenueData.map((item, index) => (
                                    <TableRow key={index}>
                                        <TableCell className="font-medium">{item.period}</TableCell>
                                        <TableCell className="text-right">{item.invoices}</TableCell>
                                        <TableCell className="text-right font-bold">
                                            {formatCurrency(item.revenue)}
                                        </TableCell>
                                        <TableCell className="text-right text-muted-foreground">
                                            {formatCurrency(item.invoices > 0 ? item.revenue / item.invoices : 0)}
                                        </TableCell>
                                    </TableRow>
                                ))}
                                <TableRow className="font-bold bg-gray-50 dark:bg-gray-800">
                                    <TableCell>{t('common.total')}</TableCell>
                                    <TableCell className="text-right">{totalInvoices}</TableCell>
                                    <TableCell className="text-right">{formatCurrency(totalRevenue)}</TableCell>
                                    <TableCell className="text-right">
                                        {formatCurrency(totalInvoices > 0 ? totalRevenue / totalInvoices : 0)}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    )
}
