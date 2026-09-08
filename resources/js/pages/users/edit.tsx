"use client"

import type React from "react"

import AppLayout from "@/layouts/app-layout"
import { Head, Link, useForm } from "@inertiajs/react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Checkbox } from "@/components/ui/checkbox"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import { Separator } from "@/components/ui/separator"
import { ArrowLeft, Save, User as UserIcon, Shield, Key, FileText } from "lucide-react"
import type { Company, User } from "@/types"

interface RoleOption {
    name: string
    label: string
    description: string
}

interface PermissionGroup {
    group: string
    items: Array<{ name: string; label: string }>
}

interface Props {
    user: User & { company?: Company }
    companies: Company[]
    is_super_admin: boolean
    role_options: RoleOption[]
    permission_groups: PermissionGroup[]
    current_role: string
    assigned_permissions: string[]
}

export default function EditUser({ user, companies, is_super_admin, role_options, permission_groups, current_role, assigned_permissions }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: user.name || "",
        email: user.email || "",
        role: current_role || "user",
        status: user.status || "active",
        company_id: user.company?.id || user.company_id || "",
        password: "",
        password_confirmation: "",
        permissions: assigned_permissions || [],
        staff_number: (user as any).staff_number || "",
        department: (user as any).department || "",
        job_title: (user as any).job_title || "",
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
                    <div className="flex-1">
                        <h1 className="text-xl font-bold text-gray-900 dark:text-gray-100">Benutzer bearbeiten</h1>
                        <p className="text-muted-foreground">Aktualisieren Sie die Benutzerinformationen</p>
                    </div>
                    {user.role === "employee" && (
                        <Button variant="outline" size="sm" asChild>
                            <Link href={route("users.documents", user.id)}>
                                <FileText className="mr-2 h-4 w-4" />
                                Dokumente
                            </Link>
                        </Button>
                    )}
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

                                {/* Status */}
                                <div className="space-y-2">
                                    <Label htmlFor="status">
                                        Status <span className="text-red-500">*</span>
                                    </Label>
                                    <Select value={data.status} onValueChange={(value) => setData("status", value as typeof data.status)}>
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

                            {/* Role — exactly one, restricted to what the editor may assign */}
                            <div className="col-span-2 space-y-3">
                                <Separator />
                                <div className="flex items-center gap-2">
                                    <Shield className="h-4 w-4 text-muted-foreground" />
                                    <Label className="text-base font-semibold">
                                        Rolle <span className="text-red-500">*</span>
                                    </Label>
                                    <span className="text-xs text-muted-foreground">bestimmt die Grundberechtigungen</span>
                                </div>
                                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    {role_options.map((option) => {
                                        const selected = data.role === option.name
                                        return (
                                            <button
                                                key={option.name}
                                                type="button"
                                                onClick={() => setData("role", option.name)}
                                                aria-pressed={selected}
                                                className={
                                                    "rounded-lg border p-3 text-left transition-colors " +
                                                    (selected
                                                        ? "border-primary bg-primary/5 ring-1 ring-primary"
                                                        : "hover:border-muted-foreground/40")
                                                }
                                            >
                                                <div className="flex items-center gap-2">
                                                    <span
                                                        className={
                                                            "h-3.5 w-3.5 shrink-0 rounded-full border " +
                                                            (selected ? "border-primary bg-primary" : "border-muted-foreground/40")
                                                        }
                                                    />
                                                    <span className="text-sm font-medium">{option.label}</span>
                                                </div>
                                                <p className="mt-1 text-xs text-muted-foreground">{option.description}</p>
                                            </button>
                                        )
                                    })}
                                </div>
                                {errors.role && (
                                    <Alert variant="destructive">
                                        <AlertDescription>{errors.role}</AlertDescription>
                                    </Alert>
                                )}
                            </div>

                            {/* Additional permissions — grouped, on top of the role */}
                            <div className="col-span-2 space-y-4">
                                <Separator />
                                <div className="flex items-center gap-2">
                                    <Key className="h-4 w-4 text-muted-foreground" />
                                    <Label className="text-base font-semibold">Zusätzliche Berechtigungen</Label>
                                    <span className="text-xs text-muted-foreground">ergänzen die Rolle für einzelne Bereiche</span>
                                </div>
                                {permission_groups.map((group) => (
                                    <div key={group.group} className="space-y-2">
                                        <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                            {group.group}
                                        </p>
                                        <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                            {group.items.map((perm) => {
                                                const checked = data.permissions.includes(perm.name)
                                                return (
                                                    <div key={perm.name} className="flex items-center space-x-2">
                                                        <Checkbox
                                                            id={`perm-${perm.name}`}
                                                            checked={checked}
                                                            onCheckedChange={(val) => {
                                                                if (val) setData("permissions", [...data.permissions, perm.name])
                                                                else setData("permissions", data.permissions.filter((x: string) => x !== perm.name))
                                                            }}
                                                        />
                                                        <label htmlFor={`perm-${perm.name}`} className="text-sm cursor-pointer">
                                                            {perm.label}
                                                        </label>
                                                    </div>
                                                )
                                            })}
                                        </div>
                                    </div>
                                ))}
                                {(errors as Record<string, string>)["permissions.0"] && (
                                    <Alert variant="destructive">
                                        <AlertDescription>{(errors as Record<string, string>)["permissions.0"]}</AlertDescription>
                                    </Alert>
                                )}
                            </div>

                            {/* Employee profile fields */}
                            {data.role === "employee" && (
                                <div className="col-span-2 space-y-3">
                                    <Separator />
                                    <div className="flex items-center gap-2">
                                        <UserIcon className="h-4 w-4 text-muted-foreground" />
                                        <Label className="text-base font-semibold">Mitarbeiterprofil</Label>
                                    </div>
                                    <div className="grid gap-4 md:grid-cols-3">
                                        <div className="space-y-2">
                                            <Label htmlFor="staff_number">Personalnummer</Label>
                                            <Input
                                                id="staff_number"
                                                value={data.staff_number}
                                                onChange={(e) => setData("staff_number", e.target.value)}
                                                placeholder="z.B. MA-001"
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="department">Abteilung</Label>
                                            <Input
                                                id="department"
                                                value={data.department}
                                                onChange={(e) => setData("department", e.target.value)}
                                                placeholder="z.B. Buchhaltung"
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="job_title">Position</Label>
                                            <Input
                                                id="job_title"
                                                value={data.job_title}
                                                onChange={(e) => setData("job_title", e.target.value)}
                                                placeholder="z.B. Sachbearbeiter"
                                            />
                                        </div>
                                    </div>
                                </div>
                            )}

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
