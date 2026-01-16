"use client"

import { useState } from "react"
import { Head, Link, router } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { ArrowLeft, EuroIcon } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem } from "@/types"
import { route } from "ziggy-js"
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from "recharts"

interface VatData {
    output_vat: number
    input_vat: number
    vat_payable: number
}

interface MonthlyVatData {
    month: string
    label: string
    output_vat: number
    input_vat: number
    vat_payable: number
}

interface VatReportsProps {
    vat: VatData
    months: MonthlyVatData[]
    filters: {
        start_date: string
        end_date: string
    }
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Berichte", href: "/reports" },
    { title: "MwSt.-Bericht" },
]

export default function VatReports({ vat, months, filters }: VatReportsProps) {
    const [startDate, setStartDate] = useState(filters.start_date)
    const [endDate, setEndDate] = useState(filters.end_date)

    const handleFilter = () => {
        router.get(route("reports.vat"), { start_date: startDate, end_date: endDate })
    }

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat("de-DE", {
            style: "currency",
            currency: "EUR",
        }).format(amount)
    }

    const chartData = months.map((m) => ({
        Monat: m.label,
        "Ausgangs-MwSt.": m.output_vat,
        "Eingangs-MwSt.": m.input_vat,
        "Zu zahlende MwSt.": m.vat_payable,
    }))

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="MwSt.-Bericht" />

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
                            <h1 className="text-1xl font-bold text-gray-900">MwSt.-Bericht</h1>
                            <p className="text-gray-600">Ausgangs- vs. Eingangs-MwSt.</p>
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
                            <CardTitle className="text-sm font-medium">Ausgangs-MwSt.</CardTitle>
                            <EuroIcon className="h-4 w-4 text-blue-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600">{formatCurrency(vat.output_vat)}</div>
                            <p className="text-xs text-gray-500 mt-1">MwSt. aus Rechnungen</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Eingangs-MwSt.</CardTitle>
                            <EuroIcon className="h-4 w-4 text-green-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">{formatCurrency(vat.input_vat)}</div>
                            <p className="text-xs text-gray-500 mt-1">MwSt. aus Ausgaben</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Zu zahlende MwSt.</CardTitle>
                            <EuroIcon className="h-4 w-4 text-purple-600" />
                        </CardHeader>
                        <CardContent>
                            <div
                                className={`text-2xl font-bold ${
                                    vat.vat_payable >= 0 ? "text-purple-600" : "text-red-600"
                                }`}
                            >
                                {formatCurrency(vat.vat_payable)}
                            </div>
                            <p className="text-xs text-gray-500 mt-1">
                                {vat.vat_payable >= 0 ? "Zu zahlen" : "Erstattung"}
                            </p>
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
                                <BarChart data={chartData}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="Monat" />
                                    <YAxis />
                                    <Tooltip formatter={(value: number) => formatCurrency(value)} />
                                    <Legend />
                                    <Bar dataKey="Ausgangs-MwSt." fill="#3b82f6" />
                                    <Bar dataKey="Eingangs-MwSt." fill="#22c55e" />
                                    <Bar dataKey="Zu zahlende MwSt." fill="#a855f7" />
                                </BarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    )
}

