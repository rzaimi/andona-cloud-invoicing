"use client"

import { useState } from "react"
import { Head, Link, router } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Euro, ArrowLeft, Download } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem } from "@/types"
import { route } from "ziggy-js"

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

export default function RevenueReports({ period, revenueData }: RevenueReportsProps) {
    const [selectedPeriod, setSelectedPeriod] = useState(period)

    const handlePeriodChange = (newPeriod: string) => {
        setSelectedPeriod(newPeriod)
        router.get(route("reports.revenue"), { period: newPeriod })
    }

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat("de-DE", {
            style: "currency",
            currency: "EUR",
        }).format(amount)
    }

    const totalRevenue = revenueData.reduce((sum, item) => sum + item.revenue, 0)
    const totalInvoices = revenueData.reduce((sum, item) => sum + item.invoices, 0)

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Umsatzberichte" />

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
                            <h1 className="text-3xl font-bold text-gray-900">Umsatzberichte</h1>
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
                        <Button variant="outline">
                            <Download className="mr-2 h-4 w-4" />
                            Exportieren
                        </Button>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Umsatzübersicht</CardTitle>
                        <CardDescription>
                            {selectedPeriod === "month" && "Monatliche Umsätze der letzten 6 Monate"}
                            {selectedPeriod === "quarter" && "Quartalsumsätze der letzten 4 Quartale"}
                            {selectedPeriod === "year" && "Jährliche Umsätze der letzten 12 Monate"}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Zeitraum</TableHead>
                                    <TableHead className="text-right">Anzahl Rechnungen</TableHead>
                                    <TableHead className="text-right">Umsatz</TableHead>
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
                                    </TableRow>
                                ))}
                                <TableRow className="font-bold bg-gray-50">
                                    <TableCell>Gesamt</TableCell>
                                    <TableCell className="text-right">{totalInvoices}</TableCell>
                                    <TableCell className="text-right">{formatCurrency(totalRevenue)}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {/* Summary Cards */}
                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">Gesamtumsatz</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(totalRevenue)}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">Durchschnitt pro Periode</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {formatCurrency(revenueData.length > 0 ? totalRevenue / revenueData.length : 0)}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">Gesamt Rechnungen</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{totalInvoices}</div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    )
}

