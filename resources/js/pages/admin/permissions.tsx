"use client"

import { useState } from "react"
import { Head, useForm, router } from "@inertiajs/react"
import AppLayout from "@/layouts/app-layout"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Badge } from "@/components/ui/badge"
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
import { Alert, AlertDescription } from "@/components/ui/alert"
import { ShieldCheck, Plus, Trash2, AlertTriangle, Info } from "lucide-react"
import { route } from "ziggy-js"

// ── types ──────────────────────────────────────────────────────────────────
type PermissionItem = {
    id: string
    name: string
    roles: string[]
}

interface Props {
    permissions: PermissionItem[]
}

// ── permission metadata ────────────────────────────────────────────────────
const PERM_META: Record<string, { label: string; description: string }> = {
    manage_users:          { label: "Benutzer verwalten",  description: "Benutzer anlegen, bearbeiten und deaktivieren"             },
    manage_companies:      { label: "Firmen verwalten",    description: "Firmen und Mandanten verwalten (Plattformadmin)"           },
    manage_settings:       { label: "Einstellungen",       description: "Firmeneinstellungen und Layouts anpassen"                  },
    manage_invoices:       { label: "Rechnungen",          description: "Rechnungen erstellen, bearbeiten und versenden"            },
    manage_offers:         { label: "Angebote",            description: "Angebote erstellen und bearbeiten"                         },
    manage_products:       { label: "Produkte",            description: "Produkte und Preislisten verwalten"                        },
    view_reports:          { label: "Berichte",            description: "Berichte und Analysen einsehen"                            },
    create_stornorechnung: { label: "Stornorechnung",      description: "Korrekturrechnungen erstellen (GoBD-konform)"              },
}

const permLabel = (name: string) => PERM_META[name]?.label ?? name
const permDescription = (name: string) => PERM_META[name]?.description ?? ""

// System permissions — deletion is blocked
const SYSTEM_PERMISSIONS = [
    "manage_users",
    "manage_companies",
    "manage_settings",
    "manage_invoices",
    "manage_offers",
    "manage_products",
    "view_reports",
    "create_stornorechnung",
]

// ── component ──────────────────────────────────────────────────────────────
export default function PermissionsIndex({ permissions }: Props) {
    const [createOpen, setCreateOpen]         = useState(false)
    const [deleteTarget, setDeleteTarget]     = useState<PermissionItem | null>(null)

    const createForm = useForm({ name: "" })

    const handleCreate = () => {
        createForm.post(route("permissions.store"), {
            onSuccess: () => { setCreateOpen(false); createForm.reset() },
        })
    }

    const handleDelete = () => {
        if (!deleteTarget) return
        router.delete(route("permissions.destroy", deleteTarget.id), {
            onSuccess: () => setDeleteTarget(null),
        })
    }

    const systemPerms  = permissions.filter((p) => SYSTEM_PERMISSIONS.includes(p.name))
    const customPerms  = permissions.filter((p) => !SYSTEM_PERMISSIONS.includes(p.name))

    return (
        <AppLayout breadcrumbs={[
            { title: "Dashboard", href: "/dashboard" },
            { title: "Rollenverwaltung", href: route("roles.index") },
            { title: "Berechtigungen" },
        ]}>
            <Head title="Berechtigungen" />

            {/* ── Page header ── */}
            <div className="flex items-center justify-between mb-6">
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                        <ShieldCheck className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <h1 className="text-xl font-bold text-gray-900 dark:text-gray-100">Berechtigungen</h1>
                        <p className="text-sm text-muted-foreground">
                            Systemberechtigungen einsehen und eigene Berechtigungen erstellen
                        </p>
                    </div>
                </div>
                <Button onClick={() => setCreateOpen(true)}>
                    <Plus className="mr-2 h-4 w-4" />
                    Neue Berechtigung
                </Button>
            </div>

            {/* ── Info banner ── */}
            <Alert className="mb-6 border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-950/20 dark:text-amber-400">
                <Info className="h-4 w-4" />
                <AlertDescription>
                    Systemberechtigungen (blau) sind fest definiert und können nicht gelöscht werden.
                    Eigene Berechtigungen können frei vergeben und an Rollen zugewiesen werden.
                </AlertDescription>
            </Alert>

            {/* ── System permissions ── */}
            <Card className="mb-6">
                <CardHeader>
                    <CardTitle className="text-base">Systemberechtigungen</CardTitle>
                    <CardDescription>Diese Berechtigungen sind fest im System verankert.</CardDescription>
                </CardHeader>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="pl-6 w-56">Name (intern)</TableHead>
                                <TableHead className="w-48">Bezeichnung</TableHead>
                                <TableHead>Beschreibung</TableHead>
                                <TableHead>Zugewiesene Rollen</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {systemPerms.map((perm) => (
                                <TableRow key={perm.id}>
                                    <TableCell className="pl-6 font-mono text-xs text-muted-foreground">
                                        {perm.name}
                                    </TableCell>
                                    <TableCell className="font-medium text-sm">
                                        {permLabel(perm.name)}
                                    </TableCell>
                                    <TableCell className="text-sm text-muted-foreground">
                                        {permDescription(perm.name)}
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex flex-wrap gap-1">
                                            {perm.roles.length === 0 ? (
                                                <span className="text-xs text-muted-foreground italic">Keine</span>
                                            ) : (
                                                perm.roles.map((r) => (
                                                    <Badge key={r} variant="secondary" className="text-xs capitalize">
                                                        {r.replace("_", " ")}
                                                    </Badge>
                                                ))
                                            )}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            {/* ── Custom permissions ── */}
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Eigene Berechtigungen</CardTitle>
                    <CardDescription>
                        Selbst erstellte Berechtigungen für erweiterte Rollenkonzepte.
                    </CardDescription>
                </CardHeader>
                <CardContent className="p-0">
                    {customPerms.length === 0 ? (
                        <div className="py-12 text-center text-sm text-muted-foreground">
                            <ShieldCheck className="mx-auto mb-3 h-8 w-8 opacity-20" />
                            Noch keine eigenen Berechtigungen erstellt.
                        </div>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6 w-56">Name (intern)</TableHead>
                                    <TableHead>Zugewiesene Rollen</TableHead>
                                    <TableHead className="w-24 text-right pr-6">Aktionen</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {customPerms.map((perm) => (
                                    <TableRow key={perm.id}>
                                        <TableCell className="pl-6 font-mono text-xs">
                                            {perm.name}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-wrap gap-1">
                                                {perm.roles.length === 0 ? (
                                                    <span className="text-xs text-muted-foreground italic">Nicht zugewiesen</span>
                                                ) : (
                                                    perm.roles.map((r) => (
                                                        <Badge key={r} variant="secondary" className="text-xs capitalize">
                                                            {r.replace("_", " ")}
                                                        </Badge>
                                                    ))
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-right pr-6">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="text-destructive hover:text-destructive hover:bg-destructive/10"
                                                onClick={() => setDeleteTarget(perm)}
                                            >
                                                <Trash2 className="h-3.5 w-3.5" />
                                                <span className="sr-only">Löschen</span>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </CardContent>
            </Card>

            {/* ── Create dialog ── */}
            <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                <DialogContent className="max-w-sm">
                    <DialogHeader>
                        <DialogTitle>Neue Berechtigung erstellen</DialogTitle>
                        <DialogDescription>
                            Der Name wird als interner Schlüssel verwendet (z. B.{" "}
                            <code className="text-xs bg-muted px-1 rounded">view_analytics</code>). Verwenden Sie
                            nur Kleinbuchstaben und Unterstriche.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-2 py-2">
                        <Label>Berechtigungsname</Label>
                        <Input
                            placeholder="z. B. view_analytics"
                            value={createForm.data.name}
                            onChange={(e) => createForm.setData("name", e.target.value.toLowerCase().replace(/\s+/g, "_"))}
                            autoFocus
                        />
                        {createForm.errors.name && (
                            <p className="text-xs text-destructive">{createForm.errors.name}</p>
                        )}
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setCreateOpen(false)}>Abbrechen</Button>
                        <Button onClick={handleCreate} disabled={createForm.processing}>
                            Erstellen
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* ── Delete confirmation ── */}
            <Dialog open={!!deleteTarget} onOpenChange={(o) => !o && setDeleteTarget(null)}>
                <DialogContent className="max-w-sm">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <AlertTriangle className="h-5 w-5 text-destructive" />
                            Berechtigung löschen?
                        </DialogTitle>
                        <DialogDescription>
                            <strong>{deleteTarget?.name}</strong> wird dauerhaft entfernt.
                            {(deleteTarget?.roles.length ?? 0) > 0 && (
                                <span className="mt-2 block text-amber-600 dark:text-amber-400">
                                    Diese Berechtigung ist noch {deleteTarget!.roles.length} Rolle(n) zugewiesen
                                    und wird dort automatisch entfernt.
                                </span>
                            )}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteTarget(null)}>Abbrechen</Button>
                        <Button
                            variant="destructive"
                            onClick={handleDelete}
                        >
                            Endgültig löschen
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    )
}
