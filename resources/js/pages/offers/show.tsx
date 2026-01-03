"use client"

import type React from "react"
import { Head, Link, router, usePage } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { ArrowLeft, Edit, Trash2, FileText, Download, Send, CheckCircle, XCircle, Clock, Eye } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem } from "@/types"
import { route } from "ziggy-js"

interface OfferItem {
    id: number | string
    description: string
    quantity: number
    unit_price: number
    unit: string
    total: number
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
    // @ts-ignore
    const { offer, settings } = usePage<OffersShowProps>().props

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Angebote", href: "/offers" },
        { title: offer.number },
    ]

    const getStatusBadge = (status: string) => {
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

        const config = statusConfig[status as keyof typeof statusConfig] || statusConfig.draft
        return <Badge variant={config.variant}>{config.label}</Badge>
    }

    const formatCurrency = (amount: number | string | null | undefined) => {
        const numAmount = typeof amount === 'string' ? parseFloat(amount) : (amount || 0)
        if (isNaN(numAmount)) return "0,00 €"
        return new Intl.NumberFormat("de-DE", {
            style: "currency",
            currency: "EUR",
        }).format(numAmount)
    }

    const formatDate = (date: string) => {
        return new Date(date).toLocaleDateString("de-DE")
    }

    const handleDelete = () => {
        if (confirm(`Möchten Sie das Angebot "${offer.number}" wirklich löschen?`)) {
            router.delete(route("offers.destroy", offer.id))
        }
    }

    const handleConvertToInvoice = () => {
        if (confirm("Möchten Sie dieses Angebot in eine Rechnung umwandeln?")) {
            router.post(route("offers.convert-to-invoice", offer.id))
        }
    }

    const handleAccept = () => {
        if (confirm("Möchten Sie dieses Angebot als angenommen markieren?")) {
            router.post(route("offers.accept", offer.id))
        }
    }

    const handleReject = () => {
        if (confirm("Möchten Sie dieses Angebot als abgelehnt markieren?")) {
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
                                Zurück
                            </Button>
                        </Link>
                        <div>
                            <div className="flex items-center gap-3">
                                <h1 className="text-3xl font-bold text-gray-900">
                                    Angebot {offer.number}
                                </h1>
                                {getStatusBadge(offer.status)}
                                {isExpired && (
                                    <Badge variant="destructive">Abgelaufen</Badge>
                                )}
                            </div>
                            <p className="text-gray-600 mt-1">
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
                                Bearbeiten
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
                            Löschen
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Main Content */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Offer Details */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Angebotsdetails</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <div className="text-sm text-gray-600">Angebotsnummer</div>
                                        <div className="font-medium">{offer.number}</div>
                                    </div>
                                    <div>
                                        <div className="text-sm text-gray-600">Status</div>
                                        <div className="mt-1">{getStatusBadge(offer.status)}</div>
                                    </div>
                                    <div>
                                        <div className="text-sm text-gray-600">Angebotsdatum</div>
                                        <div className="font-medium">{formatDate(offer.issue_date)}</div>
                                    </div>
                                    <div>
                                        <div className="text-sm text-gray-600">Gültig bis</div>
                                        <div className={`font-medium ${isExpired ? 'text-red-600' : ''}`}>
                                            {formatDate(offer.valid_until)}
                                            {isExpired && " (abgelaufen)"}
                                        </div>
                                    </div>
                                    <div>
                                        <div className="text-sm text-gray-600">Kunde</div>
                                        <div className="font-medium">{offer.customer?.name}</div>
                                    </div>
                                    {offer.customer?.email && (
                                        <div>
                                            <div className="text-sm text-gray-600">E-Mail</div>
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
                                            <TableHead>Beschreibung</TableHead>
                                            <TableHead>Menge</TableHead>
                                            <TableHead>Einzelpreis</TableHead>
                                            <TableHead className="text-right">Gesamt</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {offer.items?.map((item) => (
                                            <TableRow key={item.id}>
                                                <TableCell>{item.description}</TableCell>
                                                <TableCell>
                                                    {item.quantity} {item.unit}
                                                </TableCell>
                                                <TableCell>{formatCurrency(item.unit_price)}</TableCell>
                                                <TableCell className="text-right font-medium">
                                                    {formatCurrency(item.total)}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>

                                <div className="mt-4 flex justify-end">
                                    <div className="w-64 space-y-2">
                                        <div className="flex justify-between">
                                            <span className="text-gray-600">Zwischensumme (netto)</span>
                                            <span className="font-medium">{formatCurrency(offer.subtotal)}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="text-gray-600">
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
                                    <CardTitle>Notizen</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="whitespace-pre-wrap">{offer.notes}</p>
                                </CardContent>
                            </Card>
                        )}

                        {offer.terms_conditions && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Allgemeine Geschäftsbedingungen</CardTitle>
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
                                <CardTitle>Angebotsübersicht</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <div className="text-sm text-gray-600">Gesamtbetrag</div>
                                    <div className="text-2xl font-bold">{formatCurrency(offer.total)}</div>
                                </div>
                                <div>
                                    <div className="text-sm text-gray-600">Status</div>
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
                                    <div className="text-sm text-gray-600">Erstellt am</div>
                                    <div className="font-medium">
                                        {new Date(offer.created_at).toLocaleDateString("de-DE")}
                                    </div>
                                </div>
                                {offer.user && (
                                    <div>
                                        <div className="text-sm text-gray-600">Erstellt von</div>
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
