"use client"

import { Head, useForm, usePage, Link, router } from "@inertiajs/react"
import { useTranslation } from "react-i18next"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { 
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog"
import { 
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select"
import { Badge } from "@/components/ui/badge"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Upload, Download, FileText, Edit, Trash2, Search, Filter, X, Tag, Link2, CheckCircle, AlertCircle } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { User } from "@/types"
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
    tags?: string[]
    uploaded_by?: {
        id: string
        name: string
    }
    linkable?: {
        id: string
        number?: string
        name?: string
    }
    link_type?: string
    created_at: string
    formatted_size?: string
}

interface DocumentsProps {
    user: User
    documents: {
        data: Document[]
        links: any[]
        current_page: number
        last_page: number
    }
    customers: Array<{ id: string; name: string; number: string }>
    invoices: Array<{ id: string; number: string }>
    filters: {
        search?: string
        category?: string
        link_type?: string
        linkable_type?: string
        linkable_id?: string
        sort_by?: string
        sort_order?: string
    }
}

const CATEGORIES = [
    { value: 'all', label: 'Alle Kategorien' },
    { value: 'payroll', label: 'Lohnabrechnung' },
    { value: 'employee', label: 'Mitarbeiter' },
    { value: 'customer', label: 'Kunde' },
    { value: 'invoice', label: 'Rechnung' },
    { value: 'company', label: 'Firma' },
    { value: 'financial', label: 'Finanzen' },
    { value: 'custom', label: 'Sonstiges' },
]

const LINK_TYPES = [
    { value: 'all', label: 'Alle Typen' },
    { value: 'attachment', label: 'Anhang' },
    { value: 'contract', label: 'Vertrag' },
    { value: 'receipt', label: 'Beleg' },
    { value: 'certificate', label: 'Zertifikat' },
    { value: 'other', label: 'Sonstiges' },
]

export default function DocumentsSettings() {
    const { t } = useTranslation()
    const { props } = usePage<DocumentsProps & { flash?: { success?: string; upload_errors?: string[] } }>()
    const user = props.auth?.user || props.user
    const { documents, customers, invoices, filters, flash } = props

    const [uploadDialogOpen, setUploadDialogOpen] = useState(false)
    const [editDialogOpen, setEditDialogOpen] = useState(false)
    const [selectedDocument, setSelectedDocument] = useState<Document | null>(null)
    const [linkableType, setLinkableType] = useState<string>('')

    const uploadForm = useForm({
        files: [] as File[],
        category: 'custom',
        description: '',
        tags: '',
        linkable_type: '',
        linkable_id: '',
        link_type: '',
    })

    const editForm = useForm({
        name: '',
        category: 'custom',
        description: '',
        tags: '',
        linkable_type: '',
        linkable_id: '',
        link_type: '',
    })

    const handleUpload = (e: React.FormEvent) => {
        e.preventDefault()
        if (uploadForm.data.files.length === 0) return

        uploadForm.post(route('documents.store'), {
            forceFormData: true,
            onSuccess: () => {
                setUploadDialogOpen(false)
                uploadForm.reset()
                setLinkableType('')
            },
        })
    }

    const handleEdit = (document: Document) => {
        setSelectedDocument(document)
        editForm.setData({
            name: document.name,
            category: document.category,
            description: document.description || '',
            tags: document.tags?.join(', ') || '',
            linkable_type: document.linkable ? (document.linkable.number ? 'App\\Modules\\Invoice\\Models\\Invoice' : 'App\\Modules\\Customer\\Models\\Customer') : '',
            linkable_id: document.linkable?.id || '',
            link_type: document.link_type || '',
        })
        setLinkableType(document.linkable ? (document.linkable.number ? 'invoice' : 'customer') : '')
        setEditDialogOpen(true)
    }

    const handleUpdate = (e: React.FormEvent) => {
        e.preventDefault()
        if (!selectedDocument) return

        editForm.put(route('documents.update', selectedDocument.id), {
            onSuccess: () => {
                setEditDialogOpen(false)
                setSelectedDocument(null)
                editForm.reset()
            },
        })
    }

    const handleDelete = (document: Document) => {
        if (confirm(t('settings.confirmDeleteDocument', { name: document.name }))) {
            router.delete(route('documents.destroy', document.id))
        }
    }

    const formatFileSize = (bytes: number): string => {
        const units = ['B', 'KB', 'MB', 'GB']
        let size = bytes
        let unitIndex = 0
        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024
            unitIndex++
        }
        return `${size.toFixed(2)} ${units[unitIndex]}`
    }

    const getCategoryLabel = (category: string) => {
        return CATEGORIES.find(c => c.value === category)?.label || category
    }

    const getLinkTypeLabel = (linkType?: string) => {
        if (!linkType) return null
        return LINK_TYPES.find(t => t.value === linkType)?.label || linkType
    }

    return (
        <AppLayout user={user}>
            <Head title="Dokumente" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-1xl font-bold text-gray-900">Dokumente</h1>
                        <p className="text-gray-600 mt-2">
                            Verwalten Sie Ihre Dokumente und Dateien
                        </p>
                    </div>
                    <Button onClick={() => setUploadDialogOpen(true)}>
                        <Upload className="mr-2 h-4 w-4" />
                        Dokument hochladen
                    </Button>
                </div>

                {/* Success/Error Messages */}
                {flash?.success && (
                    <Alert>
                        <CheckCircle className="h-4 w-4" />
                        <AlertDescription>
                            {flash.success}
                            {flash.upload_errors && flash.upload_errors.length > 0 && (
                                <div className="mt-2">
                                    <p className="font-medium text-sm">Fehler bei folgenden Dateien:</p>
                                    <ul className="list-disc list-inside text-sm mt-1">
                                        {flash.upload_errors.map((error, index) => (
                                            <li key={index}>{error}</li>
                                        ))}
                                    </ul>
                                </div>
                            )}
                        </AlertDescription>
                    </Alert>
                )}

                {/* Filters */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="grid gap-4 md:grid-cols-4">
                            <div>
                                <Label htmlFor="search">Suche</Label>
                                <Input
                                    id="search"
                                    placeholder="Name, Beschreibung..."
                                    defaultValue={filters.search}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter') {
                                            router.get(route('documents.index'), {
                                                ...filters,
                                                search: (e.target as HTMLInputElement).value,
                                            })
                                        }
                                    }}
                                />
                            </div>
                            <div>
                                <Label htmlFor="category">Kategorie</Label>
                                <Select
                                    defaultValue={filters.category || 'all'}
                                    onValueChange={(value) => {
                                        router.get(route('documents.index'), {
                                            ...filters,
                                            category: value === 'all' ? undefined : value,
                                        })
                                    }}
                                >
                                    <SelectTrigger id="category">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {CATEGORIES.map((cat) => (
                                            <SelectItem key={cat.value} value={cat.value}>
                                                {cat.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label htmlFor="link_type">{t('settings.linkType')}</Label>
                                <Select
                                    defaultValue={filters.link_type || 'all'}
                                    onValueChange={(value) => {
                                        router.get(route('documents.index'), {
                                            ...filters,
                                            link_type: value === 'all' ? undefined : value,
                                        })
                                    }}
                                >
                                    <SelectTrigger id="link_type">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {LINK_TYPES.map((type) => (
                                            <SelectItem key={type.value} value={type.value}>
                                                {type.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="flex items-end">
                                <Button
                                    variant="outline"
                                    onClick={() => router.get(route('documents.index'))}
                                >
                                    <X className="mr-2 h-4 w-4" />
                                    {t('common.reset')}
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Documents Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Dokumente ({documents.data.length})</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {documents.data.length === 0 ? (
                            <div className="text-center py-12">
                                <FileText className="mx-auto h-12 w-12 text-gray-400" />
                                <h3 className="mt-2 text-sm font-semibold text-gray-900">Keine Dokumente</h3>
                                <p className="mt-1 text-sm text-gray-500">
                                    Beginnen Sie mit dem Hochladen Ihres ersten Dokuments.
                                </p>
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>{t('common.name')}</TableHead>
                                        <TableHead>Kategorie</TableHead>
                                        <TableHead>{t('common.size')}</TableHead>
                                        <TableHead>{t('settings.linkedWith')}</TableHead>
                                        <TableHead>Tags</TableHead>
                                        <TableHead>Hochgeladen</TableHead>
                                        <TableHead className="text-right">{t('common.actions')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {documents.data.map((document) => (
                                        <TableRow key={document.id}>
                                            <TableCell className="font-medium">
                                                <div className="flex items-center gap-2">
                                                    <FileText className="h-4 w-4 text-gray-400" />
                                                    {document.name}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline">
                                                    {getCategoryLabel(document.category)}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>{formatFileSize(document.file_size)}</TableCell>
                                            <TableCell>
                                                {document.linkable ? (
                                                    <div className="flex items-center gap-2">
                                                        <Link2 className="h-3 w-3 text-gray-400" />
                                                        <Link
                                                            href={
                                                                document.linkable.number
                                                                    ? `/invoices/${document.linkable.id}/edit`
                                                                    : `/customers/${document.linkable.id}/edit`
                                                            }
                                                            className="text-blue-600 hover:underline"
                                                        >
                                                            {document.linkable.number || document.linkable.name}
                                                        </Link>
                                                        {document.link_type && (
                                                            <Badge variant="secondary" className="ml-1 text-xs">
                                                                {getLinkTypeLabel(document.link_type)}
                                                            </Badge>
                                                        )}
                                                    </div>
                                                ) : (
                                                    <span className="text-gray-400">-</span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {document.tags && document.tags.length > 0 ? (
                                                    <div className="flex flex-wrap gap-1">
                                                        {document.tags.slice(0, 2).map((tag, idx) => (
                                                            <Badge key={idx} variant="secondary" className="text-xs">
                                                                {tag}
                                                            </Badge>
                                                        ))}
                                                        {document.tags.length > 2 && (
                                                            <Badge variant="secondary" className="text-xs">
                                                                +{document.tags.length - 2}
                                                            </Badge>
                                                        )}
                                                    </div>
                                                ) : (
                                                    <span className="text-gray-400">-</span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {new Date(document.created_at).toLocaleDateString('de-DE')}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => window.open(route('documents.download', document.id), '_blank')}
                                                    >
                                                        <Download className="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleEdit(document)}
                                                    >
                                                        <Edit className="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleDelete(document)}
                                                    >
                                                        <Trash2 className="h-4 w-4 text-red-600" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>

                {/* Upload Dialog */}
                <Dialog open={uploadDialogOpen} onOpenChange={setUploadDialogOpen}>
                    <DialogContent className="max-w-2xl">
                        <form onSubmit={handleUpload}>
                            <DialogHeader>
                                <DialogTitle>Dokument(e) hochladen</DialogTitle>
                                <DialogDescription>
                                    {t('settings.documentsUploadHint')}
                                </DialogDescription>
                            </DialogHeader>
                            <div className="space-y-4 py-4">
                                <div>
                                    <Label htmlFor="files">{t('settings.filesLabel')} *</Label>
                                    <Input
                                        id="files"
                                        type="file"
                                        multiple
                                        onChange={(e) => {
                                            const files = Array.from(e.target.files || [])
                                            uploadForm.setData('files', files)
                                        }}
                                        required
                                    />
                                    {uploadForm.data.files.length > 0 && (
                                        <div className="mt-2 space-y-1">
                                            <p className="text-sm text-gray-600">
                                                {uploadForm.data.files.length} {t('settings.filesSelected')}:
                                            </p>
                                            <ul className="text-xs text-gray-500 space-y-1 max-h-32 overflow-y-auto">
                                                {uploadForm.data.files.map((file, index) => (
                                                    <li key={index} className="truncate">
                                                        • {file.name} ({(file.size / 1024).toFixed(2)} KB)
                                                    </li>
                                                ))}
                                            </ul>
                                        </div>
                                    )}
                                    {uploadForm.errors.files && (
                                        <p className="text-sm text-red-600 mt-1">{uploadForm.errors.files}</p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="category">Kategorie *</Label>
                                    <Select
                                        value={uploadForm.data.category}
                                        onValueChange={(value) => uploadForm.setData('category', value)}
                                    >
                                        <SelectTrigger id="category">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {CATEGORIES.filter(c => c.value !== 'all').map((cat) => (
                                                <SelectItem key={cat.value} value={cat.value}>
                                                    {cat.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <Label htmlFor="description">{t('common.description')}</Label>
                                    <Input
                                        id="description"
                                        value={uploadForm.data.description}
                                        onChange={(e) => uploadForm.setData('description', e.target.value)}
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="tags">Tags (kommagetrennt)</Label>
                                    <Input
                                        id="tags"
                                        placeholder="z.B. Gehalt, 2024, Januar"
                                        value={uploadForm.data.tags}
                                        onChange={(e) => uploadForm.setData('tags', e.target.value)}
                                    />
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <Label htmlFor="linkable_type">{t('settings.linkWith')}</Label>
                                        <Select
                                            value={linkableType || 'none'}
                                            onValueChange={(value) => {
                                                if (value === 'none') {
                                                    setLinkableType('')
                                                    uploadForm.setData('linkable_type', '')
                                                    uploadForm.setData('linkable_id', '')
                                                } else {
                                                    setLinkableType(value)
                                                    uploadForm.setData('linkable_type', value === 'invoice' 
                                                        ? 'App\\Modules\\Invoice\\Models\\Invoice'
                                                        : 'App\\Modules\\Customer\\Models\\Customer')
                                                    uploadForm.setData('linkable_id', '')
                                                }
                                            }}
                                        >
                                            <SelectTrigger id="linkable_type">
                                                <SelectValue placeholder={t('common.selectPlaceholder')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="none">{t('settings.noLink')}</SelectItem>
                                                <SelectItem value="invoice">{t('nav.invoices')}</SelectItem>
                                                <SelectItem value="customer">Kunde</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    {linkableType && (
                                        <div>
                                            <Label htmlFor="linkable_id">
                                                {linkableType === 'invoice' ? 'Rechnung' : 'Kunde'} *
                                            </Label>
                                            <Select
                                                value={uploadForm.data.linkable_id}
                                                onValueChange={(value) => uploadForm.setData('linkable_id', value)}
                                            >
                                                <SelectTrigger id="linkable_id">
                                                    <SelectValue placeholder={t('common.selectPlaceholder')} />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {linkableType === 'invoice'
                                                        ? invoices.map((invoice) => (
                                                              <SelectItem key={invoice.id} value={invoice.id}>
                                                                  {invoice.number}
                                                              </SelectItem>
                                                          ))
                                                        : customers.map((customer) => (
                                                              <SelectItem key={customer.id} value={customer.id}>
                                                                  {customer.name} ({customer.number})
                                                              </SelectItem>
                                                          ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    )}
                                </div>
                                {linkableType && (
                                    <div>
                                        <Label htmlFor="link_type">{t('settings.linkType')}</Label>
                                        <Select
                                            value={uploadForm.data.link_type || undefined}
                                            onValueChange={(value) => uploadForm.setData('link_type', value)}
                                        >
                                            <SelectTrigger id="link_type">
                                                <SelectValue placeholder={t('common.selectPlaceholder')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {LINK_TYPES.filter(t => t.value !== 'all').map((type) => (
                                                    <SelectItem key={type.value} value={type.value}>
                                                        {type.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                )}
                            </div>
                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setUploadDialogOpen(false)}
                                >
                                    {t('common.cancel')}
                                </Button>
                                <Button type="submit" disabled={uploadForm.processing}>
                                    {uploadForm.processing ? 'Wird hochgeladen...' : 'Hochladen'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>

                {/* Edit Dialog */}
                <Dialog open={editDialogOpen} onOpenChange={setEditDialogOpen}>
                    <DialogContent className="max-w-2xl">
                        <form onSubmit={handleUpdate}>
                            <DialogHeader>
                                <DialogTitle>Dokument bearbeiten</DialogTitle>
                                <DialogDescription>
                                    Bearbeiten Sie die Metadaten des Dokuments.
                                </DialogDescription>
                            </DialogHeader>
                            <div className="space-y-4 py-4">
                                <div>
                                    <Label htmlFor="edit-name">Name *</Label>
                                    <Input
                                        id="edit-name"
                                        value={editForm.data.name}
                                        onChange={(e) => editForm.setData('name', e.target.value)}
                                        required
                                    />
                                    {editForm.errors.name && (
                                        <p className="text-sm text-red-600 mt-1">{editForm.errors.name}</p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="edit-category">Kategorie *</Label>
                                    <Select
                                        value={editForm.data.category}
                                        onValueChange={(value) => editForm.setData('category', value)}
                                    >
                                        <SelectTrigger id="edit-category">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {CATEGORIES.filter(c => c.value !== 'all').map((cat) => (
                                                <SelectItem key={cat.value} value={cat.value}>
                                                    {cat.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <Label htmlFor="edit-description">{t('common.description')}</Label>
                                    <Input
                                        id="edit-description"
                                        value={editForm.data.description}
                                        onChange={(e) => editForm.setData('description', e.target.value)}
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="edit-tags">Tags (kommagetrennt)</Label>
                                    <Input
                                        id="edit-tags"
                                        value={editForm.data.tags}
                                        onChange={(e) => editForm.setData('tags', e.target.value)}
                                    />
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <Label htmlFor="edit-linkable_type">{t('settings.linkWith')}</Label>
                                        <Select
                                            value={linkableType || 'none'}
                                            onValueChange={(value) => {
                                                if (value === 'none') {
                                                    setLinkableType('')
                                                    editForm.setData('linkable_type', '')
                                                    editForm.setData('linkable_id', '')
                                                } else {
                                                    setLinkableType(value)
                                                    editForm.setData('linkable_type', value === 'invoice' 
                                                        ? 'App\\Modules\\Invoice\\Models\\Invoice'
                                                        : 'App\\Modules\\Customer\\Models\\Customer')
                                                    editForm.setData('linkable_id', '')
                                                }
                                            }}
                                        >
                                            <SelectTrigger id="edit-linkable_type">
                                                <SelectValue placeholder={t('common.selectPlaceholder')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="none">{t('settings.noLink')}</SelectItem>
                                                <SelectItem value="invoice">{t('nav.invoices')}</SelectItem>
                                                <SelectItem value="customer">Kunde</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    {linkableType && (
                                        <div>
                                            <Label htmlFor="edit-linkable_id">
                                                {linkableType === 'invoice' ? 'Rechnung' : 'Kunde'}
                                            </Label>
                                            <Select
                                                value={editForm.data.linkable_id}
                                                onValueChange={(value) => editForm.setData('linkable_id', value)}
                                            >
                                                <SelectTrigger id="edit-linkable_id">
                                                    <SelectValue placeholder={t('common.selectPlaceholder')} />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {linkableType === 'invoice'
                                                        ? invoices.map((invoice) => (
                                                              <SelectItem key={invoice.id} value={invoice.id}>
                                                                  {invoice.number}
                                                              </SelectItem>
                                                          ))
                                                        : customers.map((customer) => (
                                                              <SelectItem key={customer.id} value={customer.id}>
                                                                  {customer.name} ({customer.number})
                                                              </SelectItem>
                                                          ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    )}
                                </div>
                                {linkableType && (
                                    <div>
                                        <Label htmlFor="edit-link_type">{t('settings.linkType')}</Label>
                                        <Select
                                            value={editForm.data.link_type || undefined}
                                            onValueChange={(value) => editForm.setData('link_type', value)}
                                        >
                                            <SelectTrigger id="edit-link_type">
                                                <SelectValue placeholder={t('common.selectPlaceholder')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {LINK_TYPES.filter(t => t.value !== 'all').map((type) => (
                                                    <SelectItem key={type.value} value={type.value}>
                                                        {type.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                )}
                            </div>
                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setEditDialogOpen(false)}
                                >
                                    {t('common.cancel')}
                                </Button>
                                <Button type="submit" disabled={editForm.processing}>
                                    {editForm.processing ? 'Wird gespeichert...' : 'Speichern'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    )
}

