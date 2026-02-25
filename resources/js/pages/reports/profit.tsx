"use client"

import { useState } from "react"
import { Head, Link, router, usePage } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { ArrowLeft, TrendingUp, TrendingDown } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem } from "@/types"
import { route } from "ziggy-js"
import { formatCurrency as formatCurrencyUtil } from "@/utils/formatting"
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from "recharts"

interface ProfitData {
    income: number
    expenses: number
    profit: number
}

interface MonthlyData {
    month: string
    label: string
    income: number
    expenses: number
    profit: number
}

interface ProfitReportsProps {
    profit: ProfitData
    months: MonthlyData[]
    filters: {
        start_date: string
        end_date: string
    }
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Berichte", href: "/reports" },
    { title: "Gewinn- und Verlustbericht" },
]

export default function ProfitReports({ profit, months, filters }: ProfitReportsProps) {
    const { auth } = usePage<{ auth: { user: { company?: { settings?: Record<string, string> } } } }>().props
    const settings = auth?.user?.company?.settings

    const [startDate, setStartDate] = useState(filters.start_date)
    const [endDate, setEndDate] = useState(filters.end_date)

    const handleFilter = () => {
        router.get(route("reports.profit"), { start_date: startDate, end_date: endDate })
    }

    const formatCurrency = (amount: number) => formatCurrencyUtil(amount, settings)

    const chartData = months.map((m) => ({
        Monat: m.label,
        Einnahmen: m.income,
        Ausgaben: m.expenses,
        Gewinn: m.profit,
    }))

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gewinn- und Verlustbericht" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href={route("reports.index")}>
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Zurück
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-1xl font-bold text-gray-900">Gewinn- und Verlustbericht</h1>
                            <p className="text-gray-600">Einnahmen vs. Ausgaben</p>
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
                            <CardTitle className="text-sm font-medium">Einnahmen</CardTitle>
                            <TrendingUp className="h-4 w-4 text-green-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">{formatCurrency(profit.income)}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Ausgaben</CardTitle>
                            <TrendingDown className="h-4 w-4 text-red-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-red-600">{formatCurrency(profit.expenses)}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Gewinn/Verlust</CardTitle>
                            {profit.profit >= 0 ? (
                                <TrendingUp className="h-4 w-4 text-green-600" />
                            ) : (
                                <TrendingDown className="h-4 w-4 text-red-600" />
                            )}
                        </CardHeader>
                        <CardContent>
                            <div
                                className={`text-2xl font-bold ${
                                    profit.profit >= 0 ? "text-green-600" : "text-red-600"
                                }`}
                            >
                                {formatCurrency(profit.profit)}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Chart */}
                {months.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Monatliche Entwicklung</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={400}>
                                <LineChart data={chartData}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="Monat" />
                                    <YAxis />
                                    <Tooltip formatter={(value: number) => formatCurrency(value)} />
                                    <Legend />
                                    <Line type="monotone" dataKey="Einnahmen" stroke="#22c55e" strokeWidth={2} />
                                    <Line type="monotone" dataKey="Ausgaben" stroke="#ef4444" strokeWidth={2} />
                                    <Line type="monotone" dataKey="Gewinn" stroke="#3b82f6" strokeWidth={2} />
                                </LineChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    )
}



