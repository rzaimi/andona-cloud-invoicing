"use client"

import { Head } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { BarChart3, Euro, Users, Receipt, TrendingUp } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem } from "@/types"

interface ReportsStats {
    invoices: {
        total: number
        paid: number
        monthly_revenue: number
        yearly_revenue: number
    }
    offers: {
        total: number
        accepted: number
        monthly_total: number
    }
    customers: {
        total: number
        active: number
    }
}

interface ReportsIndexProps {
    stats: ReportsStats
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Berichte & Analysen" },
]

export default function ReportsIndex({ stats }: ReportsIndexProps) {
    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat("de-DE", {
            style: "currency",
            currency: "EUR",
        }).format(amount)
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Berichte & Analysen" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">Berichte & Analysen</h1>
                    <p className="text-gray-600">Übersicht über alle wichtigen Kennzahlen</p>
                </div>

                {/* Statistics Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Monatlicher Umsatz</CardTitle>
                            <Euro className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(stats.invoices.monthly_revenue)}</div>
                            <p className="text-xs text-muted-foreground mt-1">
                                {stats.invoices.paid} von {stats.invoices.total} Rechnungen bezahlt
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Jährlicher Umsatz</CardTitle>
                            <TrendingUp className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(stats.invoices.yearly_revenue)}</div>
                            <p className="text-xs text-muted-foreground mt-1">Gesamter Umsatz dieses Jahr</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Aktive Kunden</CardTitle>
                            <Users className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.customers.active}</div>
                            <p className="text-xs text-muted-foreground mt-1">
                                von {stats.customers.total} insgesamt
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Angebote</CardTitle>
                            <Receipt className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.offers.accepted}</div>
                            <p className="text-xs text-muted-foreground mt-1">
                                von {stats.offers.total} angenommen
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Report Links */}
                <div className="grid gap-4 md:grid-cols-3">
                    <Card className="cursor-pointer hover:bg-gray-50 transition-colors">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Euro className="h-5 w-5" />
                                Umsatzberichte
                            </CardTitle>
                            <CardDescription>Detaillierte Umsatzanalysen und Trends</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <a href="/reports/revenue" className="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                Bericht anzeigen →
                            </a>
                        </CardContent>
                    </Card>

                    <Card className="cursor-pointer hover:bg-gray-50 transition-colors">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Users className="h-5 w-5" />
                                Kundenberichte
                            </CardTitle>
                            <CardDescription>Kundenstatistiken und Top-Kunden</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <a href="/reports/customers" className="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                Bericht anzeigen →
                            </a>
                        </CardContent>
                    </Card>

                    <Card className="cursor-pointer hover:bg-gray-50 transition-colors">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Receipt className="h-5 w-5" />
                                Steuerberichte
                            </CardTitle>
                            <CardDescription>Steuerübersicht und MwSt-Berichte</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <a href="/reports/tax" className="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                Bericht anzeigen →
                            </a>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    )
}

