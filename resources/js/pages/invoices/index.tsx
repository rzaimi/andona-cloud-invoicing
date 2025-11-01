"use client"

import type React from "react"

import { useState } from "react"
import { Head, Link, router, usePage } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Badge } from "@/components/ui/badge"
import { Plus, Edit, Trash2, Search, FileText, Send } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem, Invoice } from "@/types"

interface InvoicesIndexProps {
    invoices: {
        data: Invoice[]
        links: any[]
        meta: any
    }
    filters: {
        search?: string
        status?: string
    }
}

const breadcrumbs: BreadcrumbItem[] = [{ title: "Dashboard", href: "/dashboard" }, { title: "Rechnungen" }]

export default function InvoicesIndex() {
    // @ts-ignore
    const { invoices, filters } = usePage<InvoicesIndexProps>().props
    const [search, setSearch] = useState(filters.search || "")
    const [status, setStatus] = useState(filters.status || "all")

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault()
        router.get("/invoices", { search, status }, { preserveState: true })
    }

    const handleStatusChange = (newStatus: string) => {
        setStatus(newStatus)
        router.get("/invoices", { search, status: newStatus }, { preserveState: true })
    }

    const handleDelete = (invoice: Invoice) => {
        if (confirm(`Möchten Sie die Rechnung "${invoice.number}" wirklich löschen?`)) {
            router.delete(`/invoices/${invoice.id}`)
        }
    }

    const getStatusBadge = (status: string) => {
        const statusConfig = {
            draft: { label: "Entwurf", variant: "outline" as const },
            sent: { label: "Versendet", variant: "secondary" as const },
            paid: { label: "Bezahlt", variant: "default" as const },
            overdue: { label: "Überfällig", variant: "destructive" as const },
            cancelled: { label: "Storniert", variant: "outline" as const },
        }

        const config = statusConfig[status as keyof typeof statusConfig] || statusConfig.draft
        return <Badge variant={config.variant}>{config.label}</Badge>
    }

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat("de-DE", {
            style: "currency",
            currency: "EUR",
        }).format(amount)
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Rechnungen" />

            <div className="flex flex-1 flex-col gap-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Rechnungsverwaltung</h1>
                        <p className="text-gray-600">Verwalten Sie Ihre Rechnungen und deren Status</p>
                    </div>

                    <Link href="/invoices/create">
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            Neue Rechnung
                        </Button>
                    </Link>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle>Rechnungen filtern</CardTitle>
                        <CardDescription>Suchen und filtern Sie Ihre Rechnungen</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSearch} className="flex gap-4">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                                <Input
                                    placeholder="Nach Rechnungsnummer oder Kunde suchen..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-10"
                                />
                            </div>
                            <Select value={status} onValueChange={handleStatusChange}>
                                <SelectTrigger className="w-48">
                                    <SelectValue placeholder="Status wählen" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Alle Status</SelectItem>
                                    <SelectItem value="draft">Entwurf</SelectItem>
                                    <SelectItem value="sent">Versendet</SelectItem>
                                    <SelectItem value="paid">Bezahlt</SelectItem>
                                    <SelectItem value="overdue">Überfällig</SelectItem>
                                    <SelectItem value="cancelled">Storniert</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button type="submit">Suchen</Button>
                            {(filters.search || filters.status) && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => {
                                        setSearch("")
                                        setStatus("all")
                                        router.get("/invoices")
                                    }}
                                >
                                    Zurücksetzen
                                </Button>
                            )}
                        </form>
                    </CardContent>
                </Card>

                {/* Invoices Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Rechnungen ()</CardTitle>
                        <CardDescription>Alle Rechnungen in Ihrem System</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Rechnungsnummer</TableHead>
                                    <TableHead>Kunde</TableHead>
                                    <TableHead>Rechnungsdatum</TableHead>
                                    <TableHead>Fälligkeitsdatum</TableHead>
                                    <TableHead>Betrag</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Aktionen</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {invoices.data.map((invoice) => (
                                    <TableRow key={invoice.id}>
                                        <TableCell className="font-medium">{invoice.number}</TableCell>
                                        <TableCell>{invoice.customer?.name}</TableCell>
                                        <TableCell>{new Date(invoice.issue_date).toLocaleDateString("de-DE")}</TableCell>
                                        <TableCell>{new Date(invoice.due_date).toLocaleDateString("de-DE")}</TableCell>
                                        <TableCell className="font-medium">{formatCurrency(invoice.total)}</TableCell>
                                        <TableCell>{getStatusBadge(invoice.status)}</TableCell>
                                        <TableCell>
                                            <div className="flex space-x-2">
                                                <Link href={`/invoices/${invoice.id}/edit`}>
                                                    <Button variant="outline" size="sm" title="Bearbeiten">
                                                        <Edit className="h-4 w-4" />
                                                    </Button>
                                                </Link>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => window.open(route("invoices.pdf", invoice.id), "_blank")}
                                                    className="mr-2"
                                                >
                                                    <FileText className="h-4 w-4 mr-1" />
                                                    PDF
                                                </Button>
                                                {invoice.status === "draft" && (
                                                    <Button variant="outline" size="sm" title="Versenden">
                                                        <Send className="h-4 w-4" />
                                                    </Button>
                                                )}
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => handleDelete(invoice)}
                                                    className="text-red-600 hover:text-red-700"
                                                    title="Löschen"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        {invoices.data.length === 0 && (
                            <div className="text-center py-8">
                                <p className="text-gray-500">
                                    {filters.search || filters.status ? "Keine Rechnungen gefunden." : "Noch keine Rechnungen vorhanden."}
                                </p>
                                {!filters.search && !filters.status && (
                                    <Link href="/invoices/create">
                                        <Button className="mt-4">
                                            <Plus className="mr-2 h-4 w-4" />
                                            Erste Rechnung erstellen
                                        </Button>
                                    </Link>
                                )}
                            </div>
                        )}

                        {/* Pagination */}
                        {invoices.links && invoices.links.length > 3 && (
                            <div className="flex justify-center mt-6 gap-2">
                                {invoices.links.map((link, index) => (
                                    <Button
                                        key={index}
                                        variant={link.active ? "default" : "outline"}
                                        size="sm"
                                        disabled={!link.url}
                                        onClick={() => link.url && router.get(link.url)}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    )
}
