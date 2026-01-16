"use client"

import type React from "react"

import AppLayout from "@/layouts/app-layout"
import { Head, Link, useForm } from "@inertiajs/react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { ArrowLeft, Save, User as UserIcon } from "lucide-react"
import type { Company, User } from "@/types"

interface Props {
    user: User & { company?: Company }
    companies: Company[]
    is_super_admin: boolean
    available_roles: string[]
    available_permissions: string[]
    assigned_roles: string[]
    assigned_permissions: string[]
}

export default function EditUser({ user, companies, is_super_admin, available_roles, available_permissions, assigned_roles, assigned_permissions }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: user.name || "",
        email: user.email || "",
        role: user.role || "user",
        status: user.status || "active",
        company_id: user.company?.id || user.company_id || "",
        password: "",
        password_confirmation: "",
        roles: assigned_roles || [],
        permissions: assigned_permissions || [],
    })

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        put(route("users.update", user.id))
    }

    return (
        <AppLayout
            breadcrumbs={[
                { title: "Dashboard", href: "/dashboard" },
                { title: "Benutzerverwaltung", href: "/users" },
                { title: `Bearbeiten: ${user.name}` },
            ]}
        >
            <Head title={`Benutzer bearbeiten: ${user.name}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center gap-4">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={route("users.index")}> 
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Zurück
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-1xl font-bold text-gray-900">Benutzer bearbeiten</h1>
                        <p className="text-gray-600">Aktualisieren Sie die Benutzerinformationen</p>
                    </div>
                </div>

                {/* Form */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <UserIcon className="h-5 w-5" />
                            Benutzerinformationen
                        </CardTitle>
                        <CardDescription>Passen Sie die Informationen für den Benutzer an</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid gap-6 md:grid-cols-2">
                                {/* Name */}
                                <div className="space-y-2">
                                    <Label htmlFor="name">
                                        Vollständiger Name <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData("name", e.target.value)}
                                        className={errors.name ? "border-red-500" : ""}
                                        placeholder="Max Mustermann"
                                    />
                                    {errors.name && (
                                        <Alert variant="destructive">
                                            <AlertDescription>{errors.name}</AlertDescription>
                                        </Alert>
                                    )}
                                </div>

                                {/* Email */}
                                <div className="space-y-2">
                                    <Label htmlFor="email">
                                        E-Mail-Adresse <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData("email", e.target.value)}
                                        className={errors.email ? "border-red-500" : ""}
                                        placeholder="max@beispiel.de"
                                    />
                                    {errors.email && (
                                        <Alert variant="destructive">
                                            <AlertDescription>{errors.email}</AlertDescription>
                                        </Alert>
                                    )}
                                </div>

                                {/* Role */}
                                <div className="space-y-2">
                                    <Label htmlFor="role">
                                        Rolle <span className="text-red-500">*</span>
                                    </Label>
                                    <Select value={data.role} onValueChange={(value) => setData("role", value)}>
                                        <SelectTrigger className={errors.role ? "border-red-500" : ""}>
                                            <SelectValue placeholder="Rolle auswählen" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="user">Benutzer</SelectItem>
                                            <SelectItem value="admin">Administrator</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.role && (
                                        <Alert variant="destructive">
                                            <AlertDescription>{errors.role}</AlertDescription>
                                        </Alert>
                                    )}
                                </div>

                                {/* Status */}
                                <div className="space-y-2">
                                    <Label htmlFor="status">
                                        Status <span className="text-red-500">*</span>
                                    </Label>
                                    <Select value={data.status} onValueChange={(value) => setData("status", value)}>
                                        <SelectTrigger className={errors.status ? "border-red-500" : ""}>
                                            <SelectValue placeholder="Status auswählen" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="active">Aktiv</SelectItem>
                                            <SelectItem value="inactive">Inaktiv</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.status && (
                                        <Alert variant="destructive">
                                            <AlertDescription>{errors.status}</AlertDescription>
                                        </Alert>
                                    )}
                                </div>

                                {/* Company (only for super admin) */}
                                {is_super_admin && (
                                    <div className="space-y-2">
                                        <Label htmlFor="company_id">
                                            Firma <span className="text-red-500">*</span>
                                        </Label>
                                        <Select value={data.company_id} onValueChange={(value) => setData("company_id", value)}>
                                            <SelectTrigger className={errors.company_id ? "border-red-500" : ""}>
                                                <SelectValue placeholder="Firma auswählen" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {companies.map((company) => (
                                                    <SelectItem key={company.id} value={company.id}>
                                                        {company.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.company_id && (
                                            <Alert variant="destructive">
                                                <AlertDescription>{errors.company_id}</AlertDescription>
                                            </Alert>
                                        )}
                                    </div>
                                )}

                                {/* Password */}
                                <div className="space-y-2">
                                    <Label htmlFor="password">Neues Passwort</Label>
                                    <Input
                                        id="password"
                                        type="password"
                                        value={data.password}
                                        onChange={(e) => setData("password", e.target.value)}
                                        className={errors.password ? "border-red-500" : ""}
                                        placeholder="Leer lassen, um es nicht zu ändern"
                                    />
                                    {errors.password && (
                                        <Alert variant="destructive">
                                            <AlertDescription>{errors.password}</AlertDescription>
                                        </Alert>
                                    )}
                                </div>

                                {/* Password Confirmation */}
                                <div className="space-y-2">
                                    <Label htmlFor="password_confirmation">Passwort bestätigen</Label>
                                    <Input
                                        id="password_confirmation"
                                        type="password"
                                        value={data.password_confirmation}
                                        onChange={(e) => setData("password_confirmation", e.target.value)}
                                        className={errors.password_confirmation ? "border-red-500" : ""}
                                        placeholder="Wiederholung"
                                    />
                                    {errors.password_confirmation && (
                                        <Alert variant="destructive">
                                            <AlertDescription>{errors.password_confirmation}</AlertDescription>
                                        </Alert>
                                    )}
                                </div>
                            </div>

                            {/* Roles */}
                            <div className="space-y-2 md:col-span-2">
                                <Label>Rollen</Label>
                                <div className="grid gap-2 md:grid-cols-3">
                                    {available_roles.map((r) => {
                                        const checked = data.roles.includes(r)
                                        return (
                                            <label key={r} className="flex items-center gap-2 text-sm">
                                                <input
                                                    type="checkbox"
                                                    checked={checked}
                                                    onChange={(e) => {
                                                        if (e.target.checked) setData("roles", [...data.roles, r])
                                                        else setData("roles", data.roles.filter((x: string) => x !== r))
                                                    }}
                                                />
                                                <span>{r}</span>
                                            </label>
                                        )
                                    })}
                                </div>
                            </div>

                            {/* Permissions */}
                            <div className="space-y-2 md:col-span-2">
                                <Label>Berechtigungen</Label>
                                <div className="grid gap-2 md:grid-cols-3">
                                    {available_permissions.map((p) => {
                                        const checked = data.permissions.includes(p)
                                        return (
                                            <label key={p} className="flex items-center gap-2 text-sm">
                                                <input
                                                    type="checkbox"
                                                    checked={checked}
                                                    onChange={(e) => {
                                                        if (e.target.checked) setData("permissions", [...data.permissions, p])
                                                        else setData("permissions", data.permissions.filter((x: string) => x !== p))
                                                    }}
                                                />
                                                <span>{p}</span>
                                            </label>
                                        )
                                    })}
                                </div>
                            </div>

                            {/* Submit */}
                            <div className="flex justify-end gap-4">
                                <Button variant="outline" asChild>
                                    <Link href={route("users.index")}>Abbrechen</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    <Save className="mr-2 h-4 w-4" />
                                    {processing ? "Wird gespeichert..." : "Speichern"}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    )
}
