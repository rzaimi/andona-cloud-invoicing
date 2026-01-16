"use client"

import type React from "react"

import { Head, Link, useForm } from "@inertiajs/react"
import AppLayout from "@/layouts/app-layout"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { ArrowLeft, Save, User } from "lucide-react"
import type { Company } from "@/types"

interface Props {
    companies: Company[]
    current_company_id: string
    is_super_admin: boolean
}

export default function CreateUser({ companies, current_company_id, is_super_admin }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: "",
        email: "",
        password: "",
        password_confirmation: "",
        role: "user",
        company_id: current_company_id,
    })

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        post(route("users.store"))
    }

    return (
        <AppLayout
            breadcrumbs={[
                { title: "Dashboard", href: "/dashboard" },
                { title: "Benutzerverwaltung", href: "/users" },
                { title: "Neuer Benutzer" },
            ]}
        >
            <Head title="Neuer Benutzer" />

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
                        <h1 className="text-1xl font-bold text-gray-900">Neuer Benutzer</h1>
                        <p className="text-gray-600">Erstellen Sie einen neuen Benutzer</p>
                    </div>
                </div>

                {/* Form */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <User className="h-5 w-5" />
                            Benutzerinformationen
                        </CardTitle>
                        <CardDescription>Geben Sie die Informationen für den neuen Benutzer ein</CardDescription>
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

                                {/* Password */}
                                <div className="space-y-2">
                                    <Label htmlFor="password">
                                        Passwort <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="password"
                                        type="password"
                                        value={data.password}
                                        onChange={(e) => setData("password", e.target.value)}
                                        className={errors.password ? "border-red-500" : ""}
                                        placeholder="Mindestens 8 Zeichen"
                                    />
                                    {errors.password && (
                                        <Alert variant="destructive">
                                            <AlertDescription>{errors.password}</AlertDescription>
                                        </Alert>
                                    )}
                                </div>

                                {/* Password Confirmation */}
                                <div className="space-y-2">
                                    <Label htmlFor="password_confirmation">
                                        Passwort bestätigen <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="password_confirmation"
                                        type="password"
                                        value={data.password_confirmation}
                                        onChange={(e) => setData("password_confirmation", e.target.value)}
                                        className={errors.password_confirmation ? "border-red-500" : ""}
                                        placeholder="Passwort wiederholen"
                                    />
                                    {errors.password_confirmation && (
                                        <Alert variant="destructive">
                                            <AlertDescription>{errors.password_confirmation}</AlertDescription>
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
                            </div>

                            {/* Submit */}
                            <div className="flex justify-end gap-4">
                                <Button variant="outline" asChild>
                                    <Link href={route("users.index")}>Abbrechen</Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    <Save className="mr-2 h-4 w-4" />
                                    {processing ? "Wird erstellt..." : "Benutzer erstellen"}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    )
}
