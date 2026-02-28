"use client"

import type React from "react"

import { useState } from "react"
import { Head, Link, router, usePage } from "@inertiajs/react"
import { useTranslation } from "react-i18next"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Badge } from "@/components/ui/badge"
import { Plus, Edit, Trash2, Search, Building2, User, Eye, Download } from 'lucide-react';
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem, Customer } from "@/types"
import { route } from "ziggy-js"

interface CustomersIndexProps {
    customers: {
        data: Customer[]
        links: any[]
        meta: any
    }
    filters: {
        search?: string
    }
}

const breadcrumbs: BreadcrumbItem[] = [{ title: "Dashboard", href: "/dashboard" }, { title: "Kunden" }]

export default function CustomersIndex() {
    const { t } = useTranslation()
    const { customers, filters } = usePage<CustomersIndexProps>().props
    const [search, setSearch] = useState(filters.search || "")

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault()
        router.get("/customers", { search }, { preserveState: true })
    }

    const handleDelete = (customer: Customer) => {
        if (confirm(t('pages.customers.deleteConfirm', { name: customer.name }))) {
            router.delete(`/customers/${customer.id}`)
        }
    }

    const getCustomerTypeIcon = (type: string) => {
        return type === "business" ? <Building2 className="h-4 w-4" /> : <User className="h-4 w-4" />
    }

    const getCustomerTypeBadge = (type: string) => {
        return type === "business" ? "Unternehmen" : "Privatkunde"
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('pages.customers.title')} />

            <div className="flex flex-1 flex-col gap-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground">{t('pages.customers.title')}</h1>
                        <p className="text-muted-foreground">{t('pages.customers.subtitle')}</p>
                    </div>

                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            onClick={() => {
                                const params = new URLSearchParams()
                                if (filters.search) params.append('search', filters.search)
                                window.location.href = route('export.customers') + (params.toString() ? '?' + params.toString() : '')
                            }}
                        >
                            <Download className="mr-2 h-4 w-4" />
                            {t('common.export')}
                        </Button>
                        <Link href="/customers/create">
                            <Button>
                                <Plus className="mr-2 h-4 w-4" />
                                {t('pages.customers.new')}
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Search */}
                <Card>
                    <CardHeader>
                        <CardTitle>{t('pages.customers.searchTitle')}</CardTitle>
                        <CardDescription>{t('pages.customers.searchDesc')}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSearch} className="flex gap-2">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                                <Input
                                    placeholder="Nach Name oder E-Mail suchen..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-10"
                                />
                            </div>
                            <Button type="submit">{t('common.search')}</Button>
                            {filters.search && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => {
                                        setSearch("")
                                        router.get("/customers")
                                    }}
                                >
                                    {t('common.reset')}
                                </Button>
                            )}
                        </form>
                    </CardContent>
                </Card>

                {/* Customers Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Kunden ({customers.meta?.total ?? customers.data.length})</CardTitle>
                        <CardDescription>{t('pages.customers.allCustomers')}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Kunde</TableHead>
                                    <TableHead>Typ</TableHead>
                                    <TableHead>Kontakt</TableHead>
                                    <TableHead>Adresse</TableHead>
                                    <TableHead>Steuernummer</TableHead>
                                    <TableHead>{t('common.status')}</TableHead>
                                    <TableHead>Erstellt</TableHead>
                                    <TableHead>{t('common.actions')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {customers.data.map((customer) => (
                                    <TableRow key={customer.id}>
                                        <TableCell>
                                            <div className="flex items-center gap-2">
                                                {getCustomerTypeIcon(customer.customer_type)}
                                                <div>
                                                    <div className="font-medium">{customer.name}</div>
                                                    <div className="text-sm text-gray-500">{customer.email}</div>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="outline">{getCustomerTypeBadge(customer.customer_type)}</Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div className="text-sm">
                                                {customer.phone && <div>{customer.phone}</div>}
                                                {customer.contact_person && (
                                                    <div className="text-gray-500">Ansprechpartner: {customer.contact_person}</div>
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="text-sm">
                                                {customer.address && <div>{customer.address}</div>}
                                                {(customer.postal_code || customer.city) && (
                                                    <div>
                                                        {customer.postal_code} {customer.city}
                                                    </div>
                                                )}
                                                {customer.country && customer.country !== "Deutschland" && (
                                                    <div className="text-gray-500">{customer.country}</div>
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="text-sm">
                                                {customer.tax_number && <div>St.-Nr.: {customer.tax_number}</div>}
                                                {customer.vat_number && <div>USt-IdNr.: {customer.vat_number}</div>}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant={customer.status === "active" ? "default" : "secondary"}>
                                                {customer.status === "active" ? "Aktiv" : "Inaktiv"}
                                            </Badge>
                                        </TableCell>
                                                                        <TableCell>{customer.created_at ? new Date(customer.created_at).toLocaleDateString("de-DE") : "—"}</TableCell>
                                        <TableCell>
                                            <div className="flex space-x-2">
                                                <Button variant="ghost" size="sm" asChild>
                                                    <Link href={`/customers/${customer.id}`}>
                                                        <Eye className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                                <Link href={`/customers/${customer.id}/edit`}>
                                                    <Button variant="outline" size="sm">
                                                        <Edit className="h-4 w-4" />
                                                    </Button>
                                                </Link>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => handleDelete(customer)}
                                                    className="text-red-600 hover:text-red-700"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        {customers.data.length === 0 && (
                            <div className="text-center py-8">
                                <p className="text-gray-500">
                                    {filters.search ? "Keine Kunden gefunden." : "Noch keine Kunden vorhanden."}
                                </p>
                                {!filters.search && (
                                    <Link href="/customers/create">
                                        <Button className="mt-4">
                                            <Plus className="mr-2 h-4 w-4" />
                                            Ersten Kunden erstellen
                                        </Button>
                                    </Link>
                                )}
                            </div>
                        )}

                        {/* Pagination */}
                        {customers.links && customers.links.length > 3 && (
                            <div className="flex justify-center mt-6 gap-2">
                                {customers.links.map((link, index) => (
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
