"use client"

import { useState } from "react"
import { Head, Link, router } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { EuroIcon, ArrowLeft, Download } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem } from "@/types"
import { route } from "ziggy-js"
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, PieChart, Pie, Cell } from "recharts"

interface TaxData {
    period: string
    subtotal: number
    tax: number
    total: number
}

interface TaxReportsProps {
    period: string
    taxData: TaxData[]
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Berichte", href: "/reports" },
    { title: "Steuerberichte" },
]

const COLORS = ['oklch(0.646 0.222 41.116)', 'oklch(0.6 0.118 184.704)', 'oklch(0.398 0.07 227.392)']

export default function TaxReports({ period, taxData }: TaxReportsProps) {
    const [selectedPeriod, setSelectedPeriod] = useState(period)

    const handlePeriodChange = (newPeriod: string) => {
        setSelectedPeriod(newPeriod)
        router.get(route("reports.tax"), { period: newPeriod })
    }

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat("de-DE", {
            style: "currency",
            currency: "EUR",
        }).format(amount)
    }

    const totalSubtotal = taxData.reduce((sum, item) => sum + item.subtotal, 0)
    const totalTax = taxData.reduce((sum, item) => sum + item.tax, 0)
    const totalAmount = taxData.reduce((sum, item) => sum + item.total, 0)
    const taxPercentage = totalSubtotal > 0 ? (totalTax / totalSubtotal) * 100 : 0

    // Prepare pie chart data
    const pieData = [
        { name: "Nettobetrag", value: totalSubtotal },
        { name: "MwSt.", value: totalTax },
    ]

    const handleExport = () => {
        const headers = ["Zeitraum", "Nettobetrag", "MwSt.", "Bruttobetrag"]
        const rows = taxData.map(item => [
            item.period,
            formatCurrency(item.subtotal),
            formatCurrency(item.tax),
            formatCurrency(item.total)
        ])
        const csvContent = [
            headers.join(","),
            ...rows.map(row => row.join(",")),
            `Gesamt,${formatCurrency(totalSubtotal)},${formatCurrency(totalTax)},${formatCurrency(totalAmount)}`
        ].join("\n")

        const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" })
        const link = document.createElement("a")
        const url = URL.createObjectURL(blob)
        link.setAttribute("href", url)
        link.setAttribute("download", `steuerbericht-${selectedPeriod}-${new Date().toISOString().split('T')[0]}.csv`)
        link.style.visibility = "hidden"
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Steuerberichte" />

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
                            <h1 className="text-3xl font-bold text-gray-900">Steuerberichte</h1>
                            <p className="text-gray-600">Steuerübersicht und MwSt-Berichte</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Select value={selectedPeriod} onValueChange={handlePeriodChange}>
                            <SelectTrigger className="w-40">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="month">Monat</SelectItem>
                                <SelectItem value="year">Jahr</SelectItem>
                            </SelectContent>
                        </Select>
                        <Button variant="outline" onClick={handleExport}>
                            <Download className="mr-2 h-4 w-4" />
                            Exportieren
                        </Button>
                    </div>
                </div>

                {/* Summary Cards */}
                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Gesamt MwSt.</CardTitle>
                            <EuroIcon className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(totalTax)}</div>
                            <p className="text-xs text-muted-foreground mt-1">
                                {taxPercentage.toFixed(2)}% Steuersatz
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Gesamt Nettobetrag</CardTitle>
                            <EuroIcon className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(totalSubtotal)}</div>
                            <p className="text-xs text-muted-foreground mt-1">Ohne Steuern</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Gesamt Bruttobetrag</CardTitle>
                            <EuroIcon className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(totalAmount)}</div>
                            <p className="text-xs text-muted-foreground mt-1">Inklusive Steuern</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Durchschnitt MwSt.</CardTitle>
                            <EuroIcon className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {formatCurrency(taxData.length > 0 ? totalTax / taxData.length : 0)}
                            </div>
                            <p className="text-xs text-muted-foreground mt-1">Pro Periode</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Charts Row */}
                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Steuerübersicht</CardTitle>
                            <CardDescription>
                                {selectedPeriod === "month" && "Monatliche Steuerübersicht der letzten 6 Monate"}
                                {selectedPeriod === "year" && "Jährliche Steuerübersicht der letzten 12 Monate"}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={300}>
                                <BarChart data={taxData}>
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
                                    <Bar dataKey="subtotal" fill="oklch(0.646 0.222 41.116)" name="Nettobetrag" />
                                    <Bar dataKey="tax" fill="oklch(0.6 0.118 184.704)" name="MwSt." />
                                    <Bar dataKey="total" fill="oklch(0.398 0.07 227.392)" name="Bruttobetrag" />
                                </BarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Verteilung</CardTitle>
                            <CardDescription>Verhältnis von Nettobetrag zu Mehrwertsteuer</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={300}>
                                <PieChart>
                                    <Pie
                                        data={pieData}
                                        cx="50%"
                                        cy="50%"
                                        labelLine={false}
                                        label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(1)}%`}
                                        outerRadius={100}
                                        fill="#8884d8"
                                        dataKey="value"
                                    >
                                        {pieData.map((entry, index) => (
                                            <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                        ))}
                                    </Pie>
                                    <Tooltip 
                                        formatter={(value: number) => formatCurrency(value)}
                                        contentStyle={{ backgroundColor: "white", border: "1px solid #e5e7eb" }}
                                    />
                                </PieChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                </div>

                {/* Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Detaillierte Steuerübersicht</CardTitle>
                        <CardDescription>
                            {selectedPeriod === "month" && "Monatliche Steuerübersicht der letzten 6 Monate"}
                            {selectedPeriod === "year" && "Jährliche Steuerübersicht der letzten 12 Monate"}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Zeitraum</TableHead>
                                    <TableHead className="text-right">Nettobetrag</TableHead>
                                    <TableHead className="text-right">MwSt.</TableHead>
                                    <TableHead className="text-right">Bruttobetrag</TableHead>
                                    <TableHead className="text-right">Steuersatz</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {taxData.map((item, index) => {
                                    const itemTaxRate = item.subtotal > 0 ? (item.tax / item.subtotal) * 100 : 0
                                    return (
                                        <TableRow key={index}>
                                            <TableCell className="font-medium">{item.period}</TableCell>
                                            <TableCell className="text-right">{formatCurrency(item.subtotal)}</TableCell>
                                            <TableCell className="text-right">{formatCurrency(item.tax)}</TableCell>
                                            <TableCell className="text-right font-bold">{formatCurrency(item.total)}</TableCell>
                                            <TableCell className="text-right text-muted-foreground">
                                                {itemTaxRate.toFixed(2)}%
                                            </TableCell>
                                        </TableRow>
                                    )
                                })}
                                <TableRow className="font-bold bg-gray-50 dark:bg-gray-800">
                                    <TableCell>Gesamt</TableCell>
                                    <TableCell className="text-right">{formatCurrency(totalSubtotal)}</TableCell>
                                    <TableCell className="text-right">{formatCurrency(totalTax)}</TableCell>
                                    <TableCell className="text-right">{formatCurrency(totalAmount)}</TableCell>
                                    <TableCell className="text-right">{taxPercentage.toFixed(2)}%</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    )
}
