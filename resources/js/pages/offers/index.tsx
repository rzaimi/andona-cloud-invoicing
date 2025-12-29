"use client"

import { useState } from "react"
import { Head, Link, router, usePage } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Badge } from "@/components/ui/badge"
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
    Download
} from "lucide-react"
import { route } from "ziggy-js"
import AppLayout from "@/layouts/app-layout"
import { SendEmailDialog } from "@/components/send-email-dialog"
import type { BreadcrumbItem, Offer, PaginatedData } from "@/types"

interface OffersIndexProps {
    offers: PaginatedData<Offer>
    filters: {
        search?: string
        status?: string
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
    const { offers, filters, stats } = usePage<OffersIndexProps>().props
    const [search, setSearch] = useState(filters.search || "")
    const [status, setStatus] = useState(filters.status || "all")
    const [sendDialogOpen, setSendDialogOpen] = useState(false)
    const [selectedOffer, setSelectedOffer] = useState<Offer | null>(null)

    const handleSearch = () => {
        const params: any = {}
        if (search) params.search = search
        if (status && status !== 'all') params.status = status
        router.get("/offers", params, { preserveScroll: true })
    }

    const handleStatusChange = (newStatus: string) => {
        setStatus(newStatus)
        const params: any = {}
        if (search) params.search = search
        if (newStatus && newStatus !== 'all') params.status = newStatus
        router.get("/offers", params, { preserveScroll: true })
    }

    const handleDelete = (offer: Offer) => {
        if (confirm(`Möchten Sie das Angebot "${offer.number}" wirklich löschen?`)) {
            router.delete(route("offers.destroy", offer.id))
        }
    }

    const handleConvertToInvoice = (offer: Offer) => {
        if (confirm("Möchten Sie dieses Angebot in eine Rechnung umwandeln?")) {
            router.post(route("offers.convert-to-invoice", offer.id))
        }
    }

    const getStatusBadge = (offer: Offer) => {
        const isExpired = new Date(offer.valid_until) < new Date() && offer.status !== "accepted"

        if (isExpired && offer.status === "sent") {
            return <Badge variant="destructive">Abgelaufen</Badge>
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

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat("de-DE", {
            style: "currency",
            currency: "EUR",
        }).format(amount)
    }

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
            <Head title="Angebote" />

            <div className="flex flex-1 flex-col gap-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-gray-100">Angebote</h1>
                        <p className="text-gray-600">Verwalten Sie Ihre Kundenangebote</p>
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
                            Exportieren
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
                            <CardTitle className="text-sm font-medium">Gesamt</CardTitle>
                            <FileText className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Entwürfe</CardTitle>
                            <Edit className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.draft}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Versendet</CardTitle>
                            <Clock className="h-4 w-4 text-blue-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600">{stats.sent}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Angenommen</CardTitle>
                            <CheckCircle className="h-4 w-4 text-green-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">{stats.accepted}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Abgelehnt</CardTitle>
                            <Trash2 className="h-4 w-4 text-red-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-red-600">{stats.rejected}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Abgelaufen</CardTitle>
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
                        <CardTitle>Filter</CardTitle>
                        <CardDescription>Filtern und durchsuchen Sie Ihre Angebote</CardDescription>
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
                                        onKeyPress={(e) => e.key === "Enter" && handleSearch()}
                                    />
                                </div>
                            </div>
                            <Select value={status} onValueChange={handleStatusChange}>
                                <SelectTrigger className="w-full sm:w-48">
                                    <SelectValue placeholder="Status auswählen" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Alle Status</SelectItem>
                                    <SelectItem value="draft">Entwurf</SelectItem>
                                    <SelectItem value="sent">Versendet</SelectItem>
                                    <SelectItem value="accepted">Angenommen</SelectItem>
                                    <SelectItem value="rejected">Abgelehnt</SelectItem>
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
                        <CardTitle>Angebotsliste</CardTitle>
                        <CardDescription>{offers.total} Angebote gefunden</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Angebotsnummer</TableHead>
                                        <TableHead>Kunde</TableHead>
                                        <TableHead>Datum</TableHead>
                                        <TableHead>Gültig bis</TableHead>
                                        <TableHead>Betrag</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Gültigkeit</TableHead>
                                        <TableHead>Aktionen</TableHead>
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
                                                <TableRow key={offer.id}>
                                                    <TableCell className="font-medium">
                                                        <Link href={`/offers/${offer.id}`} className="text-blue-600 hover:text-blue-800">
                                                            {offer.number}
                                                        </Link>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div>
                                                            <div className="font-medium">{offer.customer.name}</div>
                                                            <div className="text-sm text-gray-500">{offer.customer.email}</div>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>{formatDate(offer.issue_date)}</TableCell>
                                                    <TableCell>
                                                        <div className={`${isExpired ? "text-red-600" : isExpiringSoon ? "text-orange-600" : ""}`}>
                                                            {formatDate(offer.valid_until)}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="font-medium">{formatCurrency(offer.total)}</TableCell>
                                                    <TableCell>{getStatusBadge(offer)}</TableCell>
                                                    <TableCell>
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
                                                                Gültig
                                                            </Badge>
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex space-x-2">
                                                            <Link href={route("offers.edit", offer.id)}>
                                                                <Button variant="outline" size="sm" title="Bearbeiten">
                                                                    <Edit className="h-4 w-4" />
                                                                </Button>
                                                            </Link>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => window.open(route("offers.pdf", offer.id), "_blank")}
                                                                title="PDF anzeigen"
                                                            >
                                                                <FileText className="h-4 w-4 mr-1" />
                                                                PDF
                                                            </Button>
                                                            {offer.status === "draft" && (
                                                                <Button 
                                                                    variant="outline" 
                                                                    size="sm" 
                                                                    title="Versenden"
                                                                    onClick={() => {
                                                                        setSelectedOffer(offer)
                                                                        setSendDialogOpen(true)
                                                                    }}
                                                                >
                                                                    <Send className="h-4 w-4" />
                                                                </Button>
                                                            )}
                                                            {offer.status === "accepted" && (
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                    onClick={() => handleConvertToInvoice(offer)}
                                                                    title="In Rechnung umwandeln"
                                                                >
                                                                    <FileText className="h-4 w-4 mr-1" />
                                                                    Umwandeln
                                                                </Button>
                                                            )}
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => handleDelete(offer)}
                                                                className="text-red-600 hover:text-red-700"
                                                                title="Löschen"
                                                            >
                                                                <Trash2 className="h-4 w-4" />
                                                            </Button>
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
