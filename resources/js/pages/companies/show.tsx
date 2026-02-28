"use client"

import { Head, Link, router } from "@inertiajs/react"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import {
    Building2,
    Mail,
    Phone,
    MapPin,
    Globe,
    CreditCard,
    Users,
    FileText,
    DollarSign,
    Calendar,
    Edit,
    ArrowLeft,
    Landmark,
    Settings,
    TrendingUp,
    UserCircle,
    CheckCircle,
    XCircle,
} from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import { useTranslation } from "react-i18next"
import type { User } from "@/types"

interface Company {
    id: string
    name: string
    email: string
    phone?: string
    address?: string
    postal_code?: string
    city?: string
    country?: string
    tax_number?: string
    vat_number?: string
    website?: string
    bank_name?: string
    bank_iban?: string
    bank_bic?: string
    smtp_host?: string
    smtp_port?: number
    smtp_username?: string
    smtp_encryption?: string
    smtp_from_address?: string
    smtp_from_name?: string
    created_at: string
    users?: CompanyUser[]
    settings?: CompanySetting[]
}

interface CompanyUser {
    id: string
    name: string
    email: string
    role: string
    status: string
    created_at: string
}

interface CompanySetting {
    key: string
    value: string
    type: string
}

interface Stats {
    users_count: number
    customers_count: number
    invoices_count: number
    offers_count: number
    total_revenue: number
    pending_invoices: number
}

interface ShowProps {
    auth: { user: User }
    company: Company
    stats: Stats
}

export default function Show({ auth, company, stats }: ShowProps) {
    const { t } = useTranslation()
    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat("de-DE", {
            style: "currency",
            currency: "EUR",
        }).format(amount)
    }

    const formatDate = (date: string) => {
        return new Date(date).toLocaleDateString("de-DE", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
        })
    }

    const getRoleBadge = (role: string) => {
        const variants = {
            admin: "default" as const,
            user: "secondary" as const,
        }

        const labels = {
            admin: "Administrator",
            user: "Benutzer",
        }

        return <Badge variant={variants[role as keyof typeof variants]}>{labels[role as keyof typeof labels] || role}</Badge>
    }

    const getStatusBadge = (status: string) => {
        if (status === "active") {
            return (
                <Badge variant="default" className="flex items-center gap-1">
                    <CheckCircle className="h-3 w-3" />
                    Aktiv
                </Badge>
            )
        }
        return (
            <Badge variant="secondary" className="flex items-center gap-1">
                <XCircle className="h-3 w-3" />
                Inaktiv
            </Badge>
        )
    }

    return (
        <AppLayout user={auth.user}>
            <Head title={`${company.name} - Details`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/companies">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                {t('common.back')}
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-1xl font-bold tracking-tight">{company.name}</h1>
                            <p className="text-muted-foreground">{t('settings.companyDetailsTitle')}</p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-2">
                        <Button variant="outline" asChild>
                            <Link href={`/companies/${company.id}/edit`}>
                                <Edit className="h-4 w-4 mr-2" />
                                {t('common.edit')}
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Statistics Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-6">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{t('pages.users.role')}</CardTitle>
                            <Users className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.users_count}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{t('nav.customers')}</CardTitle>
                            <UserCircle className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.customers_count}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{t('nav.invoices')}</CardTitle>
                            <FileText className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.invoices_count}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{t('nav.offers')}</CardTitle>
                            <FileText className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.offers_count}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Gesamtumsatz</CardTitle>
                            <TrendingUp className="h-4 w-4 text-green-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(stats.total_revenue)}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{t('pages.reports.openInvoices')}</CardTitle>
                            <DollarSign className="h-4 w-4 text-yellow-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.pending_invoices}</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Tabs */}
                <Tabs defaultValue="overview" className="space-y-4">
                    <TabsList>
                        <TabsTrigger value="overview">{t('common.overview')}</TabsTrigger>
                        <TabsTrigger value="users">Benutzer ({stats.users_count})</TabsTrigger>
                        <TabsTrigger value="settings">{t('nav.settings')}</TabsTrigger>
                    </TabsList>

                    <TabsContent value="overview" className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            {/* Company Information */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center">
                                        <Building2 className="h-5 w-5 mr-2" />
                                        Firmeninformationen
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="space-y-3">
                                        <div className="flex items-start">
                                            <Mail className="h-4 w-4 mr-2 mt-0.5 text-muted-foreground" />
                                            <div>
                                                <div className="text-sm font-medium">E-Mail</div>
                                                <div className="text-sm text-muted-foreground">{company.email || "-"}</div>
                                            </div>
                                        </div>

                                        {company.phone && (
                                            <div className="flex items-start">
                                                <Phone className="h-4 w-4 mr-2 mt-0.5 text-muted-foreground" />
                                                <div>
                                                    <div className="text-sm font-medium">Telefon</div>
                                                    <div className="text-sm text-muted-foreground">{company.phone}</div>
                                                </div>
                                            </div>
                                        )}

                                        {(company.address || company.city) && (
                                            <div className="flex items-start">
                                                <MapPin className="h-4 w-4 mr-2 mt-0.5 text-muted-foreground" />
                                                <div>
                                                    <div className="text-sm font-medium">Adresse</div>
                                                    <div className="text-sm text-muted-foreground">
                                                        {company.address && <div>{company.address}</div>}
                                                        {(company.postal_code || company.city) && (
                                                            <div>
                                                                {company.postal_code} {company.city}
                                                            </div>
                                                        )}
                                                        {company.country && <div>{company.country}</div>}
                                                    </div>
                                                </div>
                                            </div>
                                        )}

                                        {company.website && (
                                            <div className="flex items-start">
                                                <Globe className="h-4 w-4 mr-2 mt-0.5 text-muted-foreground" />
                                                <div>
                                                    <div className="text-sm font-medium">Website</div>
                                                    <a
                                                        href={company.website}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="text-sm text-blue-600 hover:underline"
                                                    >
                                                        {company.website}
                                                    </a>
                                                </div>
                                            </div>
                                        )}

                                        {company.tax_number && (
                                            <div className="flex items-start">
                                                <FileText className="h-4 w-4 mr-2 mt-0.5 text-muted-foreground" />
                                                <div>
                                                    <div className="text-sm font-medium">Steuernummer</div>
                                                    <div className="text-sm text-muted-foreground">{company.tax_number}</div>
                                                </div>
                                            </div>
                                        )}

                                        {company.vat_number && (
                                            <div className="flex items-start">
                                                <FileText className="h-4 w-4 mr-2 mt-0.5 text-muted-foreground" />
                                                <div>
                                                    <div className="text-sm font-medium">USt-IdNr.</div>
                                                    <div className="text-sm text-muted-foreground">{company.vat_number}</div>
                                                </div>
                                            </div>
                                        )}

                                        <div className="flex items-start">
                                            <Calendar className="h-4 w-4 mr-2 mt-0.5 text-muted-foreground" />
                                            <div>
                                                <div className="text-sm font-medium">Erstellt am</div>
                                                <div className="text-sm text-muted-foreground">{formatDate(company.created_at)}</div>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Banking Information */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center">
                                        <Landmark className="h-5 w-5 mr-2" />
                                        Bankverbindung
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {company.bank_iban || company.bank_bic || company.bank_name ? (
                                        <div className="space-y-3">
                                            {company.bank_name && (
                                                <div className="flex items-start">
                                                    <Landmark className="h-4 w-4 mr-2 mt-0.5 text-muted-foreground" />
                                                    <div>
                                                        <div className="text-sm font-medium">Bank</div>
                                                        <div className="text-sm text-muted-foreground">{company.bank_name}</div>
                                                    </div>
                                                </div>
                                            )}

                                            {company.bank_iban && (
                                                <div className="flex items-start">
                                                    <CreditCard className="h-4 w-4 mr-2 mt-0.5 text-muted-foreground" />
                                                    <div>
                                                        <div className="text-sm font-medium">IBAN</div>
                                                        <div className="text-sm text-muted-foreground font-mono">{company.bank_iban}</div>
                                                    </div>
                                                </div>
                                            )}

                                            {company.bank_bic && (
                                                <div className="flex items-start">
                                                    <CreditCard className="h-4 w-4 mr-2 mt-0.5 text-muted-foreground" />
                                                    <div>
                                                        <div className="text-sm font-medium">BIC</div>
                                                        <div className="text-sm text-muted-foreground font-mono">{company.bank_bic}</div>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    ) : (
                                        <p className="text-sm text-muted-foreground">Keine Bankverbindung hinterlegt</p>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Email Settings */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center">
                                        <Mail className="h-5 w-5 mr-2" />
                                        E-Mail Einstellungen
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {company.smtp_host ? (
                                        <div className="space-y-3">
                                            <div className="flex items-start">
                                                <Settings className="h-4 w-4 mr-2 mt-0.5 text-muted-foreground" />
                                                <div>
                                                    <div className="text-sm font-medium">SMTP Server</div>
                                                    <div className="text-sm text-muted-foreground">
                                                        {company.smtp_host}:{company.smtp_port}
                                                    </div>
                                                </div>
                                            </div>

                                            {company.smtp_username && (
                                                <div className="flex items-start">
                                                    <UserCircle className="h-4 w-4 mr-2 mt-0.5 text-muted-foreground" />
                                                    <div>
                                                        <div className="text-sm font-medium">Benutzername</div>
                                                        <div className="text-sm text-muted-foreground">{company.smtp_username}</div>
                                                    </div>
                                                </div>
                                            )}

                                            {company.smtp_from_address && (
                                                <div className="flex items-start">
                                                    <Mail className="h-4 w-4 mr-2 mt-0.5 text-muted-foreground" />
                                                    <div>
                                                        <div className="text-sm font-medium">Absender</div>
                                                        <div className="text-sm text-muted-foreground">
                                                            {company.smtp_from_name ? `${company.smtp_from_name} <${company.smtp_from_address}>` : company.smtp_from_address}
                                                        </div>
                                                    </div>
                                                </div>
                                            )}

                                            {company.smtp_encryption && (
                                                <div className="flex items-start">
                                                    <Settings className="h-4 w-4 mr-2 mt-0.5 text-muted-foreground" />
                                                    <div>
                                                        <div className="text-sm font-medium">{t('settings.smtpEncryption')}</div>
                                                        <div className="text-sm text-muted-foreground uppercase">{company.smtp_encryption}</div>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    ) : (
                                        <p className="text-sm text-muted-foreground">Keine E-Mail-Einstellungen konfiguriert</p>
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    <TabsContent value="users" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('pages.users.role')}</CardTitle>
                                <CardDescription>Alle Benutzer dieser Firma</CardDescription>
                            </CardHeader>
                            <CardContent>
                                {company.users && company.users.length > 0 ? (
                                    <div className="overflow-x-auto">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>{t('common.name')}</TableHead>
                                                    <TableHead>E-Mail</TableHead>
                                                    <TableHead>Rolle</TableHead>
                                                    <TableHead>{t('common.status')}</TableHead>
                                                    <TableHead>Erstellt am</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {company.users.map((user) => (
                                                    <TableRow key={user.id}>
                                                        <TableCell className="font-medium">{user.name}</TableCell>
                                                        <TableCell>{user.email}</TableCell>
                                                        <TableCell>{getRoleBadge(user.role)}</TableCell>
                                                        <TableCell>{getStatusBadge(user.status)}</TableCell>
                                                        <TableCell>{formatDate(user.created_at)}</TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>
                                    </div>
                                ) : (
                                    <p className="text-sm text-muted-foreground text-center py-8">Keine Benutzer vorhanden</p>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="settings" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Firmeneinstellungen</CardTitle>
                                <CardDescription>{t('pages.companies.allSettings')}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                {company.settings && company.settings.length > 0 ? (
                                    <div className="overflow-x-auto">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>{t('common.key')}</TableHead>
                                                    <TableHead>{t('common.value')}</TableHead>
                                                    <TableHead>{t('common.type')}</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {company.settings.map((setting, index) => (
                                                    <TableRow key={`${setting.key}-${index}`}>
                                                        <TableCell className="font-medium font-mono text-sm">{setting.key}</TableCell>
                                                        <TableCell className="max-w-md truncate">{setting.value}</TableCell>
                                                        <TableCell>
                                                            <Badge variant="outline">{setting.type}</Badge>
                                                        </TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>
                                    </div>
                                ) : (
                                    <p className="text-sm text-muted-foreground text-center py-8">Keine Einstellungen vorhanden</p>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    )
}
