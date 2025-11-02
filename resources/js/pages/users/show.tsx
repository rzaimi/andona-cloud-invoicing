"use client"

import type React from "react"

import AppLayout from "@/layouts/app-layout"
import { Head, Link, router } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { ArrowLeft, Edit, Mail, User as UserIcon, Building2 } from "lucide-react"
import type { Company, User } from "@/types"

interface Props {
    user: User & { company?: Company }
    can_edit: boolean
}

export default function UserShow({ user, can_edit }: Props) {
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
                                <div className="text-sm text-gray-500">E-Mail</div>
                                <div className="flex items-center gap-2">
                                    <Mail className="h-4 w-4 text-gray-500" />
                                    <span>{user.email}</span>
                                </div>
                            </div>

                            <div className="space-y-1">
                                <div className="text-sm text-gray-500">Firma</div>
                                <div className="flex items-center gap-2">
                                    <Building2 className="h-4 w-4 text-gray-500" />
                                    <span>{user.company?.name || "Keine Firma"}</span>
                                </div>
                            </div>

                            <div className="space-y-1">
                                <div className="text-sm text-gray-500">Rolle</div>
                                <Badge variant={user.role === "admin" ? "default" : user.role === "super_admin" ? "destructive" : "secondary"}>
                                    {user.role === "super_admin" ? "Super Admin" : user.role === "admin" ? "Administrator" : "Benutzer"}
                                </Badge>
                            </div>

                            <div className="space-y-1">
                                <div className="text-sm text-gray-500">Status</div>
                                <Badge variant={user.status === "active" ? "default" : "secondary"}>
                                    {user.status === "active" ? "Aktiv" : "Inaktiv"}
                                </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    )
}
