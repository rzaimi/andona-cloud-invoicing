import { Head, router } from "@inertiajs/react"
import EmployeeLayout from "@/layouts/employee-layout"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Badge } from "@/components/ui/badge"
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Pagination } from "@/components/pagination"
import { Download, FileText, Search } from "lucide-react"
import { route } from "ziggy-js"
import { useState } from "react"

interface Document {
    id: string
    name: string
    original_filename: string
    file_size: number
    mime_type: string
    category: string
    description?: string
    created_at: string
    formatted_size?: string
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
    documents: {
        data: Document[]
        links: any[]
        current_page: number
        last_page: number
        total?: number
    }
    employee: Employee
    filters: {
        search?: string
        category?: string
    }
}

const CATEGORIES = [
    { value: "all", label: "Alle Kategorien" },
    { value: "payroll", label: "Lohnabrechnung" },
    { value: "employee", label: "Mitarbeiter" },
    { value: "contract", label: "Vertrag" },
    { value: "company", label: "Unternehmen" },
    { value: "custom", label: "Sonstige" },
]

const CATEGORY_LABELS: Record<string, string> = {
    payroll: "Lohnabrechnung",
    employee: "Mitarbeiter",
    contract: "Vertrag",
    company: "Unternehmen",
    financial: "Finanzen",
    custom: "Sonstige",
}

const CATEGORY_COLORS: Record<string, string> = {
    payroll: "bg-green-100 text-green-800",
    employee: "bg-blue-100 text-blue-800",
    contract: "bg-purple-100 text-purple-800",
    company: "bg-orange-100 text-orange-800",
    financial: "bg-yellow-100 text-yellow-800",
    custom: "bg-gray-100 text-gray-800",
}

function formatFileSize(bytes: number): string {
    const units = ["B", "KB", "MB", "GB"]
    let size = bytes
    let i = 0
    while (size > 1024 && i < units.length - 1) {
        size /= 1024
        i++
    }
    return `${Math.round(size * 10) / 10} ${units[i]}`
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString("de-DE", { year: "numeric", month: "2-digit", day: "2-digit" })
}

export default function EmployeeDocuments({ documents, employee, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? "")
    const [category, setCategory] = useState(filters.category ?? "all")

    const applyFilters = (overrides: Partial<typeof filters> = {}) => {
        router.get(
            route("portal.documents"),
            { search, category, ...overrides },
            { preserveState: true, replace: true }
        )
    }

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault()
        applyFilters()
    }

    return (
        <EmployeeLayout>
            <Head title="Meine Dokumente" />

            {/* Header */}
            <div className="mb-6">
                <h1 className="text-xl font-bold text-gray-900 dark:text-gray-100">Meine Dokumente</h1>
                {employee.department && (
                    <p className="mt-1 text-muted-foreground">
                        {employee.job_title && <span>{employee.job_title} · </span>}
                        {employee.department}
                        {employee.staff_number && <span> · Nr. {employee.staff_number}</span>}
                    </p>
                )}
            </div>

            {/* Filters */}
            <Card className="mb-6">
                <CardContent className="pt-4">
                    <form onSubmit={handleSearch} className="flex flex-wrap gap-3">
                        <div className="relative flex-1 min-w-48">
                            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Dokumente suchen…"
                                className="pl-9"
                            />
                        </div>
                        <Select
                            value={category}
                            onValueChange={(val) => {
                                setCategory(val)
                                applyFilters({ category: val })
                            }}
                        >
                            <SelectTrigger className="w-48">
                                <SelectValue placeholder="Kategorie" />
                            </SelectTrigger>
                            <SelectContent>
                                {CATEGORIES.map((c) => (
                                    <SelectItem key={c.value} value={c.value}>{c.label}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Button type="submit" variant="secondary">Suchen</Button>
                    </form>
                </CardContent>
            </Card>

            {/* Document list */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <FileText className="h-5 w-5" />
                        Dokumente
                    </CardTitle>
                    {documents.total !== undefined && (
                        <CardDescription>{documents.total} Dokument(e)</CardDescription>
                    )}
                </CardHeader>
                <CardContent>
                    {documents.data.length === 0 ? (
                        <div className="py-12 text-center text-muted-foreground">
                            <FileText className="mx-auto mb-3 h-12 w-12 opacity-30" />
                            <p>Keine Dokumente vorhanden.</p>
                        </div>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Kategorie</TableHead>
                                    <TableHead>Größe</TableHead>
                                    <TableHead>Datum</TableHead>
                                    <TableHead className="text-right">Aktion</TableHead>
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
                                            {doc.formatted_size ?? formatFileSize(doc.file_size)}
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {formatDate(doc.created_at)}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                asChild
                                                className="gap-1"
                                            >
                                                <a href={route("portal.documents.download", doc.id)}>
                                                    <Download className="h-4 w-4" />
                                                    Herunterladen
                                                </a>
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
        </EmployeeLayout>
    )
}
