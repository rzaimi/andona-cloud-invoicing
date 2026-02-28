"use client"

import type React from "react"
import { Head, Link, router, usePage } from "@inertiajs/react"
import { useTranslation } from "react-i18next"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { ArrowLeft, Edit, Trash2, FileText, Download, Send, CheckCircle, XCircle, Clock, Eye } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem } from "@/types"
import { route } from "ziggy-js"
import { formatCurrency as formatCurrencyUtil } from "@/utils/formatting"

interface OfferItem {
    id: number | string
    description: string
    quantity: number
    unit_price: number
    unit: string
    total: number
    discount_type?: "percentage" | "fixed" | null
    discount_value?: number | null
    discount_amount?: number
}

interface Offer {
    id: string
    number: string
    customer_id: string
    customer?: {
        id: string
        name: string
        email?: string
    }
    issue_date: string
    valid_until: string
    notes: string | null
    terms_conditions: string | null
    layout_id: string | null
    status: "draft" | "sent" | "accepted" | "rejected"
    items: OfferItem[]
    subtotal: number
    tax_amount: number
    tax_rate: number
    total: number
    converted_to_invoice_id?: string | null
    converted_to_invoice?: {
        id: string
        number: string
    } | null
    user?: {
        id: string
        name: string
    }
    created_at: string
}

interface OffersShowProps {
    offer: Offer
    settings: any
}

export default function OffersShow() {
    const { t } = useTranslation()
    const { offer } = usePage<OffersShowProps>().props
    const settings = (usePage().props as any).auth?.user?.company?.settings ?? {}

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Angebote", href: "/offers" },
        { title: offer.number },
    ]

    const getStatusBadge = (status: string) => {
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

        const config = statusConfig[status as keyof typeof statusConfig] || statusConfig.draft
        return <Badge variant={config.variant}>{config.label}</Badge>
    }

    const formatCurrency = (amount: number | string | null | undefined) => {
        const numAmount = typeof amount === "string" ? parseFloat(amount) : (amount ?? 0)
        if (isNaN(numAmount)) return "0,00 €"
        return formatCurrencyUtil(numAmount, settings)
    }

    const formatDate = (date: string) => {
        return new Date(date).toLocaleDateString("de-DE")
    }

    const handleDelete = () => {
        if (confirm(t('pages.offers.deleteConfirm', { number: offer.number }))) {
            router.delete(route("offers.destroy", offer.id))
        }
    }

    const handleConvertToInvoice = () => {
        if (confirm(t('pages.offers.confirmConvert'))) {
            router.post(route("offers.convert-to-invoice", offer.id))
        }
    }

    const handleAccept = () => {
        if (confirm(t('pages.offers.confirmAccept'))) {
            router.post(route("offers.accept", offer.id))
        }
    }

    const handleReject = () => {
        if (confirm(t('pages.offers.confirmReject'))) {
            router.post(route("offers.reject", offer.id))
        }
    }

    const handleSend = () => {
        router.post(route("offers.send", offer.id))
    }

    const isExpired = new Date(offer.valid_until) < new Date() && offer.status !== "accepted"

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Angebot ${offer.number}`} />

            <div className="flex flex-1 flex-col gap-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href="/offers">
                            <Button variant="ghost" size="sm">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                {t('common.back')}
                            </Button>
                        </Link>
                        <div>
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-bold text-foreground">
                                    Angebot {offer.number}
                                </h1>
                                {getStatusBadge(offer.status)}
                            </div>
                            <p className="text-muted-foreground mt-1">
                                {offer.customer?.name} • {formatDate(offer.issue_date)}
                            </p>
                        </div>
                    </div>

                    <div className="flex gap-2">
                        {offer.status === "sent" && (
                            <>
                                <Button onClick={handleAccept} variant="outline">
                                    <CheckCircle className="mr-2 h-4 w-4" />
                                    Annehmen
                                </Button>
                                <Button onClick={handleReject} variant="outline">
                                    <XCircle className="mr-2 h-4 w-4" />
                                    Ablehnen
                                </Button>
                            </>
                        )}
                        {offer.status === "accepted" && !offer.converted_to_invoice_id && (
                            <Button onClick={handleConvertToInvoice}>
                                <FileText className="mr-2 h-4 w-4" />
                                In Rechnung umwandeln
                            </Button>
                        )}
                        {offer.converted_to_invoice_id && offer.converted_to_invoice && (
                            <Link href={`/invoices/${offer.converted_to_invoice.id}`}>
                                <Button variant="outline">
                                    <FileText className="mr-2 h-4 w-4" />
                                    Zur Rechnung
                                </Button>
                            </Link>
                        )}
                        <Link href={route("offers.edit", offer.id)}>
                            <Button variant="outline">
                                <Edit className="mr-2 h-4 w-4" />
                                {t('common.edit')}
                            </Button>
                        </Link>
                        <Button
                            variant="outline"
                            onClick={() => window.open(route("offers.pdf", offer.id), "_blank")}
                        >
                            <FileText className="mr-2 h-4 w-4" />
                            PDF
                        </Button>
                        {offer.status === "draft" && (
                            <Button variant="outline" onClick={handleSend}>
                                <Send className="mr-2 h-4 w-4" />
                                Versenden
                            </Button>
                        )}
                        <Button variant="destructive" onClick={handleDelete}>
                            <Trash2 className="mr-2 h-4 w-4" />
                            {t('common.delete')}
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Main Content */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Offer Details */}
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('pages.offers.details')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <div className="text-sm text-muted-foreground">{t('pages.offers.number')}</div>
                                        <div className="font-medium">{offer.number}</div>
                                    </div>
                                    <div>
                                        <div className="text-sm text-muted-foreground">{t('common.status')}</div>
                                        <div className="mt-1">{getStatusBadge(offer.status)}</div>
                                    </div>
                                    <div>
                                        <div className="text-sm text-muted-foreground">{t('pages.offers.issueDate')}</div>
                                        <div className="font-medium">{formatDate(offer.issue_date)}</div>
                                    </div>
                                    <div>
                                        <div className="text-sm text-muted-foreground">{t('pages.offers.validUntil')}</div>
                                        <div className={`font-medium ${isExpired ? "text-red-600" : ""}`}>
                                            {formatDate(offer.valid_until)}
                                            {isExpired && " (abgelaufen)"}
                                        </div>
                                    </div>
                                    <div>
                                        <div className="text-sm text-muted-foreground">Kunde</div>
                                        <div className="font-medium">{offer.customer?.name}</div>
                                    </div>
                                    {offer.customer?.email && (
                                        <div>
                                            <div className="text-sm text-muted-foreground">E-Mail</div>
                                            <div className="font-medium">{offer.customer.email}</div>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Offer Items */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Angebotsposten</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Produkt-Nr.</TableHead>
                                            <TableHead>{t('common.description')}</TableHead>
                                            <TableHead>{t('common.quantity')}</TableHead>
                                            <TableHead>USt.</TableHead>
                                            <TableHead>Einzelpreis</TableHead>
                                            <TableHead>Rabatt</TableHead>
                                            <TableHead>Rabatt-Wert</TableHead>
                                            <TableHead className="text-right">{t('common.total')}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {offer.items?.map((item) => {
                                            const hasDiscount = item.discount_type && item.discount_value && item.discount_amount && item.discount_amount > 0
                                            const baseTotal = Number(item.quantity) * Number(item.unit_price)
                                            
                                            return (
                                                <TableRow key={item.id}>
                                                    <TableCell>
                                                        {item.product?.number || item.product?.sku || <span className="text-gray-400">-</span>}
                                                    </TableCell>
                                                    <TableCell>{item.description}</TableCell>
                                                    <TableCell>
                                                        {item.quantity} {item.unit}
                                                    </TableCell>
                                                    <TableCell>{(Number(offer.tax_rate || 0) * 100).toFixed(0)}%</TableCell>
                                                    <TableCell>{formatCurrency(item.unit_price)}</TableCell>
                                                    <TableCell>
                                                        {hasDiscount ? (
                                                            <span className="text-sm">
                                                                {item.discount_type === 'percentage' ? 'Prozent' : 'Fester Betrag'}
                                                            </span>
                                                        ) : (
                                                            <span className="text-gray-400">-</span>
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        {hasDiscount ? (
                                                            <div className="space-y-1">
                                                                <div className="text-sm">
                                                                    {item.discount_type === 'percentage' 
                                                                        ? `${item.discount_value}%`
                                                                        : formatCurrency(item.discount_value)
                                                                    }
                                                                </div>
                                                                <div className="text-xs text-red-600">
                                                                    -{formatCurrency(item.discount_amount)}
                                                                </div>
                                                            </div>
                                                        ) : (
                                                            <span className="text-gray-400">-</span>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-right font-medium">
                                                        <div className="space-y-1">
                                                            <div>{formatCurrency(item.total)}</div>
                                                            {hasDiscount && (
                                                                <div className="text-xs text-gray-500 line-through">
                                                                    {formatCurrency(baseTotal)}
                                                                </div>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            )
                                        })}
                                    </TableBody>
                                </Table>

                                <div className="mt-4 flex justify-end">
                                    <div className="w-64 space-y-2">
                                        {(() => {
                                            const totalDiscount = offer.items?.reduce((sum, item) => {
                                                return sum + (Number(item.discount_amount) || 0)
                                            }, 0) || 0
                                            
                                            return (
                                                <>
                                                    {totalDiscount > 0 && (
                                                        <div className="flex justify-between text-red-600">
                                                            <span>Gesamtrabatt</span>
                                                            <span className="font-medium">-{formatCurrency(totalDiscount)}</span>
                                                        </div>
                                                    )}
                                                </>
                                            )
                                        })()}
                                        <div className="flex justify-between">
                                            <span className="text-muted-foreground">Zwischensumme (netto)</span>
                                            <span className="font-medium">{formatCurrency(offer.subtotal)}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="text-muted-foreground">
                                                {Number(offer.tax_rate || 0) * 100}% Umsatzsteuer
                                            </span>
                                            <span className="font-medium">{formatCurrency(offer.tax_amount)}</span>
                                        </div>
                                        <div className="flex justify-between pt-2 border-t text-lg font-bold">
                                            <span>Gesamtbetrag</span>
                                            <span>{formatCurrency(offer.total)}</span>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {offer.notes && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>{t('common.notes')}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="whitespace-pre-wrap">{offer.notes}</p>
                                </CardContent>
                            </Card>
                        )}

                        {offer.terms_conditions && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>{t('pages.offers.offerTerms')}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="whitespace-pre-wrap">{offer.terms_conditions}</p>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Sidebar */}
                    <div className="lg:col-span-1 space-y-6">
                        {/* Offer Summary */}
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('pages.offers.offerSummary')}</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <div className="text-sm text-muted-foreground">Gesamtbetrag</div>
                                    <div className="text-2xl font-bold">{formatCurrency(offer.total)}</div>
                                </div>
                                <div>
                                    <div className="text-sm text-muted-foreground">{t('common.status')}</div>
                                    <div className="mt-1">{getStatusBadge(offer.status)}</div>
                                </div>
                                {isExpired && (
                                    <div className="pt-4 border-t">
                                        <div className="text-sm text-red-600 font-medium">
                                            Dieses Angebot ist abgelaufen
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Quick Actions */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Schnellaktionen</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                <Button
                                    variant="outline"
                                    className="w-full justify-start"
                                    onClick={() => window.open(route("offers.pdf", offer.id), "_blank")}
                                >
                                    <Download className="mr-2 h-4 w-4" />
                                    PDF herunterladen
                                </Button>
                                {offer.status === "draft" && (
                                    <Button
                                        variant="outline"
                                        className="w-full justify-start"
                                        onClick={handleSend}
                                    >
                                        <Send className="mr-2 h-4 w-4" />
                                        Angebot versenden
                                    </Button>
                                )}
                                {offer.status === "accepted" && !offer.converted_to_invoice_id && (
                                    <Button
                                        className="w-full justify-start"
                                        onClick={handleConvertToInvoice}
                                    >
                                        <FileText className="mr-2 h-4 w-4" />
                                        In Rechnung umwandeln
                                    </Button>
                                )}
                                {offer.converted_to_invoice_id && offer.converted_to_invoice && (
                                    <Link href={`/invoices/${offer.converted_to_invoice.id}`} className="block">
                                        <Button
                                            variant="outline"
                                            className="w-full justify-start"
                                        >
                                            <FileText className="mr-2 h-4 w-4" />
                                            Zur Rechnung ({offer.converted_to_invoice.number})
                                        </Button>
                                    </Link>
                                )}
                                {offer.status === "sent" && (
                                    <>
                                        <Button
                                            variant="outline"
                                            className="w-full justify-start"
                                            onClick={handleAccept}
                                        >
                                            <CheckCircle className="mr-2 h-4 w-4" />
                                            Als angenommen markieren
                                        </Button>
                                        <Button
                                            variant="outline"
                                            className="w-full justify-start"
                                            onClick={handleReject}
                                        >
                                            <XCircle className="mr-2 h-4 w-4" />
                                            Als abgelehnt markieren
                                        </Button>
                                    </>
                                )}
                            </CardContent>
                        </Card>

                        {/* Metadata */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Metadaten</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <div className="text-sm text-muted-foreground">Erstellt am</div>
                                    <div className="font-medium">
                                        {new Date(offer.created_at).toLocaleDateString("de-DE")}
                                    </div>
                                </div>
                                {offer.user && (
                                    <div>
                                        <div className="text-sm text-muted-foreground">Erstellt von</div>
                                        <div className="font-medium">{offer.user.name}</div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    )
}
