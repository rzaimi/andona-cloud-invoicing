"use client"

import type React from "react"

import { Head, Link, router, usePage } from "@inertiajs/react"
import { useState } from "react"
import { useTranslation } from "react-i18next"
import AppLayout from "@/layouts/app-layout"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Badge } from "@/components/ui/badge"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Plus, Search, Edit, Trash2, Eye, Users, Building2, AlertCircle, CheckCircle2 } from "lucide-react"
import type { User, Company, PaginatedResponse } from "@/types"

interface Props {
    users: PaginatedResponse<User & { company: Company; can_edit: boolean; can_delete: boolean }>
    search: string
    can_create: boolean
    can_manage_companies: boolean
}

export default function UsersIndex({ users, search: initialSearch, can_create, can_manage_companies }: Props) {
    const { t } = useTranslation()
    const { flash } = usePage<{ flash?: { success?: string; error?: string } }>().props
    const [search, setSearch] = useState(initialSearch ?? "")

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault()
        router.get(route("users.index"), { search }, { preserveState: true })
    }

    const handleDelete = (user: User) => {
        if (confirm(t('pages.users.deleteConfirm'))) {
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
                return t('pages.users.roleSuperAdmin')
            case "admin":
                return t('pages.users.roleAdmin')
            default:
                return t('pages.users.roleUser')
        }
    }

    return (
        <AppLayout breadcrumbs={[{ title: "Dashboard", href: "/dashboard" }, { title: t('pages.users.title')}]}>
            <Head title={t('pages.users.title')} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground">{t('pages.users.title')}</h1>
                        <p className="text-muted-foreground">{t('pages.users.subtitle')}</p>
                    </div>

                    <div className="flex gap-2">
                        {can_manage_companies && (
                            <Button variant="outline" asChild>
                                <Link href={route("companies.index")}>
                                    <Building2 className="mr-2 h-4 w-4" />
                                    {t('pages.users.manageCompanies')}
                                </Link>
                            </Button>
                        )}
                        {can_create && (
                            <Button asChild>
                                <Link href={route("users.create")}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    {t('pages.users.new')}
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                {/* Flash Messages */}
                {flash?.success && (
                    <Alert className="border-green-200 bg-green-50">
                        <CheckCircle2 className="h-4 w-4 text-green-600" />
                        <AlertDescription className="text-green-800">{flash.success}</AlertDescription>
                    </Alert>
                )}
                {flash?.error && (
                    <Alert variant="destructive">
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>{flash.error}</AlertDescription>
                    </Alert>
                )}

                {/* Search */}
                <Card>
                    <CardHeader>
                        <CardTitle>{t('common.search')}</CardTitle>
                        <CardDescription>{t('pages.users.searchPlaceholder')}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSearch} className="flex gap-2">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    placeholder={t('pages.users.searchPlaceholder')}
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-10"
                                />
                            </div>
                            <Button type="submit">{t('common.search')}</Button>
                        </form>
                    </CardContent>
                </Card>

                {/* Users Table */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Users className="h-5 w-5" />
                            {t('pages.users.title')} ({users.total})
                        </CardTitle>
                        <CardDescription>{t('pages.users.subtitle')}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>{t('common.name')}</TableHead>
                                        <TableHead>{t('nav.companies')}</TableHead>
                                        <TableHead>{t('pages.users.role')}</TableHead>
                                        <TableHead>{t('common.status')}</TableHead>
                                        <TableHead>{t('common.createdAt')}</TableHead>
                                        <TableHead className="w-[120px]">{t('common.actions')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {users.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="text-center py-8 text-muted-foreground">
                                                {t('pages.users.noUsers')}
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
                                                            <div className="text-sm text-muted-foreground">{user.email}</div>
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
                                                <TableCell className="text-sm text-muted-foreground">
                                                    {new Date(user.created_at).toLocaleDateString("de-DE")}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center gap-2">
                                                        <Button variant="ghost" size="sm" asChild title="Ansehen">
                                                            <Link href={route("users.show", user.id)}>
                                                                <Eye className="h-4 w-4" />
                                                            </Link>
                                                        </Button>
                                                        {user.can_edit && (
                                                            <Button variant="ghost" size="sm" asChild title="Bearbeiten">
                                                                <Link href={route("users.edit", user.id)}>
                                                                    <Edit className="h-4 w-4" />
                                                                </Link>
                                                            </Button>
                                                        )}
                                                        {user.can_delete && (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => handleDelete(user)}
                                                                className="text-red-600 hover:text-red-700"
                                                                title={t('common.delete')}
                                                            >
                                                                <Trash2 className="h-4 w-4" />
                                                            </Button>
                                                        )}
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
                                <div className="text-sm text-muted-foreground">
                                    {t('common.showingEntries', { from: users.from, to: users.to, total: users.total })}
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
