"use client"

import AppLayout from "@/layouts/app-layout"
import { Head, Link } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Separator } from "@/components/ui/separator"
import { ArrowLeft, Edit, Mail, User as UserIcon, Building2, Shield, Key, Calendar } from "lucide-react"
import type { Company, User } from "@/types"

const PERMISSION_LABELS: Record<string, string> = {
    manage_users: "Benutzer verwalten",
    manage_companies: "Firmen verwalten",
    manage_settings: "Einstellungen verwalten",
    manage_invoices: "Rechnungen verwalten",
    manage_offers: "Angebote verwalten",
    manage_products: "Produkte verwalten",
    view_reports: "Berichte anzeigen",
    create_stornorechnung: "Stornorechnungen erstellen",
}

const ROLE_LABELS: Record<string, string> = {
    super_admin: "Super Admin",
    admin: "Administrator",
    user: "Benutzer",
}

const getRoleVariant = (role: string): "default" | "destructive" | "secondary" | "outline" => {
    if (role === "super_admin") return "destructive"
    if (role === "admin") return "default"
    return "secondary"
}

interface Props {
    user: User & { company?: Company }
    can_edit: boolean
    roles: string[]
    permissions: string[]
}

export default function UserShow({ user, can_edit, roles, permissions }: Props) {
    return (
        <AppLayout
            breadcrumbs={[
                { title: "Dashboard", href: "/dashboard" },
                { title: "Benutzerverwaltung", href: "/users" },
                { title: user.name },
            ]}
        >
            <Head title={`Benutzer: ${user.name}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <Button variant="outline" asChild>
                        <Link href={route("users.index")}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Zurück
                        </Link>
                    </Button>
                    {can_edit && (
                        <Button asChild>
                            <Link href={route("users.edit", user.id)}>
                                <Edit className="mr-2 h-4 w-4" />
                                Bearbeiten
                            </Link>
                        </Button>
                    )}
                </div>

                {/* Basic Info */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <UserIcon className="h-5 w-5" />
                            {user.name}
                        </CardTitle>
                        <CardDescription>Benutzerdetails</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-6 md:grid-cols-2">
                            <div className="space-y-1">
                                <div className="text-sm text-muted-foreground">E-Mail</div>
                                <div className="flex items-center gap-2">
                                    <Mail className="h-4 w-4 text-muted-foreground" />
                                    <span>{user.email}</span>
                                </div>
                            </div>

                            <div className="space-y-1">
                                <div className="text-sm text-muted-foreground">Firma</div>
                                <div className="flex items-center gap-2">
                                    <Building2 className="h-4 w-4 text-muted-foreground" />
                                    <span>{user.company?.name || "Keine Firma"}</span>
                                </div>
                            </div>

                            <div className="space-y-1">
                                <div className="text-sm text-muted-foreground">Status</div>
                                <Badge variant={user.status === "active" ? "default" : "secondary"}>
                                    {user.status === "active" ? "Aktiv" : "Inaktiv"}
                                </Badge>
                            </div>

                            <div className="space-y-1">
                                <div className="text-sm text-muted-foreground">Erstellt am</div>
                                <div className="flex items-center gap-2">
                                    <Calendar className="h-4 w-4 text-muted-foreground" />
                                    <span>{new Date(user.created_at).toLocaleDateString("de-DE")}</span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Roles & Permissions */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Shield className="h-5 w-5" />
                            Rollen &amp; Berechtigungen
                        </CardTitle>
                        <CardDescription>Zugewiesene Rollen und aktive Berechtigungen dieses Benutzers</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        {/* Roles */}
                        <div className="space-y-2">
                            <div className="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                <Shield className="h-3.5 w-3.5" />
                                Rollen
                            </div>
                            {roles.length > 0 ? (
                                <div className="flex flex-wrap gap-2">
                                    {roles.map((r) => (
                                        <Badge key={r} variant={getRoleVariant(r)}>
                                            {ROLE_LABELS[r] ?? r}
                                        </Badge>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground italic">Keine Rollen zugewiesen</p>
                            )}
                        </div>

                        <Separator />

                        {/* Permissions */}
                        <div className="space-y-2">
                            <div className="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                <Key className="h-3.5 w-3.5" />
                                Aktive Berechtigungen (inkl. Rollen)
                            </div>
                            {permissions.length > 0 ? (
                                <div className="flex flex-wrap gap-2">
                                    {permissions.map((p) => (
                                        <Badge key={p} variant="outline">
                                            {PERMISSION_LABELS[p] ?? p}
                                        </Badge>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground italic">Keine Berechtigungen</p>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    )
}
