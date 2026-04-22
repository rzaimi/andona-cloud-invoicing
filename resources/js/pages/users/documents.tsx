import { Head, Link, useForm } from "@inertiajs/react"
import AppLayout from "@/layouts/app-layout"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Switch } from "@/components/ui/switch"
import { Pagination } from "@/components/pagination"
import { ArrowLeft, Upload, Download, FileText, Trash2, Eye, EyeOff } from "lucide-react"
import { route } from "ziggy-js"
import { useState } from "react"
import { router } from "@inertiajs/react"

interface Document {
    id: string
    name: string
    original_filename: string
    file_size: number
    mime_type: string
    category: string
    description?: string
    visible_to_employee: boolean
    link_type?: string
    created_at: string
}

interface Employee {
    id: string
    name: string
    email: string
    staff_number?: string
    department?: string
    job_title?: string
}

interface Props {
    employee: Employee
    documents: {
        data: Document[]
        links: any[]
        current_page: number
        last_page: number
        total?: number
    }
}

const CATEGORIES = [
    { value: "employee",  label: "Mitarbeiter" },
    { value: "company",   label: "Unternehmen" },
    { value: "financial", label: "Finanzen" },
    { value: "custom",    label: "Sonstige" },
]

const CATEGORY_LABELS: Record<string, string> = {
    payroll:   "Lohnabrechnung",
    employee:  "Mitarbeiter",
    company:   "Unternehmen",
    financial: "Finanzen",
    custom:    "Sonstige",
}

const CATEGORY_COLORS: Record<string, string> = {
    payroll:   "bg-green-100 text-green-800",
    employee:  "bg-blue-100 text-blue-800",
    company:   "bg-orange-100 text-orange-800",
    financial: "bg-yellow-100 text-yellow-800",
    custom:    "bg-gray-100 text-gray-800",
}

const EMPLOYEE_LINK_TYPES = [
    { value: "payroll",     label: "Lohnabrechnung" },
    { value: "contract",    label: "Arbeitsvertrag" },
    { value: "certificate", label: "Zeugnis / Zertifikat" },
    { value: "id_document", label: "Ausweisdokument" },
    { value: "warning",     label: "Abmahnung" },
    { value: "other",       label: "Sonstiges" },
]

const LINK_TYPE_LABELS: Record<string, string> = {
    payroll:     "Lohnabrechnung",
    contract:    "Arbeitsvertrag",
    certificate: "Zeugnis / Zertifikat",
    id_document: "Ausweisdokument",
    warning:     "Abmahnung",
    other:       "Sonstiges",
    attachment:  "Anhang",
    receipt:     "Beleg",
}

function formatFileSize(bytes: number): string {
    const units = ["B", "KB", "MB", "GB"]
    let size = bytes
    let i = 0
    while (size > 1024 && i < units.length - 1) { size /= 1024; i++ }
    return `${Math.round(size * 10) / 10} ${units[i]}`
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString("de-DE")
}

export default function UserDocuments({ employee, documents }: Props) {
    const [uploading, setUploading] = useState(false)

    const { data, setData, post, processing, errors, reset } = useForm<{
        files: File[]
        category: string
        description: string
        visible_to_employee: boolean
        linkable_type: string
        linkable_id: string
        link_type: string
        _redirect: string
    }>({
        files: [],
        category: "payroll",
        description: "",
        visible_to_employee: true,
        linkable_type: "App\\Modules\\User\\Models\\User",
        linkable_id: employee.id,
        link_type: "payroll",
        _redirect: route("users.documents", { user: employee.id }),
    })

    const handleUpload = (e: React.FormEvent) => {
        e.preventDefault()
        post(route("documents.store"), {
            forceFormData: true,
        })
    }

    const handleDelete = (doc: Document) => {
        if (confirm(`Dokument "${doc.name}" wirklich löschen?`)) {
            router.delete(route("documents.destroy", { document: doc.id }))
        }
    }

    const toggleVisibility = (doc: Document) => {
        router.put(route("documents.update", { document: doc.id }), {
            name: doc.name,
            category: doc.category,
            description: doc.description ?? "",
            visible_to_employee: !doc.visible_to_employee,
            linkable_type: "App\\Modules\\User\\Models\\User",
            linkable_id: employee.id,
        })
    }

    return (
        <AppLayout
            breadcrumbs={[
                { title: "Dashboard", href: "/dashboard" },
                { title: "Benutzerverwaltung", href: "/users" },
                { title: employee.name, href: route("users.edit", { user: employee.id }) },
                { title: "Dokumente" },
            ]}
        >
            <Head title={`Dokumente: ${employee.name}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center gap-4">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={route("users.edit", { user: employee.id })}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Zurück
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-xl font-bold text-gray-900 dark:text-gray-100">Dokumente: {employee.name}</h1>
                        {employee.department && (
                            <p className="text-muted-foreground">
                                {employee.job_title && <span>{employee.job_title} · </span>}
                                {employee.department}
                                {employee.staff_number && <span> · Nr. {employee.staff_number}</span>}
                            </p>
                        )}
                    </div>
                </div>

                {/* Upload card */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Upload className="h-5 w-5" />
                            Dokument hochladen
                        </CardTitle>
                        <CardDescription>Laden Sie Dokumente für diesen Mitarbeiter hoch (z.B. Lohnabrechnungen, Verträge)</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleUpload} className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                {/* File input */}
                                <div className="space-y-2">
                                    <Label htmlFor="files">Datei(en) <span className="text-red-500">*</span></Label>
                                    <Input
                                        id="files"
                                        type="file"
                                        multiple
                                        onChange={(e) => setData("files", Array.from(e.target.files ?? []))}
                                        className={errors.files ? "border-red-500" : ""}
                                    />
                                    {errors.files && (
                                        <Alert variant="destructive"><AlertDescription>{errors.files}</AlertDescription></Alert>
                                    )}
                                </div>

                                {/* Category */}
                                <div className="space-y-2">
                                    <Label>Kategorie</Label>
                                    <Select value={data.category} onValueChange={(v) => setData("category", v)}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {CATEGORIES.map((c) => (
                                                <SelectItem key={c.value} value={c.value}>{c.label}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                {/* Description */}
                                <div className="space-y-2 md:col-span-2">
                                    <Label htmlFor="description">Beschreibung</Label>
                                    <Input
                                        id="description"
                                        value={data.description}
                                        onChange={(e) => setData("description", e.target.value)}
                                        placeholder="Optional: kurze Beschreibung"
                                    />
                                </div>

                                {/* Verknüpfungstyp */}
                                <div className="space-y-2">
                                    <Label>Verknüpfungstyp</Label>
                                    <Select value={data.link_type} onValueChange={(v) => setData("link_type", v)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Typ auswählen" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {EMPLOYEE_LINK_TYPES.map((t) => (
                                                <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                {/* Visible to employee */}
                                <div className="flex items-center gap-3 md:col-span-2">
                                    <Switch
                                        id="visible_to_employee"
                                        checked={data.visible_to_employee}
                                        onCheckedChange={(v) => setData("visible_to_employee", v)}
                                    />
                                    <Label htmlFor="visible_to_employee">Für Mitarbeiter sichtbar</Label>
                                </div>
                            </div>

                            <div className="flex justify-end">
                                <Button type="submit" disabled={processing || data.files.length === 0}>
                                    <Upload className="mr-2 h-4 w-4" />
                                    {processing ? "Wird hochgeladen…" : "Hochladen"}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Document list */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <FileText className="h-5 w-5" />
                            Vorhandene Dokumente
                        </CardTitle>
                        {documents.total !== undefined && (
                            <CardDescription>{documents.total} Dokument(e)</CardDescription>
                        )}
                    </CardHeader>
                    <CardContent>
                        {documents.data.length === 0 ? (
                            <div className="py-12 text-center text-muted-foreground">
                                <FileText className="mx-auto mb-3 h-12 w-12 opacity-30" />
                                <p>Noch keine Dokumente für diesen Mitarbeiter.</p>
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Kategorie</TableHead>
                                        <TableHead>Verknüpfungstyp</TableHead>
                                        <TableHead>Größe</TableHead>
                                        <TableHead>Datum</TableHead>
                                        <TableHead>Sichtbar</TableHead>
                                        <TableHead className="text-right">Aktionen</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {documents.data.map((doc) => (
                                        <TableRow key={doc.id}>
                                            <TableCell>
                                                <div className="font-medium">{doc.name}</div>
                                                {doc.description && (
                                                    <div className="text-xs text-muted-foreground">{doc.description}</div>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <Badge className={CATEGORY_COLORS[doc.category] ?? CATEGORY_COLORS.custom}>
                                                    {CATEGORY_LABELS[doc.category] ?? doc.category}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-sm text-muted-foreground">
                                                {doc.link_type ? (LINK_TYPE_LABELS[doc.link_type] ?? doc.link_type) : <span className="text-muted-foreground/40">—</span>}
                                            </TableCell>
                                            <TableCell className="text-sm text-muted-foreground">
                                                {formatFileSize(doc.file_size)}
                                            </TableCell>
                                            <TableCell className="text-sm text-muted-foreground">
                                                {formatDate(doc.created_at)}
                                            </TableCell>
                                            <TableCell>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => toggleVisibility(doc)}
                                                    title={doc.visible_to_employee ? "Für Mitarbeiter sichtbar" : "Versteckt"}
                                                >
                                                    {doc.visible_to_employee
                                                        ? <Eye className="h-4 w-4 text-green-600" />
                                                        : <EyeOff className="h-4 w-4 text-muted-foreground" />
                                                    }
                                                </Button>
                                            </TableCell>
                                            <TableCell className="text-right space-x-1">
                                                <Button variant="ghost" size="icon" asChild>
                                                    <a href={route("documents.download", { document: doc.id })} title="Herunterladen">
                                                        <Download className="h-4 w-4" />
                                                    </a>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => handleDelete(doc)}
                                                    title="Löschen"
                                                    className="text-red-500 hover:text-red-700"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}

                        {documents.last_page > 1 && (
                            <div className="mt-4">
                                <Pagination links={documents.links} />
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    )
}
