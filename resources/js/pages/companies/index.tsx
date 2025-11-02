"use client"

import type React from "react"

import { Head, Link, router } from "@inertiajs/react"
import { route } from "ziggy-js"
import { useState } from "react"
import AppLayout from "@/layouts/app-layout"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Badge } from "@/components/ui/badge"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { Plus, Search, MoreHorizontal, Edit, Trash2, Eye, Building2, Users, Receipt, FileText } from "lucide-react"
import type { Company, User, PaginatedResponse } from "@/types"

interface CompanyWithStats extends Company {
    users_count: number
    customers_count: number
    invoices_count: number
    offers_count: number
    users: User[]
}

interface Props {
    companies: PaginatedResponse<CompanyWithStats>
}

export default function CompaniesIndex({ companies }: Props) {
    const [search, setSearch] = useState("")

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault()
        router.get(route("companies.index"), { search }, { preserveState: true })
    }

    const handleDelete = (company: CompanyWithStats) => {
        if (confirm("Sind Sie sicher, dass Sie diese Firma löschen möchten?")) {
            router.delete(route("companies.destroy", company.id))
        }
    }

    return (
        <AppLayout breadcrumbs={[{ title: "Dashboard", href: "/dashboard" }, { title: "Firmenverwaltung" }]}>
            <Head title="Firmenverwaltung" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Firmenverwaltung</h1>
                        <p className="text-gray-600">Verwalten Sie alle Firmen im System</p>
                    </div>

                    <Button asChild>
                        <Link href={route("companies.wizard.start")}>
                            <Plus className="mr-2 h-4 w-4" />
                            Neue Firma erstellen
                        </Link>
                    </Button>
                </div>

                {/* Search */}
                <Card>
                    <CardHeader>
                        <CardTitle>Firmen suchen</CardTitle>
                        <CardDescription>Suchen Sie nach Firmen anhand von Name oder E-Mail</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSearch} className="flex gap-2">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                <Input
                                    placeholder="Firmenname oder E-Mail eingeben..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-10"
                                />
                            </div>
                            <Button type="submit">Suchen</Button>
                        </form>
                    </CardContent>
                </Card>

                {/* Companies Table */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Building2 className="h-5 w-5" />
                            Firmen ({companies.total})
                        </CardTitle>
                        <CardDescription>Alle Firmen im System</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Firma</TableHead>
                                        <TableHead>Kontakt</TableHead>
                                        <TableHead>Benutzer</TableHead>
                                        <TableHead>Statistiken</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Erstellt</TableHead>
                                        <TableHead className="w-[70px]">Aktionen</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {companies.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={7} className="text-center py-8 text-gray-500">
                                                Keine Firmen gefunden
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        companies.data.map((company) => (
                                            <TableRow key={company.id}>
                                                <TableCell>
                                                    <div className="flex items-center gap-3">
                                                        <Avatar className="h-10 w-10">
                                                            <AvatarImage src={company.logo ? `/storage/${company.logo}` : undefined} />
                                                            <AvatarFallback>
                                                                {company.name
                                                                    .split(" ")
                                                                    .map((n) => n[0])
                                                                    .join("")
                                                                    .toUpperCase()
                                                                    .slice(0, 2)}
                                                            </AvatarFallback>
                                                        </Avatar>
                                                        <div>
                                                            <div className="font-medium">{company.name}</div>
                                                            <div className="text-sm text-gray-500">
                                                                {company.city}, {company.country}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div>
                                                        <div className="font-medium">{company.email}</div>
                                                        <div className="text-sm text-gray-500">{company.phone}</div>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center gap-2">
                                                        <Badge variant="outline">{company.users_count} Benutzer</Badge>
                                                        <div className="flex -space-x-1">
                                                            {company.users.slice(0, 3).map((user) => (
                                                                <Avatar key={user.id} className="h-6 w-6 border-2 border-white">
                                                                    <AvatarFallback className="text-xs">
                                                                        {user.name
                                                                            .split(" ")
                                                                            .map((n) => n[0])
                                                                            .join("")
                                                                            .toUpperCase()}
                                                                    </AvatarFallback>
                                                                </Avatar>
                                                            ))}
                                                        </div>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex flex-wrap gap-1">
                                                        <Badge variant="secondary" className="text-xs">
                                                            <Users className="mr-1 h-3 w-3" />
                                                            {company.customers_count}
                                                        </Badge>
                                                        <Badge variant="secondary" className="text-xs">
                                                            <Receipt className="mr-1 h-3 w-3" />
                                                            {company.invoices_count}
                                                        </Badge>
                                                        <Badge variant="secondary" className="text-xs">
                                                            <FileText className="mr-1 h-3 w-3" />
                                                            {company.offers_count}
                                                        </Badge>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant={company.status === "active" ? "default" : "secondary"}>
                                                        {company.status === "active" ? "Aktiv" : "Inaktiv"}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-sm text-gray-500">
                                                    {new Date(company.created_at).toLocaleDateString("de-DE")}
                                                </TableCell>
                                                <TableCell>
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" className="h-8 w-8 p-0">
                                                                <MoreHorizontal className="h-4 w-4" />
                                                                <span className="sr-only">Aktionen öffnen</span>
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end">
                                                            <DropdownMenuLabel>Aktionen</DropdownMenuLabel>
                                                            <DropdownMenuItem asChild>
                                                                <Link href={route("companies.show", company.id)}>
                                                                    <Eye className="mr-2 h-4 w-4" />
                                                                    Anzeigen
                                                                </Link>
                                                            </DropdownMenuItem>
                                                            <DropdownMenuItem asChild>
                                                                <Link href={route("companies.edit", company.id)}>
                                                                    <Edit className="mr-2 h-4 w-4" />
                                                                    Bearbeiten
                                                                </Link>
                                                            </DropdownMenuItem>
                                                            <DropdownMenuSeparator />
                                                            <DropdownMenuItem className="text-red-600" onClick={() => handleDelete(company)}>
                                                                <Trash2 className="mr-2 h-4 w-4" />
                                                                Löschen
                                                            </DropdownMenuItem>
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {/* Pagination */}
                        {companies.last_page > 1 && (
                            <div className="flex items-center justify-between px-2 py-4">
                                <div className="text-sm text-gray-500">
                                    Zeige {companies.from} bis {companies.to} von {companies.total} Einträgen
                                </div>
                                <div className="flex gap-2">
                                    {companies.links.map((link, index) => (
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
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    )
}
