"use client"

import { Head, Link, usePage } from "@inertiajs/react"
import { useTranslation } from "react-i18next"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Users, ArrowLeft, TrendingUp, TrendingDown, Download } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem } from "@/types"
import { route } from "ziggy-js"
import { formatCurrency as formatCurrencyUtil } from "@/utils/formatting"
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from "recharts"

interface TopCustomer {
    customer_id: string
    customer_name: string
    total_revenue: number
    invoice_count: number
}

interface CustomerStats {
    top_customers: TopCustomer[]
    growth: {
        last_month: number
        this_month: number
        change: number
    }
    by_status: Record<string, number>
}

interface CustomerReportsProps {
    customerStats: CustomerStats
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Berichte", href: "/reports" },
    { title: "Kundenberichte" },
]

export default function CustomerReports({
    customerStats }: CustomerReportsProps) {
    const { t } = useTranslation()
    const { auth } = usePage<{ auth: { user: { company?: { settings?: Record<string, string> } } } }>().props
    const settings = auth?.user?.company?.settings

    const formatCurrency = (amount: number) => formatCurrencyUtil(amount, settings)

    const handleExport = () => {
        const headers = ["Rang", "Kunde", "Anzahl Rechnungen", "Gesamtumsatz"]
        const rows = customerStats.top_customers.map((customer, index) => [
            (index + 1).toString(),
            customer.customer_name,
            customer.invoice_count.toString(),
            formatCurrency(customer.total_revenue)
        ])
        const csvContent = [
            headers.join(","),
            ...rows.map(row => row.join(","))
        ].join("\n")

        const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" })
        const link = document.createElement("a")
        const url = URL.createObjectURL(blob)
        link.setAttribute("href", url)
        link.setAttribute("download", `kundenbericht-${new Date().toISOString().split('T')}.csv`)
        link.style.visibility = "hidden"
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kundenberichte" />

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
                            <h1 className="text-1xl font-bold text-gray-900">{t('nav.reportsCustomers')}</h1>
                            <p className="text-gray-600">{t('pages.reports.customersSubtitle')}</p>
                        </div>
                    </div>
                    <Button variant="outline" onClick={handleExport}>
                        <Download className="mr-2 h-4 w-4" />
                        {t('common.export')}
                    </Button>
                </div>

                {/* Statistics Cards */}
                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">{t('pages.reports.customerGrowth')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center justify-between">
                                <div>
                                    <div className="text-2xl font-bold">{customerStats.growth.this_month}</div>
                                    <p className="text-xs text-muted-foreground">Aktueller Monat</p>
                                </div>
                                <div className="text-right">
                                    {customerStats.growth.change > 0 ? (
                                        <div className="flex items-center text-green-600">
                                            <TrendingUp className="h-4 w-4 mr-1" />
                                            <span className="text-sm font-medium">+{customerStats.growth.change}</span>
                                        </div>
                                    ) : customerStats.growth.change < 0 ? (
                                        <div className="flex items-center text-red-600">
                                            <TrendingDown className="h-4 w-4 mr-1" />
                                            <span className="text-sm font-medium">{customerStats.growth.change}</span>
                                        </div>
                                    ) : (
                                        <span className="text-sm text-muted-foreground">{t('pages.reports.noChange')}</span>
                                    )}
                                    <p className="text-xs text-muted-foreground">vs. Vormonat</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">{t('pages.reports.customersByStatus')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                {Object.entries(customerStats.by_status).map(([status, count]) => (
                                    <div key={status} className="flex items-center justify-between">
                                        <span className="text-sm capitalize">{status === "active" ? "Aktiv" : status}</span>
                                        <span className="text-sm font-medium">{count}</span>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">{t('pages.reports.topCustomers')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{customerStats.top_customers.length}</div>
                            <p className="text-xs text-muted-foreground mt-1">{t('pages.reports.topCustomersByRevenue')}</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Top Customers Chart */}
                {customerStats.top_customers.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Top 10 Kunden - Umsatzvergleich</CardTitle>
                            <CardDescription>{t('pages.reports.topCustomersByRevenueDesc')}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={350}>
                                <BarChart 
                                    data={customerStats.top_customers.slice().reverse().map((c, i) => ({
                                        name: c.customer_name.length > 20 ? c.customer_name.substring(0, 20) + "..." : c.customer_name,
                                        umsatz: c.total_revenue,
                                        rechnungen: c.invoice_count
                                    }))}
                                    layout="vertical"
                                >
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis 
                                        type="number"
                                        tickFormatter={(value) => formatCurrency(value)}
                                        tick={{ fontSize: 12 }}
                                    />
                                    <YAxis 
                                        type="category"
                                        dataKey="name"
                                        tick={{ fontSize: 12 }}
                                        width={150}
                                    />
                                    <Tooltip 
                                        formatter={(value: number, name: string) => [
                                            name === "umsatz" ? formatCurrency(value) : value,
                                            name === "umsatz" ? "Umsatz" : "Rechnungen"
                                        ]}
                                        contentStyle={{ backgroundColor: "white", border: "1px solid #e5e7eb" }}
                                    />
                                    <Legend />
                                    <Bar dataKey="umsatz" fill="oklch(0.646 0.222 41.116)" name="Umsatz" />
                                </BarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                )}

                {/* Top Customers Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>{t('pages.reports.top10CustomersByRevenue')}</CardTitle>
                        <CardDescription>{t('pages.reports.top10CustomersDesc')}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{t('pages.reports.rank')}</TableHead>
                                    <TableHead>{t('pages.reports.customer')}</TableHead>
                                    <TableHead className="text-right">{t('pages.reports.invoiceCount')}</TableHead>
                                    <TableHead className="text-right">{t('pages.reports.totalRevenue')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {customerStats.top_customers.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={4} className="text-center py-8 text-gray-500">
                                            {t('pages.reports.noData')}
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    customerStats.top_customers.map((customer, index) => (
                                        <TableRow key={customer.customer_id}>
                                            <TableCell className="font-medium">#{index + 1}</TableCell>
                                            <TableCell>{customer.customer_name}</TableCell>
                                            <TableCell className="text-right">{customer.invoice_count}</TableCell>
                                            <TableCell className="text-right font-bold">
                                                {formatCurrency(customer.total_revenue)}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    )
}

