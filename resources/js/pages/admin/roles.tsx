"use client"

import { useState } from "react"
import { Head, useForm, router } from "@inertiajs/react"
import AppLayout from "@/layouts/app-layout"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Badge } from "@/components/ui/badge"
import { Checkbox } from "@/components/ui/checkbox"
import { Separator } from "@/components/ui/separator"
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog"
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table"
import { Shield, Plus, Pencil, Trash2, Users, Lock, AlertTriangle } from "lucide-react"
import { route } from "ziggy-js"

// ── types ──────────────────────────────────────────────────────────────────
type Permission = {
    id: string
    name: string
    roles_count?: number
}

type Role = {
    id: string
    name: string
    users_count: number
    permissions: { id: string; name: string }[]
}

interface Props {
    roles: Role[]
    permissions: Permission[]
}

// ── permission metadata ────────────────────────────────────────────────────
const PERM_META: Record<string, { label: string; description: string; variant: "default" | "secondary" | "destructive" | "outline" }> = {
    manage_users:          { label: "Benutzer verwalten",  description: "Benutzer anlegen, bearbeiten und deaktivieren",             variant: "default"     },
    manage_companies:      { label: "Firmen verwalten",    description: "Firmen und Mandanten verwalten (Plattformadmin)",           variant: "destructive" },
    manage_settings:       { label: "Einstellungen",       description: "Firmeneinstellungen und Layouts anpassen",                  variant: "secondary"   },
    manage_invoices:       { label: "Rechnungen",          description: "Rechnungen erstellen, bearbeiten und versenden",            variant: "default"     },
    manage_offers:         { label: "Angebote",            description: "Angebote erstellen und bearbeiten",                         variant: "default"     },
    manage_products:       { label: "Produkte",            description: "Produkte und Preislisten verwalten",                        variant: "secondary"   },
    view_reports:          { label: "Berichte",            description: "Berichte und Analysen einsehen",                            variant: "outline"     },
    create_stornorechnung: { label: "Stornorechnung",      description: "Korrekturrechnungen erstellen (GoBD-konform)",              variant: "outline"     },
}

const permLabel = (name: string) => PERM_META[name]?.label ?? name
const permVariant = (name: string) => PERM_META[name]?.variant ?? "secondary"

// System roles that cannot be renamed or deleted
const SYSTEM_ROLES = ["super_admin", "admin", "user"]

// ── component ──────────────────────────────────────────────────────────────
export default function RolesIndex({ roles, permissions }: Props) {
    const [createOpen, setCreateOpen]       = useState(false)
    const [editRole, setEditRole]           = useState<Role | null>(null)
    const [deleteRole, setDeleteRole]       = useState<Role | null>(null)

    // ── create form ──────────────────────────────────────────────────────
    const createForm = useForm({ name: "", permissions: [] as string[] })

    const handleCreate = () => {
        createForm.post(route("roles.store"), {
            onSuccess: () => { setCreateOpen(false); createForm.reset() },
        })
    }

    // ── edit form ────────────────────────────────────────────────────────
    const editForm = useForm({ name: "", permissions: [] as string[] })

    const openEdit = (role: Role) => {
        setEditRole(role)
        editForm.setData({ name: role.name, permissions: role.permissions.map((p) => p.name) })
    }

    const handleUpdate = () => {
        if (!editRole) return
        editForm.put(route("roles.update", editRole.id), {
            onSuccess: () => { setEditRole(null); editForm.reset() },
        })
    }

    // ── delete ───────────────────────────────────────────────────────────
    const handleDelete = () => {
        if (!deleteRole) return
        router.delete(route("roles.destroy", deleteRole.id), {
            onSuccess: () => setDeleteRole(null),
        })
    }

    // ── helpers ──────────────────────────────────────────────────────────
    const togglePerm = (
        form: typeof createForm | typeof editForm,
        perm: string,
    ) => {
        const current = form.data.permissions
        form.setData("permissions",
            current.includes(perm)
                ? current.filter((p) => p !== perm)
                : [...current, perm],
        )
    }

    // ── stats ─────────────────────────────────────────────────────────────
    const totalUsers = roles.reduce((s, r) => s + r.users_count, 0)

    return (
        <AppLayout breadcrumbs={[{ title: "Dashboard", href: "/dashboard" }, { title: "Rollen & Berechtigungen" }]}>
            <Head title="Rollenverwaltung" />

            {/* ── Page header ── */}
            <div className="flex items-center justify-between mb-6">
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                        <Shield className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <h1 className="text-xl font-bold text-gray-900 dark:text-gray-100">Rollenverwaltung</h1>
                        <p className="text-sm text-muted-foreground">
                            Rollen definieren und Berechtigungen zuweisen
                        </p>
                    </div>
                </div>
                <Button onClick={() => setCreateOpen(true)}>
                    <Plus className="mr-2 h-4 w-4" />
                    Neue Rolle
                </Button>
            </div>

            {/* ── Stats ── */}
            <div className="grid grid-cols-3 gap-4 mb-6">
                <Card>
                    <CardContent className="pt-5">
                        <p className="text-xs text-muted-foreground uppercase tracking-wide mb-1">Rollen</p>
                        <p className="text-2xl font-bold">{roles.length}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="pt-5">
                        <p className="text-xs text-muted-foreground uppercase tracking-wide mb-1">Berechtigungen</p>
                        <p className="text-2xl font-bold">{permissions.length}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="pt-5">
                        <p className="text-xs text-muted-foreground uppercase tracking-wide mb-1">Zugewiesene Nutzer</p>
                        <p className="text-2xl font-bold">{totalUsers}</p>
                    </CardContent>
                </Card>
            </div>

            {/* ── Roles table ── */}
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Alle Rollen</CardTitle>
                    <CardDescription>
                        Systemrollen (
                        <Lock className="inline h-3 w-3 mb-0.5" />
                        ) können nicht gelöscht werden.
                    </CardDescription>
                </CardHeader>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="pl-6">Rolle</TableHead>
                                <TableHead>Berechtigungen</TableHead>
                                <TableHead className="w-28 text-center">Nutzer</TableHead>
                                <TableHead className="w-28 text-right pr-6">Aktionen</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {roles.map((role) => {
                                const isSystem = SYSTEM_ROLES.includes(role.name)
                                return (
                                    <TableRow key={role.id}>
                                        {/* Name */}
                                        <TableCell className="pl-6 font-medium">
                                            <div className="flex items-center gap-2">
                                                {isSystem && (
                                                    <Lock className="h-3.5 w-3.5 text-muted-foreground shrink-0" />
                                                )}
                                                <span className="capitalize">{role.name.replace("_", " ")}</span>
                                            </div>
                                        </TableCell>

                                        {/* Permissions */}
                                        <TableCell>
                                            <div className="flex flex-wrap gap-1.5">
                                                {role.permissions.length === 0 ? (
                                                    <span className="text-xs text-muted-foreground italic">Keine</span>
                                                ) : (
                                                    role.permissions.map((p) => (
                                                        <Badge key={p.id} variant={permVariant(p.name)} className="text-xs">
                                                            {permLabel(p.name)}
                                                        </Badge>
                                                    ))
                                                )}
                                            </div>
                                        </TableCell>

                                        {/* User count */}
                                        <TableCell className="text-center">
                                            <div className="flex items-center justify-center gap-1.5 text-sm text-muted-foreground">
                                                <Users className="h-3.5 w-3.5" />
                                                {role.users_count}
                                            </div>
                                        </TableCell>

                                        {/* Actions */}
                                        <TableCell className="text-right pr-6">
                                            <div className="flex items-center justify-end gap-2">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => openEdit(role)}
                                                >
                                                    <Pencil className="h-3.5 w-3.5" />
                                                    <span className="sr-only">Bearbeiten</span>
                                                </Button>
                                                {!isSystem && (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-destructive hover:text-destructive hover:bg-destructive/10"
                                                        onClick={() => setDeleteRole(role)}
                                                        disabled={role.users_count > 0}
                                                        title={role.users_count > 0 ? "Rolle wird noch verwendet" : "Löschen"}
                                                    >
                                                        <Trash2 className="h-3.5 w-3.5" />
                                                        <span className="sr-only">Löschen</span>
                                                    </Button>
                                                )}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                )
                            })}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            {/* ── Permission matrix (read-only overview) ── */}
            <Card className="mt-6">
                <CardHeader>
                    <CardTitle className="text-base">Berechtigungsmatrix</CardTitle>
                    <CardDescription>Übersicht aller Berechtigungen und deren Zuweisung</CardDescription>
                </CardHeader>
                <CardContent className="overflow-x-auto p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="pl-6 w-64">Berechtigung</TableHead>
                                {roles.map((r) => (
                                    <TableHead key={r.id} className="text-center capitalize min-w-[100px]">
                                        {r.name.replace("_", " ")}
                                    </TableHead>
                                ))}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {permissions.map((perm) => (
                                <TableRow key={perm.id}>
                                    <TableCell className="pl-6">
                                        <div>
                                            <p className="text-sm font-medium">{permLabel(perm.name)}</p>
                                            <p className="text-xs text-muted-foreground">{PERM_META[perm.name]?.description ?? perm.name}</p>
                                        </div>
                                    </TableCell>
                                    {roles.map((role) => {
                                        const has = role.permissions.some((p) => p.name === perm.name)
                                        return (
                                            <TableCell key={role.id} className="text-center">
                                                {has ? (
                                                    <span className="text-green-600 font-bold text-base">✓</span>
                                                ) : (
                                                    <span className="text-muted-foreground/30 text-base">–</span>
                                                )}
                                            </TableCell>
                                        )
                                    })}
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            {/* ── Create dialog ── */}
            <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Neue Rolle erstellen</DialogTitle>
                        <DialogDescription>
                            Vergeben Sie einen Namen und wählen Sie die Berechtigungen für diese Rolle.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-2">
                        <div className="space-y-2">
                            <Label>Rollenname</Label>
                            <Input
                                placeholder="z. B. buchhalter"
                                value={createForm.data.name}
                                onChange={(e) => createForm.setData("name", e.target.value)}
                                autoFocus
                            />
                            {createForm.errors.name && (
                                <p className="text-xs text-destructive">{createForm.errors.name}</p>
                            )}
                        </div>

                        <Separator />

                        <div className="space-y-2">
                            <Label>Berechtigungen</Label>
                            <div className="space-y-2.5 max-h-60 overflow-y-auto pr-1">
                                {permissions.map((perm) => (
                                    <div key={perm.id} className="flex items-start gap-3">
                                        <Checkbox
                                            id={`create-${perm.name}`}
                                            checked={createForm.data.permissions.includes(perm.name)}
                                            onCheckedChange={() => togglePerm(createForm, perm.name)}
                                            disabled={perm.name === "manage_companies"}
                                        />
                                        <div className="grid gap-0.5 leading-none">
                                            <label
                                                htmlFor={`create-${perm.name}`}
                                                className="text-sm font-medium cursor-pointer"
                                            >
                                                {permLabel(perm.name)}
                                            </label>
                                            <p className="text-xs text-muted-foreground">
                                                {PERM_META[perm.name]?.description ?? ""}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setCreateOpen(false)}>Abbrechen</Button>
                        <Button onClick={handleCreate} disabled={createForm.processing}>
                            Rolle erstellen
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* ── Edit dialog ── */}
            <Dialog open={!!editRole} onOpenChange={(o) => !o && setEditRole(null)}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Rolle bearbeiten</DialogTitle>
                        <DialogDescription>
                            Passen Sie Name und Berechtigungen an.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-2">
                        <div className="space-y-2">
                            <Label>Rollenname</Label>
                            <Input
                                value={editForm.data.name}
                                onChange={(e) => editForm.setData("name", e.target.value)}
                                disabled={editRole ? SYSTEM_ROLES.includes(editRole.name) : false}
                            />
                            {editForm.errors.name && (
                                <p className="text-xs text-destructive">{editForm.errors.name}</p>
                            )}
                        </div>

                        <Separator />

                        <div className="space-y-2">
                            <Label>Berechtigungen</Label>
                            <div className="space-y-2.5 max-h-60 overflow-y-auto pr-1">
                                {permissions.map((perm) => {
                                    const isSuperAdmin = editRole?.name === "super_admin"
                                    const isPlatform = perm.name === "manage_companies"
                                    const locked = isSuperAdmin || (!isSuperAdmin && isPlatform)
                                    return (
                                        <div key={perm.id} className="flex items-start gap-3">
                                            <Checkbox
                                                id={`edit-${perm.name}`}
                                                checked={editForm.data.permissions.includes(perm.name)}
                                                onCheckedChange={() => !locked && togglePerm(editForm, perm.name)}
                                                disabled={locked}
                                            />
                                            <div className="grid gap-0.5 leading-none">
                                                <label
                                                    htmlFor={`edit-${perm.name}`}
                                                    className={`text-sm font-medium ${locked ? "opacity-60 cursor-default" : "cursor-pointer"}`}
                                                >
                                                    {permLabel(perm.name)}
                                                    {isPlatform && (
                                                        <Badge variant="outline" className="ml-2 text-[10px]">
                                                            <Lock className="mr-1 h-2.5 w-2.5" />
                                                            Plattform
                                                        </Badge>
                                                    )}
                                                </label>
                                                <p className="text-xs text-muted-foreground">
                                                    {PERM_META[perm.name]?.description ?? ""}
                                                </p>
                                            </div>
                                        </div>
                                    )
                                })}
                            </div>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setEditRole(null)}>Abbrechen</Button>
                        <Button onClick={handleUpdate} disabled={editForm.processing}>
                            Speichern
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* ── Delete confirmation ── */}
            <Dialog open={!!deleteRole} onOpenChange={(o) => !o && setDeleteRole(null)}>
                <DialogContent className="max-w-sm">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <AlertTriangle className="h-5 w-5 text-destructive" />
                            Rolle löschen?
                        </DialogTitle>
                        <DialogDescription>
                            Die Rolle <strong>{deleteRole?.name}</strong> wird dauerhaft gelöscht. Diese Aktion
                            kann nicht rückgängig gemacht werden.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteRole(null)}>Abbrechen</Button>
                        <Button variant="destructive" onClick={handleDelete}>
                            Endgültig löschen
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    )
}
