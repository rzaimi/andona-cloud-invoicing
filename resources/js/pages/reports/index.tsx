"use client"

import { Head, usePage } from "@inertiajs/react"
import { useTranslation } from "react-i18next"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { BarChart3, Euro, Users, EuroIcon, TrendingUp } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem } from "@/types"
import { formatCurrency as formatCurrencyUtil } from "@/utils/formatting"

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
    const { t } = useTranslation()
    const { auth } = usePage<{ auth: { user: { company?: { settings?: Record<string, string> } } } }>().props
    const settings = auth?.user?.company?.settings

    const formatCurrency = (amount: number) => formatCurrencyUtil(amount, settings)

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('pages.reports.title')} />

            <div className="space-y-6">
                <div>
                    <h1 className="text-1xl font-bold text-gray-900">{t('pages.reports.title')}</h1>
                    <p className="text-gray-600">{t('pages.reports.overviewSubtitle')}</p>
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
                            <CardTitle className="text-sm font-medium">{t('pages.reports.annualRevenue')}</CardTitle>
                            <TrendingUp className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(stats.invoices.yearly_revenue)}</div>
                            <p className="text-xs text-muted-foreground mt-1">Gesamter Umsatz dieses Jahr</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{t('pages.reports.activeCustomers')}</CardTitle>
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
                            <CardTitle className="text-sm font-medium">{t('nav.offers')}</CardTitle>
                            <EuroIcon className="h-4 w-4 text-muted-foreground" />
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
                            <CardDescription>{t('pages.reports.customersSubtitle')}</CardDescription>
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
                                <EuroIcon className="h-5 w-5" />
                                Steuerberichte
                            </CardTitle>
                            <CardDescription>{t('pages.reports.taxSubtitle')}</CardDescription>
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

