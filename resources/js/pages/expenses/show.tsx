"use client"

import type React from "react"
import { Head, Link, router, usePage } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { ArrowLeft, Edit, Trash2, Download } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem } from "@/types"

interface Expense {
    id: string
    category_id?: string
    title: string
    description?: string
    amount: number
    vat_rate: number
    vat_amount: number
    net_amount: number
    expense_date: string
    payment_method?: string
    reference?: string
    receipt_path?: string
    category?: {
        id: string
        name: string
    }
    user?: {
        id: string
        name: string
    }
    created_at: string
    updated_at: string
}

interface ExpensesShowProps {
    expense: Expense
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Ausgaben", href: "/expenses" },
    { title: "Ausgabedetails" },
]

export default function ExpensesShow() {
    // @ts-ignore
    const { expense } = usePage<ExpensesShowProps>().props

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat("de-DE", {
            style: "currency",
            currency: "EUR",
        }).format(amount)
    }

    const formatDate = (date: string) => {
        return new Date(date).toLocaleDateString("de-DE", {
            year: "numeric",
            month: "long",
            day: "numeric",
        })
    }

    const formatDateTime = (date: string) => {
        return new Date(date).toLocaleString("de-DE", {
            year: "numeric",
            month: "long",
            day: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        })
    }

    const handleDelete = () => {
        if (confirm("Möchten Sie diese Ausgabe wirklich löschen?")) {
            router.delete(`/expenses/${expense.id}`)
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Ausgabedetails" />

            <div className="flex flex-1 flex-col gap-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href="/expenses">
                            <Button variant="ghost" size="sm">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Zurück
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-1xl font-bold text-gray-900">Ausgabedetails</h1>
                            <p className="text-gray-600">Details zur Ausgabe</p>
                        </div>
                    </div>

                    <div className="flex gap-2">
                        <Link href={`/expenses/${expense.id}/edit`}>
                            <Button variant="outline">
                                <Edit className="mr-2 h-4 w-4" />
                                Bearbeiten
                            </Button>
                        </Link>
                        <Button variant="destructive" onClick={handleDelete}>
                            <Trash2 className="mr-2 h-4 w-4" />
                            Löschen
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Main Details */}
                    <div className="lg:col-span-2 space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Ausgabeninformationen</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <div className="text-sm text-gray-600">Titel</div>
                                    <div className="text-xl font-bold">{expense.title}</div>
                                </div>

                                {expense.description && (
                                    <div className="pt-4 border-t">
                                        <div className="text-sm text-gray-600">Beschreibung</div>
                                        <div className="mt-1 whitespace-pre-wrap">{expense.description}</div>
                                    </div>
                                )}

                                <div className="grid grid-cols-3 gap-4 pt-4 border-t">
                                    <div>
                                        <div className="text-sm text-gray-600">Netto-Betrag</div>
                                        <div className="text-xl font-bold">{formatCurrency(expense.net_amount)}</div>
                                    </div>
                                    <div>
                                        <div className="text-sm text-gray-600">MwSt. ({Math.round(expense.vat_rate * 100)}%)</div>
                                        <div className="text-xl font-bold">{formatCurrency(expense.vat_amount)}</div>
                                    </div>
                                    <div>
                                        <div className="text-sm text-gray-600">Gesamtbetrag</div>
                                        <div className="text-xl font-bold text-blue-600">
                                            {formatCurrency(expense.amount)}
                                        </div>
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-4 pt-4 border-t">
                                    <div>
                                        <div className="text-sm text-gray-600">Ausgabedatum</div>
                                        <div className="font-medium">{formatDate(expense.expense_date)}</div>
                                    </div>
                                    {expense.category && (
                                        <div>
                                            <div className="text-sm text-gray-600">Kategorie</div>
                                            <div className="font-medium">{expense.category.name}</div>
                                        </div>
                                    )}
                                </div>

                                {expense.payment_method && (
                                    <div className="pt-4 border-t">
                                        <div className="text-sm text-gray-600">Zahlungsmethode</div>
                                        <div className="font-medium">{expense.payment_method}</div>
                                    </div>
                                )}

                                {expense.reference && (
                                    <div className="pt-4 border-t">
                                        <div className="text-sm text-gray-600">Referenz</div>
                                        <div className="font-medium">{expense.reference}</div>
                                    </div>
                                )}

                                {expense.receipt_path && (
                                    <div className="pt-4 border-t">
                                        <div className="text-sm text-gray-600 mb-2">Beleg</div>
                                        <Link href={`/expenses/${expense.id}/receipt`}>
                                            <Button variant="outline">
                                                <Download className="mr-2 h-4 w-4" />
                                                Beleg herunterladen
                                            </Button>
                                        </Link>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Sidebar */}
                    <div className="lg:col-span-1 space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Metadaten</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <div className="text-sm text-gray-600">Erstellt am</div>
                                    <div className="font-medium">{formatDateTime(expense.created_at)}</div>
                                </div>
                                <div>
                                    <div className="text-sm text-gray-600">Zuletzt aktualisiert</div>
                                    <div className="font-medium">{formatDateTime(expense.updated_at)}</div>
                                </div>
                                {expense.user && (
                                    <div>
                                        <div className="text-sm text-gray-600">Erstellt von</div>
                                        <div className="font-medium">{expense.user.name}</div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    )
}

