"use client"

import { useState } from "react"
import { Head, Link, router, usePage } from "@inertiajs/react"
import { useTranslation } from "react-i18next"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Badge } from "@/components/ui/badge"
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu"
import {
    Plus,
    Search,
    Filter,
    Edit,
    Trash2,
    FileText,
    AlertTriangle,
    Clock,
    CheckCircle,
    Send,
    Download,
    Eye,
    MoreHorizontal,
    ArrowUpDown,
    ChevronDown,
    ChevronUp,
} from "lucide-react"
import { route } from "ziggy-js"
import AppLayout from "@/layouts/app-layout"
import { SendEmailDialog } from "@/components/send-email-dialog"
import type { BreadcrumbItem, Offer, PaginatedData } from "@/types"
import { formatCurrency as formatCurrencyUtil } from "@/utils/formatting"

interface OffersIndexProps {
    offers: PaginatedData<Offer>
    filters: {
        search?: string
        status?: string
        sort?: string
        direction?: "asc" | "desc"
    }
    stats: {
        total: number
        draft: number
        sent: number
        accepted: number
        rejected: number
        expired: number
    }
}

const breadcrumbs: BreadcrumbItem[] = [{ title: "Dashboard", href: "/dashboard" }, { title: "Angebote" }]

export default function OffersIndex() {
    const { t } = useTranslation()
    const { offers, filters, stats } = usePage<OffersIndexProps>().props
    const settings = (usePage().props as any).auth?.user?.company?.settings ?? {}
    const [search, setSearch] = useState(filters.search || "")
    const [status, setStatus] = useState(filters.status || "all")
    const sort = filters.sort || "issue_date"
    const direction = filters.direction || "desc"
    const [sendDialogOpen, setSendDialogOpen] = useState(false)
    const [selectedOffer, setSelectedOffer] = useState<Offer | null>(null)

    const handleSearch = () => {
        const params: any = {}
        if (search) params.search = search
        if (status && status !== 'all') params.status = status
        if (sort) params.sort = sort
        if (direction) params.direction = direction
        router.get("/offers", params, { preserveScroll: true })
    }

    const handleStatusChange = (newStatus: string) => {
        setStatus(newStatus)
        const params: any = {}
        if (search) params.search = search
        if (newStatus && newStatus !== 'all') params.status = newStatus
        if (sort) params.sort = sort
        if (direction) params.direction = direction
        router.get("/offers", params, { preserveScroll: true })
    }

    const handleSort = (column: string) => {
        const params: any = {}
        if (search) params.search = search
        if (status && status !== "all") params.status = status

        const nextDirection =
            sort === column ? (direction === "asc" ? "desc" : "asc") : "asc"

        params.sort = column
        params.direction = nextDirection
        router.get("/offers", params, { preserveScroll: true })
    }

    const renderSortIcon = (column: string) => {
        if (sort !== column) return <ArrowUpDown className="h-3.5 w-3.5 opacity-60" />
        return direction === "asc" ? <ChevronUp className="h-3.5 w-3.5" /> : <ChevronDown className="h-3.5 w-3.5" />
    }

    const handleDelete = (offer: Offer) => {
        if (confirm(t('pages.offers.deleteConfirm', { number: offer.number }))) {
            router.delete(route("offers.destroy", offer.id))
        }
    }

    const handleConvertToInvoice = (offer: Offer) => {
        if (confirm(t('pages.offers.confirmConvert'))) {
            router.post(route("offers.convert-to-invoice", offer.id))
        }
    }

    const getStatusBadge = (offer: Offer) => {
        const isExpired = new Date(offer.valid_until) < new Date() && offer.status !== "accepted"

        if (isExpired && offer.status === "sent") {
            return <Badge variant="destructive">{t('common.expired')}</Badge>
        }

        const statusConfig = {
            draft: { label: "Entwurf", variant: "outline" as const },
            sent: { label: "Versendet", variant: "secondary" as const },
            accepted: { label: "Angenommen", variant: "default" as const },
            rejected: { label: "Abgelehnt", variant: "destructive" as const },
        }

        const config = statusConfig[offer.status as keyof typeof statusConfig] || statusConfig.draft
        return <Badge variant={config.variant}>{config.label}</Badge>
    }

    const formatCurrency = (amount: number) => formatCurrencyUtil(amount, settings)

    const formatDate = (date: string) => {
        return new Date(date).toLocaleDateString("de-DE")
    }

    const getDaysUntilExpiry = (validUntil: string) => {
        const today = new Date()
        const expiryDate = new Date(validUntil)
        const diffTime = expiryDate.getTime() - today.getTime()
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
        return diffDays
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('pages.offers.title')} />

            <div className="flex flex-1 flex-col gap-4">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground">{t('pages.offers.title')}</h1>
                        <p className="text-muted-foreground">{t('pages.offers.subtitle')}</p>
                    </div>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            onClick={() => {
                                const params = new URLSearchParams()
                                if (filters.search) params.append('search', filters.search)
                                if (filters.status && filters.status !== 'all') params.append('status', filters.status)
                                window.location.href = route('export.offers') + (params.toString() ? '?' + params.toString() : '')
                            }}
                        >
                            <Download className="mr-2 h-4 w-4" />
                            {t('common.export')}
                        </Button>
                        <Link href="/offers/create">
                            <Button>
                                <Plus className="mr-2 h-4 w-4" />
                                Neues Angebot
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Statistics Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{t('common.total')}</CardTitle>
                            <FileText className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{t('common.draft')}</CardTitle>
                            <Edit className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.draft}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{t('common.sent')}</CardTitle>
                            <Clock className="h-4 w-4 text-blue-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600">{stats.sent}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{t('common.accepted')}</CardTitle>
                            <CheckCircle className="h-4 w-4 text-green-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">{stats.accepted}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{t('common.rejected')}</CardTitle>
                            <Trash2 className="h-4 w-4 text-red-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-red-600">{stats.rejected}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{t('common.expired')}</CardTitle>
                            <AlertTriangle className="h-4 w-4 text-orange-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-orange-600">{stats.expired}</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle>{t('common.filters')}</CardTitle>
                        <CardDescription>{t('pages.offers.filterDesc')}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-col sm:flex-row gap-4">
                            <div className="flex-1">
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                                    <Input
                                        placeholder="Nach Angebotsnummer oder Kunde suchen..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="pl-10"
                                        onKeyDown={(e) => e.key === "Enter" && handleSearch()}
                                    />
                                </div>
                            </div>
                            <Select value={status} onValueChange={handleStatusChange}>
                                <SelectTrigger className="w-full sm:w-48">
                                    <SelectValue placeholder={t('pages.users.selectStatus')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Alle Status</SelectItem>
                                    <SelectItem value="draft">{t('common.draft')}</SelectItem>
                                    <SelectItem value="sent">{t('common.sent')}</SelectItem>
                                    <SelectItem value="accepted">{t('common.accepted')}</SelectItem>
                                    <SelectItem value="rejected">{t('common.rejected')}</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button onClick={handleSearch}>
                                <Filter className="mr-2 h-4 w-4" />
                                Filtern
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Offers Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>{t('pages.offers.offerList')}</CardTitle>
                        <CardDescription>{offers.total} Angebote gefunden</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow className="h-10">
                                        <TableHead className="py-2">
                                            <button
                                                type="button"
                                                onClick={() => handleSort("number")}
                                                className="flex items-center gap-1 text-xs font-semibold text-muted-foreground hover:text-foreground"
                                            >
                                                Angebotsnummer
                                                {renderSortIcon("number")}
                                            </button>
                                        </TableHead>
                                        <TableHead className="py-2">
                                            <button
                                                type="button"
                                                onClick={() => handleSort("customer")}
                                                className="flex items-center gap-1 text-xs font-semibold text-muted-foreground hover:text-foreground"
                                            >
                                                Kunde
                                                {renderSortIcon("customer")}
                                            </button>
                                        </TableHead>
                                        <TableHead className="py-2">
                                            <button
                                                type="button"
                                                onClick={() => handleSort("issue_date")}
                                                className="flex items-center gap-1 text-xs font-semibold text-muted-foreground hover:text-foreground"
                                            >
                                                Datum
                                                {renderSortIcon("issue_date")}
                                            </button>
                                        </TableHead>
                                        <TableHead className="py-2">
                                            <button
                                                type="button"
                                                onClick={() => handleSort("valid_until")}
                                                className="flex items-center gap-1 text-xs font-semibold text-muted-foreground hover:text-foreground"
                                            >
                                                {t('pages.offers.validUntil')}
                                                {renderSortIcon("valid_until")}
                                            </button>
                                        </TableHead>
                                        <TableHead className="py-2">
                                            <button
                                                type="button"
                                                onClick={() => handleSort("total")}
                                                className="flex items-center gap-1 text-xs font-semibold text-muted-foreground hover:text-foreground"
                                            >
                                                Betrag
                                                {renderSortIcon("total")}
                                            </button>
                                        </TableHead>
                                        <TableHead className="py-2">
                                            <button
                                                type="button"
                                                onClick={() => handleSort("status")}
                                                className="flex items-center gap-1 text-xs font-semibold text-muted-foreground hover:text-foreground"
                                            >
                                                Status
                                                {renderSortIcon("status")}
                                            </button>
                                        </TableHead>
                                        <TableHead className="py-2">{t('pages.offers.validUntil')}</TableHead>
                                        <TableHead className="w-[156px] py-2 text-right">{t('common.actions')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {offers.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={8} className="text-center py-8 text-gray-500">
                                                Keine Angebote gefunden
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        offers.data.map((offer) => {
                                            const daysUntilExpiry = getDaysUntilExpiry(offer.valid_until)
                                            const isExpiringSoon = daysUntilExpiry <= 7 && daysUntilExpiry > 0
                                            const isExpired = daysUntilExpiry < 0

                                            return (
                                                <TableRow key={offer.id} className="group h-11">
                                                    <TableCell className="py-2 text-sm font-medium">
                                                        <Link href={`/offers/${offer.id}`} className="text-blue-600 hover:text-blue-800">
                                                            {offer.number}
                                                        </Link>
                                                    </TableCell>
                                                    <TableCell className="py-2 text-sm">{offer.customer.name}</TableCell>
                                                    <TableCell className="py-2 text-sm">{formatDate(offer.issue_date)}</TableCell>
                                                    <TableCell className="py-2 text-sm">
                                                        <div className={`${isExpired ? "text-red-600" : isExpiringSoon ? "text-orange-600" : ""}`}>
                                                            {formatDate(offer.valid_until)}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="py-2 text-sm font-medium">{formatCurrency(offer.total)}</TableCell>
                                                    <TableCell className="py-2 text-sm">{getStatusBadge(offer)}</TableCell>
                                                    <TableCell className="py-2 text-sm">
                                                        {isExpired ? (
                                                            <Badge variant="destructive" className="text-xs">
                                                                <AlertTriangle className="w-3 h-3 mr-1" />
                                                                Abgelaufen
                                                            </Badge>
                                                        ) : isExpiringSoon ? (
                                                            <Badge variant="outline" className="text-orange-600 border-orange-600 text-xs">
                                                                <Clock className="w-3 h-3 mr-1" />
                                                                {daysUntilExpiry} Tage
                                                            </Badge>
                                                        ) : (
                                                            <Badge variant="outline" className="text-green-600 border-green-600 text-xs">
                                                                <CheckCircle className="w-3 h-3 mr-1" />
                                                                {t('common.valid')}
                                                            </Badge>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="w-[156px] py-2">
                                                        <div className="flex w-[156px] items-center justify-end gap-2">
                                                            {/* Quick actions (visible on row hover, like invoices) */}
                                                            <div className="flex items-center gap-1 invisible group-hover:visible">
                                                                <Link href={route("offers.show", offer.id)}>
                                                                    <Button variant="ghost" size="icon" className="h-8 w-8" title="Anzeigen">
                                                                        <Eye className="h-4 w-4" />
                                                                    </Button>
                                                                </Link>
                                                                <Link href={route("offers.edit", offer.id)}>
                                                                    <Button variant="ghost" size="icon" className="h-8 w-8" title="Bearbeiten">
                                                                        <Edit className="h-4 w-4" />
                                                                    </Button>
                                                                </Link>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="h-8 w-8"
                                                                    onClick={() => window.open(route("offers.pdf", offer.id), "_blank")}
                                                                    title="PDF anzeigen"
                                                                >
                                                                    <FileText className="h-4 w-4" />
                                                                </Button>
                                                            </div>

                                                            {/* All actions */}
                                                            <DropdownMenu>
                                                                <DropdownMenuTrigger asChild>
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        className="h-8 w-8 opacity-70 hover:opacity-100"
                                                                        title="Aktionen"
                                                                    >
                                                                        <MoreHorizontal className="h-5 w-5" />
                                                                    </Button>
                                                                </DropdownMenuTrigger>
                                                                <DropdownMenuContent align="end" className="w-56">
                                                                    <DropdownMenuItem asChild>
                                                                        <Link href={route("offers.show", offer.id)}>
                                                                            <Eye className="mr-2 h-4 w-4" />
                                                                            Anzeigen
                                                                        </Link>
                                                                    </DropdownMenuItem>
                                                                    <DropdownMenuItem asChild>
                                                                        <Link href={route("offers.edit", offer.id)}>
                                                                            <Edit className="mr-2 h-4 w-4" />
                                                                            {t('common.edit')}
                                                                        </Link>
                                                                    </DropdownMenuItem>
                                                                    <DropdownMenuItem onClick={() => window.open(route("offers.pdf", offer.id), "_blank")}>
                                                                        <FileText className="mr-2 h-4 w-4" />
                                                                        {t('pages.invoices.openPdf')}
                                                                    </DropdownMenuItem>

                                                                    {offer.status === "draft" && (
                                                                        <DropdownMenuItem
                                                                            onClick={() => {
                                                                                setSelectedOffer(offer)
                                                                                setSendDialogOpen(true)
                                                                            }}
                                                                        >
                                                                            <Send className="mr-2 h-4 w-4" />
                                                                            Versenden
                                                                        </DropdownMenuItem>
                                                                    )}

                                                                    {offer.status === "accepted" && !offer.converted_to_invoice_id && (
                                                                        <DropdownMenuItem onClick={() => handleConvertToInvoice(offer)}>
                                                                            <FileText className="mr-2 h-4 w-4" />
                                                                            In Rechnung umwandeln
                                                                        </DropdownMenuItem>
                                                                    )}

                                                                    <DropdownMenuItem
                                                                        className="text-red-600 focus:text-red-600"
                                                                        onClick={() => handleDelete(offer)}
                                                                    >
                                                                        <Trash2 className="mr-2 h-4 w-4" />
                                                                        {t('common.delete')}
                                                                    </DropdownMenuItem>
                                                                </DropdownMenuContent>
                                                            </DropdownMenu>
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            )
                                        })
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {/* Pagination */}
                        {offers.links && offers.links.length > 3 && (
                            <div className="flex justify-center mt-6 gap-2">
                                {offers.links.map((link, index) => (
                                    <Button
                                        key={index}
                                        variant={link.active ? "default" : "outline"}
                                        size="sm"
                                        disabled={!link.url}
                                        onClick={() => link.url && router.get(link.url)}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {selectedOffer && (
                <SendEmailDialog
                    open={sendDialogOpen}
                    onOpenChange={setSendDialogOpen}
                    type="offer"
                    documentId={selectedOffer.id}
                    documentNumber={selectedOffer.number}
                    customerEmail={selectedOffer.customer?.email}
                    onSuccess={() => {
                        router.reload()
                    }}
                />
            )}
        </AppLayout>
    )
}
