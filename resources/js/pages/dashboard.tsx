import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import {
    Users,
    FileText,
    Receipt,
    Plus,
    Settings,
    Package,
    Euro,
    TrendingUp,
    TrendingDown,
    AlertTriangle,
    Clock,
    CheckCircle,
    XCircle,
    Archive,
    Eye,
    Edit,
} from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem, User, Customer, Invoice, Offer, Product } from "@/types"
import { Head, Link, usePage } from "@inertiajs/react"

interface DashboardStats {
    customers: {
        total: number
        active: number
        new_this_month: number
    }
    invoices: {
        total: number
        draft: number
        sent: number
        paid: number
        overdue: number
        total_amount: number
        paid_amount: number
        outstanding_amount: number
    }
    offers: {
        total: number
        draft: number
        sent: number
        accepted: number
        rejected: number
        expired: number
        total_amount: number
    }
    products: {
        total: number
        active: number
        low_stock: number
        out_of_stock: number
    }
    revenue: {
        this_month: number
        last_month: number
        this_year: number
        last_year: number
    }
}

interface GrowthData {
    revenue_growth: number
    invoice_growth: number
    customer_growth: number
}

interface DashboardProps {
    stats: DashboardStats
    growth: GrowthData
    recent: {
        invoices: Invoice[]
        offers: Offer[]
        customers: Customer[]
    }
    alerts: {
        overdue_invoices: Invoice[]
        low_stock_products: Product[]
    }
    user: User
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Dashboard",
    },
]

export default function Dashboard() {
    const { stats, growth, recent, alerts, user } = usePage<DashboardProps>().props

    const getStatusColor = (status: string) => {
        switch (status) {
            case "paid":
                return "bg-green-100 text-green-800"
            case "sent":
                return "bg-blue-100 text-blue-800"
            case "overdue":
                return "bg-red-100 text-red-800"
            case "draft":
                return "bg-gray-100 text-gray-800"
            case "accepted":
                return "bg-green-100 text-green-800"
            case "rejected":
                return "bg-red-100 text-red-800"
            case "expired":
                return "bg-orange-100 text-orange-800"
            default:
                return "bg-gray-100 text-gray-800"
        }
    }

    const getStatusIcon = (status: string) => {
        switch (status) {
            case "paid":
                return <CheckCircle className="h-4 w-4" />
            case "sent":
                return <Clock className="h-4 w-4" />
            case "overdue":
                return <AlertTriangle className="h-4 w-4" />
            case "draft":
                return <Edit className="h-4 w-4" />
            case "accepted":
                return <CheckCircle className="h-4 w-4" />
            case "rejected":
                return <XCircle className="h-4 w-4" />
            case "expired":
                return <Archive className="h-4 w-4" />
            default:
                return <Clock className="h-4 w-4" />
        }
    }

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat("de-DE", {
            style: "currency",
            currency: user.company?.settings?.default_currency || "EUR",
        }).format(amount)
    }

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString("de-DE")
    }

    const getGrowthIcon = (growth: number) => {
        if (growth > 0) return <TrendingUp className="h-4 w-4 text-green-600" />
        if (growth < 0) return <TrendingDown className="h-4 w-4 text-red-600" />
        return <div className="h-4 w-4" />
    }

    const getGrowthColor = (growth: number) => {
        if (growth > 0) return "text-green-600"
        if (growth < 0) return "text-red-600"
        return "text-gray-600"
    }

    const translateStatus = (status: string) => {
        const statusMap: Record<string, string> = {
            draft: "Entwurf",
            sent: "Versendet",
            paid: "Bezahlt",
            overdue: "Überfällig",
            cancelled: "Storniert",
            accepted: "Angenommen",
            rejected: "Abgelehnt",
            expired: "Abgelaufen",
        }
        return statusMap[status] || status
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="flex flex-1 flex-col gap-6">
                {/* Welcome Section */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Dashboard</h1>
                        <p className="text-gray-600">Willkommen zurück, {user.name}</p>
                        <p className="text-sm text-gray-500">{user.company?.name || "Keine Firma"}</p>
                    </div>

                    <div className="flex gap-2">
                        <Link href="/invoices/create">
                            <Button>
                                <Plus className="mr-2 h-4 w-4" />
                                Neue Rechnung
                            </Button>
                        </Link>
                        <Link href="/offers/create">
                            <Button variant="outline">
                                <Plus className="mr-2 h-4 w-4" />
                                Neues Angebot
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Main Stats Cards */}
                <div className="grid auto-rows-min gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Kunden</CardTitle>
                            <Users className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.customers.total}</div>
                            <div className="flex items-center space-x-2 text-xs text-muted-foreground">
                                <span>{stats.customers.active} aktiv</span>
                                <span>•</span>
                                <span>{stats.customers.new_this_month} neu</span>
                            </div>
                            <Link href="/customers" className="text-xs text-muted-foreground hover:underline">
                                Alle Kunden anzeigen
                            </Link>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Rechnungen</CardTitle>
                            <Receipt className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.invoices.total}</div>
                            <div className="flex items-center space-x-2 text-xs text-muted-foreground">
                                <span className="text-green-600">{stats.invoices.paid} bezahlt</span>
                                <span>•</span>
                                <span className="text-red-600">{stats.invoices.overdue} überfällig</span>
                            </div>
                            <Link href="/invoices" className="text-xs text-muted-foreground hover:underline">
                                Alle Rechnungen anzeigen
                            </Link>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Angebote</CardTitle>
                            <FileText className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.offers.total}</div>
                            <div className="flex items-center space-x-2 text-xs text-muted-foreground">
                                <span className="text-green-600">{stats.offers.accepted} angenommen</span>
                                <span>•</span>
                                <span className="text-blue-600">{stats.offers.sent} versendet</span>
                            </div>
                            <Link href="/offers" className="text-xs text-muted-foreground hover:underline">
                                Alle Angebote anzeigen
                            </Link>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Produkte</CardTitle>
                            <Package className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.products.total}</div>
                            <div className="flex items-center space-x-2 text-xs text-muted-foreground">
                                <span className="text-orange-600">{stats.products.low_stock} wenig Lager</span>
                                <span>•</span>
                                <span className="text-red-600">{stats.products.out_of_stock} ausverkauft</span>
                            </div>
                            <Link href="/products" className="text-xs text-muted-foreground hover:underline">
                                Alle Produkte anzeigen
                            </Link>
                        </CardContent>
                    </Card>
                </div>

                {/* Revenue Cards */}
                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Umsatz diesen Monat</CardTitle>
                            <Euro className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(stats.revenue.this_month)}</div>
                            <div className="flex items-center space-x-2 text-xs">
                                {getGrowthIcon(growth.revenue_growth)}
                                <span className={getGrowthColor(growth.revenue_growth)}>
                  {growth.revenue_growth > 0 ? "+" : ""}
                                    {growth.revenue_growth}% vs. letzter Monat
                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Offene Rechnungen</CardTitle>
                            <AlertTriangle className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(stats.invoices.outstanding_amount)}</div>
                            <div className="text-xs text-muted-foreground">
                                {stats.invoices.sent + stats.invoices.overdue} Rechnungen offen
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Umsatz dieses Jahr</CardTitle>
                            <TrendingUp className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(stats.revenue.this_year)}</div>
                            <div className="text-xs text-muted-foreground">
                                Bezahlte Rechnungen: {formatCurrency(stats.invoices.paid_amount)}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Alerts Section */}
                {(alerts.overdue_invoices.length > 0 || alerts.low_stock_products.length > 0) && (
                    <div className="grid gap-4 md:grid-cols-2">
                        {/* Overdue Invoices Alert */}
                        {alerts.overdue_invoices.length > 0 && (
                            <Card className="border-red-200">
                                <CardHeader>
                                    <CardTitle className="text-red-800 flex items-center">
                                        <AlertTriangle className="mr-2 h-5 w-5" />
                                        Überfällige Rechnungen ({alerts.overdue_invoices.length})
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-3">
                                        {alerts.overdue_invoices.slice(0, 5).map((invoice) => (
                                            <div key={invoice.id} className="flex items-center justify-between">
                                                <div>
                                                    <p className="font-medium">{invoice.number}</p>
                                                    <p className="text-sm text-gray-600">{invoice.customer?.name}</p>
                                                </div>
                                                <div className="text-right">
                                                    <p className="font-medium text-red-600">{formatCurrency(invoice.total)}</p>
                                                    <p className="text-xs text-gray-500">Fällig: {formatDate(invoice.due_date)}</p>
                                                </div>
                                            </div>
                                        ))}
                                        {alerts.overdue_invoices.length > 5 && (
                                            <Link href="/invoices?status=overdue" className="text-sm text-blue-600 hover:underline">
                                                Alle {alerts.overdue_invoices.length} überfälligen Rechnungen anzeigen
                                            </Link>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {/* Low Stock Alert */}
                        {alerts.low_stock_products.length > 0 && (
                            <Card className="border-orange-200">
                                <CardHeader>
                                    <CardTitle className="text-orange-800 flex items-center">
                                        <Package className="mr-2 h-5 w-5" />
                                        Niedriger Lagerbestand ({alerts.low_stock_products.length})
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-3">
                                        {alerts.low_stock_products.slice(0, 5).map((product) => (
                                            <div key={product.id} className="flex items-center justify-between">
                                                <div>
                                                    <p className="font-medium">{product.name}</p>
                                                    <p className="text-sm text-gray-600">{product.number}</p>
                                                </div>
                                                <div className="text-right">
                                                    <p className="font-medium text-orange-600">
                                                        {product.stock_quantity} {product.unit}
                                                    </p>
                                                    <p className="text-xs text-gray-500">Min: {product.min_stock_level}</p>
                                                </div>
                                            </div>
                                        ))}
                                        {alerts.low_stock_products.length > 5 && (
                                            <Link href="/products?filter=low_stock" className="text-sm text-blue-600 hover:underline">
                                                Alle {alerts.low_stock_products.length} Produkte mit niedrigem Lagerbestand anzeigen
                                            </Link>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                )}

                {/* Recent Activity */}
                <div className="grid gap-4 md:grid-cols-3">
                    {/* Recent Invoices */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Aktuelle Rechnungen</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {recent.invoices.map((invoice) => (
                                    <div key={invoice.id} className="flex items-center justify-between">
                                        <div className="flex items-center space-x-3">
                                            {getStatusIcon(invoice.status)}
                                            <div>
                                                <p className="font-medium">{invoice.number}</p>
                                                <p className="text-sm text-gray-600">{invoice.customer?.name}</p>
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            <p className="font-medium">{formatCurrency(invoice.total)}</p>
                                            <Badge variant="secondary" className={`text-xs ${getStatusColor(invoice.status)}`}>
                                                {translateStatus(invoice.status)}
                                            </Badge>
                                        </div>
                                    </div>
                                ))}
                                {recent.invoices.length === 0 && (
                                    <p className="text-gray-500 text-center py-4">Keine aktuellen Rechnungen</p>
                                )}
                                <Link href="/invoices" className="text-sm text-blue-600 hover:underline block text-center">
                                    Alle Rechnungen anzeigen
                                </Link>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Recent Offers */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Aktuelle Angebote</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {recent.offers.map((offer) => (
                                    <div key={offer.id} className="flex items-center justify-between">
                                        <div className="flex items-center space-x-3">
                                            {getStatusIcon(offer.status)}
                                            <div>
                                                <p className="font-medium">{offer.number}</p>
                                                <p className="text-sm text-gray-600">{offer.customer?.name}</p>
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            <p className="font-medium">{formatCurrency(offer.total)}</p>
                                            <Badge variant="secondary" className={`text-xs ${getStatusColor(offer.status)}`}>
                                                {translateStatus(offer.status)}
                                            </Badge>
                                        </div>
                                    </div>
                                ))}
                                {recent.offers.length === 0 && (
                                    <p className="text-gray-500 text-center py-4">Keine aktuellen Angebote</p>
                                )}
                                <Link href="/offers" className="text-sm text-blue-600 hover:underline block text-center">
                                    Alle Angebote anzeigen
                                </Link>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Quick Actions */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Schnellaktionen</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-2">
                                <Link href="/customers/create">
                                    <Button variant="outline" className="w-full justify-start bg-transparent">
                                        <Users className="mr-2 h-4 w-4" />
                                        Neuen Kunden hinzufügen
                                    </Button>
                                </Link>

                                <Link href="/products/create">
                                    <Button variant="outline" className="w-full justify-start bg-transparent">
                                        <Package className="mr-2 h-4 w-4" />
                                        Neues Produkt hinzufügen
                                    </Button>
                                </Link>

                                {user.role === "admin" && (
                                    <Link href="/users">
                                        <Button variant="outline" className="w-full justify-start bg-transparent">
                                            <Users className="mr-2 h-4 w-4" />
                                            Benutzer verwalten
                                        </Button>
                                    </Link>
                                )}

                                <Link href="/settings">
                                    <Button variant="outline" className="w-full justify-start bg-transparent">
                                        <Settings className="mr-2 h-4 w-4" />
                                        Firmeneinstellungen
                                    </Button>
                                </Link>

                                {user.role === "admin" && (
                                    <Link href="/settings/invoice-layouts">
                                        <Button variant="outline" className="w-full justify-start bg-transparent">
                                            <FileText className="mr-2 h-4 w-4" />
                                            Rechnungslayouts
                                        </Button>
                                    </Link>
                                )}

                                <Link href="/reports">
                                    <Button variant="outline" className="w-full justify-start bg-transparent">
                                        <TrendingUp className="mr-2 h-4 w-4" />
                                        Berichte anzeigen
                                    </Button>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Recent Customers */}
                {recent.customers.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Neue Kunden</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                {recent.customers.map((customer) => (
                                    <div key={customer.id} className="flex items-center justify-between p-3 border rounded-lg">
                                        <div>
                                            <p className="font-medium">{customer.name}</p>
                                            <p className="text-sm text-gray-600">{customer.email}</p>
                                            <p className="text-xs text-gray-500">Erstellt: {formatDate(customer.created_at)}</p>
                                        </div>
                                        <div className="flex space-x-1">
                                            <Link href={`/customers/${customer.id}`}>
                                                <Button variant="ghost" size="sm">
                                                    <Eye className="h-4 w-4" />
                                                </Button>
                                            </Link>
                                            <Link href={`/customers/${customer.id}/edit`}>
                                                <Button variant="ghost" size="sm">
                                                    <Edit className="h-4 w-4" />
                                                </Button>
                                            </Link>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    )
}
