"use client"

import { Head, Link } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Users, ArrowLeft, TrendingUp, TrendingDown } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem } from "@/types"
import { route } from "ziggy-js"

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

export default function CustomerReports({ customerStats }: CustomerReportsProps) {
    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat("de-DE", {
            style: "currency",
            currency: "EUR",
        }).format(amount)
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kundenberichte" />

            <div className="space-y-6">
                <div className="flex items-center gap-4">
                    <Link href={route("reports.index")}>
                        <Button variant="outline" size="sm">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Zurück
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Kundenberichte</h1>
                        <p className="text-gray-600">Kundenstatistiken und Top-Kunden</p>
                    </div>
                </div>

                {/* Statistics Cards */}
                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">Kundenwachstum</CardTitle>
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
                                        <span className="text-sm text-muted-foreground">Keine Änderung</span>
                                    )}
                                    <p className="text-xs text-muted-foreground">vs. Vormonat</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">Kunden nach Status</CardTitle>
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
                            <CardTitle className="text-sm font-medium">Top Kunden</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{customerStats.top_customers.length}</div>
                            <p className="text-xs text-muted-foreground mt-1">Kunden mit höchstem Umsatz</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Top Customers Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Top 10 Kunden nach Umsatz</CardTitle>
                        <CardDescription>Kunden mit dem höchsten Gesamtumsatz</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Rang</TableHead>
                                    <TableHead>Kunde</TableHead>
                                    <TableHead className="text-right">Anzahl Rechnungen</TableHead>
                                    <TableHead className="text-right">Gesamtumsatz</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {customerStats.top_customers.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={4} className="text-center py-8 text-gray-500">
                                            Keine Daten verfügbar
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

