"use client"

import { Head, Link, usePage } from "@inertiajs/react"
import { useTranslation } from "react-i18next"
import {
    ArrowLeft,
    Edit,
    Plus,
    Mail,
    Phone,
    MapPin,
    Building,
    FileText,
    EuroIcon,
    Euro,
    User,
    Hash,
    Globe,
    AlertCircle,
    CheckCircle,
    Clock,
    XCircle,
} from "lucide-react"
import { formatCurrency as formatCurrencyUtil } from "@/utils/formatting"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Separator } from "@/components/ui/separator"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Alert, AlertDescription } from "@/components/ui/alert"
import AppLayout from "@/layouts/app-layout"
import type { Customer, Invoice, Offer, BreadcrumbItem } from "@/types"

interface CustomerShowProps {
    customer: Customer & {
        invoices: Invoice[]
        offers: Offer[]
    }
    stats: {
        total_invoices: number
        total_offers: number
        total_revenue: number
        outstanding_amount: number
        average_invoice_amount: number
        last_invoice_date: string | null
        last_offer_date: string | null
    }
}

export default function CustomerShow() {
    const { t } = useTranslation()
    const { customer, stats } = usePage<CustomerShowProps>().props
    const settings = (usePage().props as any).auth?.user?.company?.settings ?? {}

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Kunden", href: "/customers" },
        { title: customer.name },
    ]

    const getStatusBadge = (status: string, type: "invoice" | "offer") => {
        const variants = {
            invoice: {
                draft: { variant: "secondary" as const, icon: Clock, text: "Entwurf" },
                sent: { variant: "default" as const, icon: Mail, text: "Versendet" },
                paid: {
                    variant: "default" as const,
                    icon: CheckCircle,
                    text: "Bezahlt",
                    className: "bg-green-500 hover:bg-green-600",
                },
                overdue: { variant: "destructive" as const, icon: AlertCircle, text: t('common.overdue')},
                cancelled: { variant: "secondary" as const, icon: XCircle, text: "Storniert" },
            },
            offer: {
                draft: { variant: "secondary" as const, icon: Clock, text: "Entwurf" },
                sent: { variant: "default" as const, icon: Mail, text: "Versendet" },
                accepted: {
                    variant: "default" as const,
                    icon: CheckCircle,
                    text: "Angenommen",
                    className: "bg-green-500 hover:bg-green-600",
                },
                rejected: { variant: "destructive" as const, icon: XCircle, text: "Abgelehnt" },
                expired: { variant: "secondary" as const, icon: AlertCircle, text: "Abgelaufen" },
            },
        }

        const config = variants[type][status as keyof (typeof variants)[typeof type]]
        if (!config) return null

        const Icon = config.icon

        return (
            <Badge variant={config.variant} className={config.className}>
                <Icon className="w-3 h-3 mr-1" />
                {config.text}
            </Badge>
        )
    }

    const formatCurrency = (amount: number) => formatCurrencyUtil(amount, settings)

    const formatDate = (dateString: string | null | undefined) => {
        if (!dateString) return "—"
        return new Date(dateString).toLocaleDateString("de-DE", {
            year: "numeric",
            month: "2-digit",
            day: "2-digit",
        })
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Kunde: ${customer.name}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/customers">
                                <ArrowLeft className="w-4 h-4 mr-2" />
                                {t('pages.customers.backToCustomers')}
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold">{customer.name}</h1>
                            <p className="text-muted-foreground">Kunde #{customer.number}</p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-2">
                        <Button variant="outline" asChild>
                            <Link href={`/customers/${customer.id}/edit`}>
                                <Edit className="w-4 h-4 mr-2" />
                                {t('common.edit')}
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={`/invoices/create?customer_id=${customer.id}`}>
                                <EuroIcon className="w-4 h-4 mr-2" />
                                Neue Rechnung
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={`/offers/create?customer_id=${customer.id}`}>
                                <FileText className="w-4 h-4 mr-2" />
                                Neues Angebot
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Customer Status Alert */}
                {customer.status === "inactive" && (
                    <Alert variant="destructive">
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>
                            {t('pages.customers.inactiveWarning')}
                        </AlertDescription>
                    </Alert>
                )}

                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Gesamtumsatz</CardTitle>
                            <Euro className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">{formatCurrency(stats.total_revenue)}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{t('pages.customers.outstanding')}</CardTitle>
                            <AlertCircle className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-orange-600">{formatCurrency(stats.outstanding_amount)}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{t('nav.invoices')}</CardTitle>
                            <EuroIcon className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_invoices}</div>
                            <p className="text-xs text-muted-foreground">Ø {formatCurrency(stats.average_invoice_amount)}</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{t('nav.offers')}</CardTitle>
                            <FileText className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_offers}</div>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Customer Details */}
                    <Card className="lg:col-span-1">
                        <CardHeader>
                            <CardTitle>Kundendetails</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center space-x-2">
                                <Hash className="w-4 h-4 text-muted-foreground" />
                                <span className="text-sm font-medium">Kundennummer:</span>
                                <span className="text-sm">{customer.number}</span>
                            </div>

                            {customer.email && (
                                <div className="flex items-center space-x-2">
                                    <Mail className="w-4 h-4 text-muted-foreground" />
                                    <span className="text-sm font-medium">E-Mail:</span>
                                    <a href={`mailto:${customer.email}`} className="text-sm text-blue-600 hover:underline">
                                        {customer.email}
                                    </a>
                                </div>
                            )}

                            {customer.phone && (
                                <div className="flex items-center space-x-2">
                                    <Phone className="w-4 h-4 text-muted-foreground" />
                                    <span className="text-sm font-medium">Telefon:</span>
                                    <a href={`tel:${customer.phone}`} className="text-sm text-blue-600 hover:underline">
                                        {customer.phone}
                                    </a>
                                </div>
                            )}

                            {customer.contact_person && (
                                <div className="flex items-center space-x-2">
                                    <User className="w-4 h-4 text-muted-foreground" />
                                    <span className="text-sm font-medium">Ansprechpartner:</span>
                                    <span className="text-sm">{customer.contact_person}</span>
                                </div>
                            )}

                            {(customer.address || customer.city) && (
                                <div className="flex items-start space-x-2">
                                    <MapPin className="w-4 h-4 text-muted-foreground mt-0.5" />
                                    <div className="text-sm">
                                        <div className="font-medium">Adresse:</div>
                                        {customer.address && <div>{customer.address}</div>}
                                        {(customer.postal_code || customer.city) && (
                                            <div>
                                                {customer.postal_code} {customer.city}
                                            </div>
                                        )}
                                        {customer.country && <div>{customer.country}</div>}
                                    </div>
                                </div>
                            )}

                            {customer.tax_number && (
                                <div className="flex items-center space-x-2">
                                    <Building className="w-4 h-4 text-muted-foreground" />
                                    <span className="text-sm font-medium">Steuernummer:</span>
                                    <span className="text-sm">{customer.tax_number}</span>
                                </div>
                            )}

                            {customer.vat_number && (
                                <div className="flex items-center space-x-2">
                                    <Globe className="w-4 h-4 text-muted-foreground" />
                                    <span className="text-sm font-medium">USt-IdNr.:</span>
                                    <span className="text-sm">{customer.vat_number}</span>
                                </div>
                            )}

                            <Separator />

                            <div className="flex items-center justify-between">
                                <span className="text-sm font-medium">Status:</span>
                                <Badge variant={customer.status === "active" ? "default" : "secondary"}>
                                    {customer.status === "active" ? "Aktiv" : "Inaktiv"}
                                </Badge>
                            </div>

                            <div className="flex items-center justify-between">
                                <span className="text-sm font-medium">Kundentyp:</span>
                                <Badge variant="outline">
                                    {customer.customer_type === "business" ? t('pages.customers.typeBusiness') : t('pages.customers.typePrivate')}
                                </Badge>
                            </div>

                            <div className="text-xs text-muted-foreground">Erstellt am: {formatDate(customer.created_at)}</div>
                        </CardContent>
                    </Card>

                    {/* Invoices and Offers */}
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Rechnungen & Angebote</CardTitle>
                            <CardDescription>{t('pages.customers.allDocuments')}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Tabs defaultValue="invoices" className="w-full">
                                <TabsList className="grid w-full grid-cols-2">
                                    <TabsTrigger value="invoices">Rechnungen ({customer.invoices?.length || 0})</TabsTrigger>
                                    <TabsTrigger value="offers">Angebote ({customer.offers?.length || 0})</TabsTrigger>
                                </TabsList>

                                <TabsContent value="invoices" className="space-y-4">
                                    {customer.invoices && customer.invoices.length > 0 ? (
                                        <div className="rounded-md border">
                                            <Table>
                                                <TableHeader>
                                                    <TableRow>
                                                        <TableHead>{t('common.number')}</TableHead>
                                                        <TableHead>Datum</TableHead>
                                                        <TableHead>{t('pages.invoices.dueDate')}</TableHead>
                                                        <TableHead>{t('common.status')}</TableHead>
                                                        <TableHead className="text-right">{t('common.amount')}</TableHead>
                                                        <TableHead className="w-[100px]">{t('common.actions')}</TableHead>
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    {customer.invoices.map((invoice) => (
                                                        <TableRow key={invoice.id}>
                                                            <TableCell className="font-medium">
                                                                <Link href={`/invoices/${invoice.id}`} className="text-blue-600 hover:underline">
                                                                    {invoice.number}
                                                                </Link>
                                                            </TableCell>
                                                            <TableCell>{formatDate(invoice.issue_date)}</TableCell>
                                                            <TableCell>{formatDate(invoice.due_date)}</TableCell>
                                                            <TableCell>{getStatusBadge(invoice.status, "invoice")}</TableCell>
                                                            <TableCell className="text-right font-medium">{formatCurrency(invoice.total)}</TableCell>
                                                            <TableCell>
                                                                <div className="flex items-center space-x-2">
                                                                    <Button variant="ghost" size="sm" asChild>
                                                                        <Link href={`/invoices/${invoice.id}/edit`}>
                                                                            <Edit className="w-4 h-4" />
                                                                        </Link>
                                                                    </Button>
                                                                </div>
                                                            </TableCell>
                                                        </TableRow>
                                                    ))}
                                                </TableBody>
                                            </Table>
                                        </div>
                                    ) : (
                                        <div className="text-center py-8">
                                            <EuroIcon className="w-12 h-12 text-muted-foreground mx-auto mb-4" />
                                            <h3 className="text-lg font-medium mb-2">Keine Rechnungen</h3>
                                            <p className="text-muted-foreground mb-4">
                                                {t('pages.customers.noInvoices')}
                                            </p>
                                            <Button asChild>
                                                <Link href={`/invoices/create?customer_id=${customer.id}`}>
                                                    <Plus className="w-4 h-4 mr-2" />
                                                    Erste Rechnung erstellen
                                                </Link>
                                            </Button>
                                        </div>
                                    )}
                                </TabsContent>

                                <TabsContent value="offers" className="space-y-4">
                                    {customer.offers && customer.offers.length > 0 ? (
                                        <div className="rounded-md border">
                                            <Table>
                                                <TableHeader>
                                                    <TableRow>
                                                        <TableHead>{t('common.number')}</TableHead>
                                                        <TableHead>Datum</TableHead>
                                                        <TableHead>{t('pages.offers.validUntil')}</TableHead>
                                                        <TableHead>{t('common.status')}</TableHead>
                                                        <TableHead className="text-right">{t('common.amount')}</TableHead>
                                                        <TableHead className="w-[100px]">{t('common.actions')}</TableHead>
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    {customer.offers.map((offer) => (
                                                        <TableRow key={offer.id}>
                                                            <TableCell className="font-medium">
                                                                <Link href={`/offers/${offer.id}`} className="text-blue-600 hover:underline">
                                                                    {offer.number}
                                                                </Link>
                                                            </TableCell>
                                                            <TableCell>{formatDate(offer.issue_date)}</TableCell>
                                                            <TableCell>{formatDate(offer.valid_until)}</TableCell>
                                                            <TableCell>{getStatusBadge(offer.status, "offer")}</TableCell>
                                                            <TableCell className="text-right font-medium">{formatCurrency(offer.total)}</TableCell>
                                                            <TableCell>
                                                                <div className="flex items-center space-x-2">
                                                                    <Button variant="ghost" size="sm" asChild>
                                                                        <Link href={`/offers/${offer.id}/edit`}>
                                                                            <Edit className="w-4 h-4" />
                                                                        </Link>
                                                                    </Button>
                                                                </div>
                                                            </TableCell>
                                                        </TableRow>
                                                    ))}
                                                </TableBody>
                                            </Table>
                                        </div>
                                    ) : (
                                        <div className="text-center py-8">
                                            <FileText className="w-12 h-12 text-muted-foreground mx-auto mb-4" />
                                            <h3 className="text-lg font-medium mb-2">Keine Angebote</h3>
                                            <p className="text-muted-foreground mb-4">
                                                {t('pages.customers.noOffers')}
                                            </p>
                                            <Button asChild>
                                                <Link href={`/offers/create?customer_id=${customer.id}`}>
                                                    <Plus className="w-4 h-4 mr-2" />
                                                    Erstes Angebot erstellen
                                                </Link>
                                            </Button>
                                        </div>
                                    )}
                                </TabsContent>
                            </Tabs>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    )
}
