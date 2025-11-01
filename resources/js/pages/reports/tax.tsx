"use client"

import { useState } from "react"
import { Head, Link, router } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Receipt, ArrowLeft, Download } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem } from "@/types"
import { route } from "ziggy-js"

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
                        <Button variant="outline">
                            <Download className="mr-2 h-4 w-4" />
                            Exportieren
                        </Button>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Steuerübersicht</CardTitle>
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
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {taxData.map((item, index) => (
                                    <TableRow key={index}>
                                        <TableCell className="font-medium">{item.period}</TableCell>
                                        <TableCell className="text-right">{formatCurrency(item.subtotal)}</TableCell>
                                        <TableCell className="text-right">{formatCurrency(item.tax)}</TableCell>
                                        <TableCell className="text-right font-bold">{formatCurrency(item.total)}</TableCell>
                                    </TableRow>
                                ))}
                                <TableRow className="font-bold bg-gray-50">
                                    <TableCell>Gesamt</TableCell>
                                    <TableCell className="text-right">{formatCurrency(totalSubtotal)}</TableCell>
                                    <TableCell className="text-right">{formatCurrency(totalTax)}</TableCell>
                                    <TableCell className="text-right">{formatCurrency(totalAmount)}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {/* Summary Cards */}
                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">Gesamt MwSt.</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(totalTax)}</div>
                            <p className="text-xs text-muted-foreground mt-1">Zu zahlende Mehrwertsteuer</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">Gesamt Nettobetrag</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(totalSubtotal)}</div>
                            <p className="text-xs text-muted-foreground mt-1">Ohne Steuern</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">Gesamt Bruttobetrag</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(totalAmount)}</div>
                            <p className="text-xs text-muted-foreground mt-1">Inklusive Steuern</p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    )
}

