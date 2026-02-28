"use client"

import { useMemo, useState } from "react"
import { useForm } from "@inertiajs/react"
import { useTranslation } from "react-i18next"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Switch } from "@/components/ui/switch"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Textarea } from "@/components/ui/textarea"
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog"
import { Pencil, Plus, Trash2 } from "lucide-react"
import { route } from "ziggy-js"

type SettingType = "string" | "integer" | "decimal" | "boolean" | "json"

export interface CompanySettingRow {
    id: string
    key: string
    type: SettingType
    value: any
    raw_value?: string | null
    description?: string | null
    updated_at?: string | null
}

interface CompanySettingsAdminTabProps {
    companySettings: CompanySettingRow[]
}

function formatValueForDisplay(setting: CompanySettingRow): string {
    if (setting.type === "boolean") return setting.value ? "true" : "false"
    if (setting.type === "json") {
        try {
            return JSON.stringify(setting.value, null, 2)
        } catch {
            return String(setting.raw_value ?? "")
        }
    }
    return String(setting.value ?? "")
}

function ValueEditor({
    type,
    value,
    onChange,
}: {
    type: SettingType
    value: any
    onChange: (v: any) => void
}) {
    if (type === "boolean") {
        return (
            <div className="flex items-center justify-between rounded-md border px-3 py-2">
                <span className="text-sm text-muted-foreground">false / true</span>
                <Switch checked={!!value} onCheckedChange={onChange} />
            </div>
        )
    }
    if (type === "json") {
        return (
            <Textarea
                value={value ?? ""}
                onChange={(e) => onChange(e.target.value)}
                rows={8}
                placeholder='{"key":"value"}'
                className="font-sans"
            />
        )
    }
    return (
        <Input
            type="text"
            value={value ?? ""}
            onChange={(e) => onChange(e.target.value)}
            placeholder={type === "decimal" ? "0.19" : type === "integer" ? "14" : "value"}
        />
    )
}

export default function CompanySettingsAdminTab({
    companySettings }: CompanySettingsAdminTabProps) {
    const { t } = useTranslation()
    const [query, setQuery] = useState("")
    const [typeFilter, setTypeFilter] = useState<"all" | SettingType>("all")

    const [createOpen, setCreateOpen] = useState(false)
    const [editOpen, setEditOpen] = useState(false)
    const [editTarget, setEditTarget] = useState<CompanySettingRow | null>(null)

    const createForm = useForm<{
        key: string
        type: SettingType
        value: any
        description: string | null
    }>({
        key: "",
        type: "string",
        value: "",
        description: null,
    })

    const editForm = useForm<{
        type: SettingType
        value: any
        description: string | null
    }>({
        type: "string",
        value: "",
        description: null,
    })

    const filtered = useMemo(() => {
        const q = query.trim().toLowerCase()
        return companySettings.filter((s) => {
            if (typeFilter !== "all" && s.type !== typeFilter) return false
            if (!q) return true
            return (
                s.key.toLowerCase().includes(q) ||
                (s.description ?? "").toLowerCase().includes(q) ||
                formatValueForDisplay(s).toLowerCase().includes(q)
            )
        })
    }, [companySettings, query, typeFilter])

    const openEdit = (s: CompanySettingRow) => {
        setEditTarget(s)
        const initialValue =
            s.type === "json"
                ? s.raw_value ?? (s.value ? JSON.stringify(s.value, null, 2) : "")
                : s.type === "boolean"
                    ? !!s.value
                    : s.raw_value ?? String(s.value ?? "")

        editForm.setData({
            type: s.type,
            value: initialValue,
            description: s.description ?? null,
        })
        setEditOpen(true)
    }

    const resetCreate = () => createForm.reset()

    const submitCreate = () => {
        createForm.post(route("settings.company-settings.store"), {
            onSuccess: () => {
                setCreateOpen(false)
                resetCreate()
            },
        })
    }

    const submitEdit = () => {
        if (!editTarget) return
        editForm.put(route("settings.company-settings.update", { companySetting: editTarget.id }), {
            onSuccess: () => {
                setEditOpen(false)
                setEditTarget(null)
                editForm.clearErrors()
            },
        })
    }

    const deleteSetting = (s: CompanySettingRow) => {
        if (!confirm(t('settings.confirmDeleteSetting', { key: s.key }))) return
        editForm.delete(route("settings.company-settings.destroy", { companySetting: s.id }))
    }

    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>{t('settings.advancedSettings')}</CardTitle>
                    <CardDescription>
                        {t('settings.advancedSettingsDesc')}
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div className="flex flex-1 flex-col gap-3 md:flex-row">
                            <Input
                                value={query}
                                onChange={(e) => setQuery(e.target.value)}
                                placeholder="Suche nach key, description oder value…"
                                className="md:max-w-sm"
                            />
                            <Select value={typeFilter} onValueChange={(v) => setTypeFilter(v as any)}>
                                <SelectTrigger className="md:w-52">
                                    <SelectValue placeholder="Type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Alle Typen</SelectItem>
                                    <SelectItem value="string">string</SelectItem>
                                    <SelectItem value="integer">integer</SelectItem>
                                    <SelectItem value="decimal">decimal</SelectItem>
                                    <SelectItem value="boolean">boolean</SelectItem>
                                    <SelectItem value="json">json</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                            <DialogTrigger asChild>
                                <Button
                                    onClick={() => {
                                        resetCreate()
                                        createForm.clearErrors()
                                    }}
                                >
                                    <Plus className="mr-2 h-4 w-4" />
                                    Neu
                                </Button>
                            </DialogTrigger>
                            <DialogContent className="max-w-2xl">
                                <DialogHeader>
                                    <DialogTitle>Setting erstellen</DialogTitle>
                                </DialogHeader>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label>Key</Label>
                                        <Input
                                            value={createForm.data.key}
                                            onChange={(e) => createForm.setData("key", e.target.value)}
                                            placeholder="invoice_tax_note"
                                        />
                                        {createForm.errors.key && <p className="text-sm text-red-600">{createForm.errors.key}</p>}
                                        <p className="text-xs text-muted-foreground">Erlaubt: Buchstaben, Zahlen, _, ., -</p>
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Type</Label>
                                        <Select value={createForm.data.type} onValueChange={(v) => createForm.setData("type", v as SettingType)}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="string">string</SelectItem>
                                                <SelectItem value="integer">integer</SelectItem>
                                                <SelectItem value="decimal">decimal</SelectItem>
                                                <SelectItem value="boolean">boolean</SelectItem>
                                                <SelectItem value="json">json</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        {createForm.errors.type && <p className="text-sm text-red-600">{createForm.errors.type}</p>}
                                    </div>
                                    <div className="space-y-2 md:col-span-2">
                                        <Label>Value</Label>
                                        <ValueEditor type={createForm.data.type} value={createForm.data.value} onChange={(v) => createForm.setData("value", v)} />
                                        {createForm.errors.value && <p className="text-sm text-red-600">{createForm.errors.value}</p>}
                                    </div>
                                    <div className="space-y-2 md:col-span-2">
                                        <Label>Description</Label>
                                        <Textarea
                                            value={createForm.data.description ?? ""}
                                            onChange={(e) => createForm.setData("description", e.target.value || null)}
                                            rows={3}
                                        />
                                        {createForm.errors.description && <p className="text-sm text-red-600">{createForm.errors.description}</p>}
                                    </div>
                                </div>

                                <DialogFooter>
                                    <Button variant="outline" onClick={() => setCreateOpen(false)}>
                                        {t('common.cancel')}
                                    </Button>
                                    <Button onClick={submitCreate} disabled={createForm.processing}>
                                        {createForm.processing ? "Speichert..." : "Erstellen"}
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </div>

                    <div className="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-[240px]">Key</TableHead>
                                    <TableHead className="w-[110px]">Type</TableHead>
                                    <TableHead>Value</TableHead>
                                    <TableHead className="w-[240px]">Description</TableHead>
                                    <TableHead className="w-[110px] text-right">{t('common.actions')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {filtered.map((s) => (
                                    <TableRow key={s.id} className="group">
                                        <TableCell className="text-sm">{s.key}</TableCell>
                                        <TableCell className="text-sm">{s.type}</TableCell>
                                        <TableCell className="max-w-[520px]">
                                            <div className="max-h-24 overflow-auto whitespace-pre-wrap break-words rounded bg-muted px-2 py-1 text-sm">
                                                {formatValueForDisplay(s)}
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">{s.description || "-"}</TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                <Button variant="outline" size="icon" className="h-8 w-8" onClick={() => openEdit(s)} title="Bearbeiten">
                                                    <Pencil className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="icon"
                                                    className="h-8 w-8 text-red-600 hover:text-red-700"
                                                    onClick={() => deleteSetting(s)}
                                                    title={t('common.delete')}
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {filtered.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={5} className="py-10 text-center text-sm text-muted-foreground">
                                            Keine Settings gefunden.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>

            {/* Edit dialog */}
            <Dialog open={editOpen} onOpenChange={setEditOpen}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Setting bearbeiten</DialogTitle>
                    </DialogHeader>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Key</Label>
                            <Input value={editTarget?.key ?? ""} disabled />
                        </div>
                        <div className="space-y-2">
                            <Label>Type</Label>
                            <Select value={editForm.data.type} onValueChange={(v) => editForm.setData("type", v as SettingType)}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="string">string</SelectItem>
                                    <SelectItem value="integer">integer</SelectItem>
                                    <SelectItem value="decimal">decimal</SelectItem>
                                    <SelectItem value="boolean">boolean</SelectItem>
                                    <SelectItem value="json">json</SelectItem>
                                </SelectContent>
                            </Select>
                            {editForm.errors.type && <p className="text-sm text-red-600">{editForm.errors.type}</p>}
                        </div>
                        <div className="space-y-2 md:col-span-2">
                            <Label>Value</Label>
                            <ValueEditor type={editForm.data.type} value={editForm.data.value} onChange={(v) => editForm.setData("value", v)} />
                            {editForm.errors.value && <p className="text-sm text-red-600">{editForm.errors.value}</p>}
                        </div>
                        <div className="space-y-2 md:col-span-2">
                            <Label>Description</Label>
                            <Textarea
                                value={editForm.data.description ?? ""}
                                onChange={(e) => editForm.setData("description", e.target.value || null)}
                                rows={3}
                            />
                            {editForm.errors.description && <p className="text-sm text-red-600">{editForm.errors.description}</p>}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setEditOpen(false)}>
                            {t('common.cancel')}
                        </Button>
                        <Button onClick={submitEdit} disabled={editForm.processing}>
                            {editForm.processing ? "Speichert..." : "Speichern"}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    )
}

