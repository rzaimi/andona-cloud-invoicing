"use client"

import type React from "react"

import { Head, Link, router } from "@inertiajs/react"
import { useState } from "react"
import AppLayout from "@/layouts/app-layout"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Badge } from "@/components/ui/badge"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Plus, Search, Edit, Trash2, Eye, Users, Building2 } from "lucide-react"
import type { User, Company, PaginatedResponse } from "@/types"

interface Props {
    users: PaginatedResponse<User & { company: Company }>
    can_create: boolean
    can_manage_companies: boolean
}

export default function UsersIndex({ users, can_create, can_manage_companies }: Props) {
    const [search, setSearch] = useState("")

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault()
        router.get(route("users.index"), { search }, { preserveState: true })
    }

    const handleDelete = (user: User) => {
        if (confirm("Sind Sie sicher, dass Sie diesen Benutzer löschen möchten?")) {
            router.delete(route("users.destroy", user.id))
        }
    }

    const getRoleColor = (role: string) => {
        switch (role) {
            case "super_admin":
                return "destructive"
            case "admin":
                return "default"
            default:
                return "secondary"
        }
    }

    const getRoleLabel = (role: string) => {
        switch (role) {
            case "super_admin":
                return "Super Admin"
            case "admin":
                return "Administrator"
            default:
                return "Benutzer"
        }
    }

    return (
        <AppLayout breadcrumbs={[{ title: "Dashboard", href: "/dashboard" }, { title: "Benutzerverwaltung" }]}>
            <Head title="Benutzerverwaltung" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Benutzerverwaltung</h1>
                        <p className="text-gray-600">Verwalten Sie Benutzer in Ihrem System</p>
                    </div>

                    <div className="flex gap-2">
                        {can_manage_companies && (
                            <Button variant="outline" asChild>
                                <Link href={route("companies.index")}>
                                    <Building2 className="mr-2 h-4 w-4" />
                                    Firmen verwalten
                                </Link>
                            </Button>
                        )}
                        {can_create && (
                            <Button asChild>
                                <Link href={route("users.create")}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Neuer Benutzer
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                {/* Search */}
                <Card>
                    <CardHeader>
                        <CardTitle>Benutzer suchen</CardTitle>
                        <CardDescription>Suchen Sie nach Benutzern anhand von Name oder E-Mail</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSearch} className="flex gap-2">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                <Input
                                    placeholder="Name oder E-Mail eingeben..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-10"
                                />
                            </div>
                            <Button type="submit">Suchen</Button>
                        </form>
                    </CardContent>
                </Card>

                {/* Users Table */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Users className="h-5 w-5" />
                            Benutzer ({users.total})
                        </CardTitle>
                        <CardDescription>Alle Benutzer im System</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Benutzer</TableHead>
                                        <TableHead>Firma</TableHead>
                                        <TableHead>Rolle</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Erstellt</TableHead>
                                        <TableHead className="w-[120px]">Aktionen</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {users.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="text-center py-8 text-gray-500">
                                                Keine Benutzer gefunden
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        users.data.map((user) => (
                                            <TableRow key={user.id}>
                                                <TableCell>
                                                    <div className="flex items-center gap-3">
                                                        <Avatar className="h-8 w-8">
                                                            <AvatarFallback>
                                                                {user.name
                                                                    .split(" ")
                                                                    .map((n) => n[0])
                                                                    .join("")
                                                                    .toUpperCase()}
                                                            </AvatarFallback>
                                                        </Avatar>
                                                        <div>
                                                            <div className="font-medium">{user.name}</div>
                                                            <div className="text-sm text-gray-500">{user.email}</div>
                                                        </div>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="font-medium">{user.company?.name || "Keine Firma"}</div>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant={getRoleColor(user.role)}>{getRoleLabel(user.role)}</Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant={user.status === "active" ? "default" : "secondary"}>
                                                        {user.status === "active" ? "Aktiv" : "Inaktiv"}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-sm text-gray-500">
                                                    {new Date(user.created_at).toLocaleDateString("de-DE")}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center gap-2">
                                                        <Button variant="ghost" size="sm" asChild>
                                                            <Link href={route("users.show", user.id)}>
                                                                <Eye className="h-4 w-4" />
                                                            </Link>
                                                        </Button>
                                                        <Button variant="ghost" size="sm" asChild>
                                                            <Link href={route("users.edit", user.id)}>
                                                                <Edit className="h-4 w-4" />
                                                            </Link>
                                                        </Button>
                                                        <Button 
                                                            variant="ghost" 
                                                            size="sm" 
                                                            onClick={() => handleDelete(user)}
                                                            className="text-red-600 hover:text-red-700"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {/* Pagination */}
                        {users.last_page > 1 && (
                            <div className="flex items-center justify-between px-2 py-4">
                                <div className="text-sm text-gray-500">
                                    Zeige {users.from} bis {users.to} von {users.total} Einträgen
                                </div>
                                <div className="flex gap-2">
                                    {users.links.map((link, index) => (
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
