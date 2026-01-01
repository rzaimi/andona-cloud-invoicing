"use client"

import type * as React from "react"
import { Link } from "@inertiajs/react"
import {
    Building2,
    Users,
    FileText,
    EuroIcon,
    LayoutTemplate,
    Bell,
    BarChart3,
    Calendar,
    CreditCard,
    HelpCircle,
    LogOut,
    ChevronUp,
    ChevronDown,
    Plus,
    Home,
    Euro,
    AlertTriangle,
    Package,
    Tag,
    Warehouse,
    Mail,
    FileCheck,
    Download,
    Folder,
    ReceiptText,
    ReceiptEuro,
} from "lucide-react"
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarRail,
    SidebarSeparator,
} from "@/components/ui/sidebar"
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
    DropdownMenuLabel,
    DropdownMenuGroup,
} from "@/components/ui/dropdown-menu"
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from "@/components/ui/collapsible"
import { Badge } from "@/components/ui/badge"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Button } from "@/components/ui/button"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { router, usePage } from "@inertiajs/react"
import { useState, useEffect } from "react"
import { route } from "ziggy-js"

interface User {
    id: string
    name: string
    email: string
    role: string
    company: {
        id: string
        name: string
        logo?: string | null
    } | null
    roles?: string[]
    permissions?: string[]
}

interface Stats {
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

interface Company {
    id: string
    name: string
}

interface AppSidebarProps extends React.ComponentProps<typeof Sidebar> {
    user: User
    stats?: Stats
    available_companies?: Company[]
}

export function AppSidebar({ user, stats, ...props }: AppSidebarProps) {
    const { url, props: pageProps } = usePage<{ auth?: { available_companies?: Company[] } }>()
    const availableCompanies = pageProps.auth?.available_companies || []
    const canSwitchCompany = availableCompanies.length > 0 && user.permissions?.includes("manage_companies")
    const [selectedCompanyId, setSelectedCompanyId] = useState<string>(user.company?.id || "")

    useEffect(() => {
        // Initialize selected company ID from user's company
        // Ensure we always have a valid company selected
        if (user.company?.id) {
            setSelectedCompanyId(user.company.id)
        } else if (availableCompanies.length > 0 && canSwitchCompany) {
            // If no company from user, but we have available companies and can switch, select first one
            setSelectedCompanyId(availableCompanies[0].id)
        }
    }, [user.company?.id, availableCompanies, canSwitchCompany])

    const isActive = (path: string) => {
        return url.startsWith(path)
    }

    const mainNavigation = [
        {
            title: "Dashboard",
            url: "/dashboard",
            icon: Home,
            isActive: isActive("/dashboard"),
        },
    ]

    // Compact: Main items only, rest in dropdowns
    const quickActions = [
        {
            title: "Neue Rechnung",
            url: "/invoices/create",
            icon: Plus,
            isActive: isActive("/invoices/create"),
        },
        {
            title: "Neues Angebot",
            url: "/offers/create",
            icon: Plus,
            isActive: isActive("/offers/create"),
        },
        {
            title: "Neuer Kunde",
            url: "/customers/create",
            icon: Plus,
            isActive: isActive("/customers/create"),
        },
        {
            title: "Neue Ausgabe",
            url: "/expenses/create",
            icon: Plus,
            isActive: isActive("/expenses/create"),
        },
        {
            title: "Neues Produkt",
            url: "/products/create",
            icon: Plus,
            isActive: isActive("/products/create"),
        },
    ]

    const invoiceManagement = [
        {
            title: "Alle Rechnungen",
            url: "/invoices",
            icon: ReceiptText,
            isActive: isActive("/invoices"),
            badge: stats?.invoices?.draft && stats.invoices.draft > 0 ? stats.invoices.draft : null,
        },
        {
            title: "Neue Rechnung",
            url: "/invoices/create",
            icon: Plus,
            isActive: isActive("/invoices/create"),
        },
        {
            title: "Überfällig",
            url: "/invoices?status=overdue",
            icon: AlertTriangle,
            isActive: url === "/invoices?status=overdue",
            badge: stats?.invoices?.overdue && stats.invoices.overdue > 0 ? stats.invoices.overdue : null,
            badgeVariant: "destructive" as const,
        },
    ]

    const offerManagement = [
        {
            title: "Alle Angebote",
            url: "/offers",
            icon: FileText,
            isActive: isActive("/offers"),
            badge: stats?.offers?.draft && stats.offers.draft > 0 ? stats.offers.draft : null,
        },
        {
            title: "Neues Angebot",
            url: "/offers/create",
            icon: Plus,
            isActive: isActive("/offers/create"),
        },
    ]

    const customerManagement = [
        {
            title: "Alle Kunden",
            url: "/customers",
            icon: Users,
            isActive: isActive("/customers"),
        },
        {
            title: "Neuer Kunde",
            url: "/customers/create",
            icon: Plus,
            isActive: isActive("/customers/create"),
        },
    ]

    const paymentManagement = [
        {
            title: "Alle Zahlungen",
            url: "/payments",
            icon: CreditCard,
            isActive: isActive("/payments"),
        },
        {
            title: "Neue Zahlung",
            url: "/payments/create",
            icon: Plus,
            isActive: isActive("/payments/create"),
        },
    ]

    const expenseManagement = [
        {
            title: "Alle Ausgaben",
            url: "/expenses",
            icon: ReceiptEuro,
            isActive: isActive("/expenses"),
        },
        {
            title: "Kategorien",
            url: "/expenses/categories",
            icon: Folder,
            isActive: isActive("/expenses/categories"),
        },
    ]

    const productManagement = [
        {
            title: "Alle Produkte",
            url: "/products",
            icon: Package,
            isActive: isActive("/products"),
            badge: stats?.products?.low_stock && stats.products.low_stock > 0 ? stats.products.low_stock : null,
            badgeVariant: "destructive" as const,
        },
        {
            title: "Kategorien",
            url: "/categories",
            icon: Tag,
            isActive: isActive("/categories"),
        },
        {
            title: "Lagerbestand",
            url: "/warehouses",
            icon: Warehouse,
            isActive: isActive("/warehouses"),
            badge: stats?.products?.out_of_stock && stats.products.out_of_stock > 0 ? stats.products.out_of_stock : null,
            badgeVariant: "destructive" as const,
        },
    ]

    const documentManagement = [
        {
            title: "Dokumente",
            url: "/settings/documents",
            icon: HelpCircle,
            isActive: isActive("/settings/documents"),
        },
    ]

    const reportsAndAnalytics = [
        {
            title: "Übersicht",
            url: "/reports",
            icon: BarChart3,
            isActive: isActive("/reports"),
        },
        {
            title: "Umsatzberichte",
            url: "/reports/revenue",
            icon: Euro,
            isActive: isActive("/reports/revenue"),
        },
        {
            title: "Ausgabenberichte",
            url: "/reports/expenses",
            icon: ReceiptEuro,
            isActive: isActive("/reports/expenses"),
        },
        {
            title: "Gewinn & Verlust",
            url: "/reports/profit",
            icon: BarChart3,
            isActive: isActive("/reports/profit"),
        },
        {
            title: "MwSt.-Bericht",
            url: "/reports/vat",
            icon: FileText,
            isActive: isActive("/reports/vat"),
        },
        {
            title: "Kundenberichte",
            url: "/reports/customers",
            icon: Users,
            isActive: isActive("/reports/customers"),
        },
        {
            title: "Steuerberichte",
            url: "/reports/tax",
            icon: FileText,
            isActive: isActive("/reports/tax"),
        },
    ]

    // Moved to dropdown
    const adminNavigation = [
        {
            title: "Benutzer verwalten",
            url: "/users",
            icon: Users,
            isActive: isActive("/users"),
        },
        {
            title: "Firmen verwalten",
            url: "/companies",
            icon: Building2,
            isActive: isActive("/companies"),
            adminOnly: true,
        },
    ]

    // Moved to dropdown
    const supportNavigation = [
        {
            title: "Hilfe & Support",
            url: "/help",
            icon: HelpCircle,
            isActive: isActive("/help"),
        },
        {
            title: "Kalender",
            url: "/calendar",
            icon: Calendar,
            isActive: isActive("/calendar"),
        },
    ]

    return (
        <Sidebar variant="inset" {...props}>
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <div className="flex items-center justify-between w-full">
                            <SidebarMenuButton size="lg" asChild className="flex-1">
                                <Link href="/dashboard">
                                    {user.company?.logo ? (
                                        <div className="flex aspect-square size-8 items-center justify-center rounded-lg overflow-hidden">
                                            <img 
                                                src={`/storage/${user.company.logo}`} 
                                                alt={user.company.name}
                                                className="w-full h-full object-contain"
                                                onError={(e) => {
                                                    // Hide image if it fails to load (404/403)
                                                    const target = e.target as HTMLImageElement
                                                    target.style.display = 'none'
                                                }}
                                            />
                                        </div>
                                    ) : (
                                        <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                                            <EuroIcon className="size-4" />
                                        </div>
                                    )}
                                    <div className="grid flex-1 text-left text-sm leading-tight min-w-0">
                                        <span className="truncate font-semibold">AndoBill</span>
                                        {canSwitchCompany ? (
                                            <Select
                                                value={selectedCompanyId}
                                                onValueChange={(value) => {
                                                    setSelectedCompanyId(value)
                                                    router.post(
                                                        route("company-context.switch"),
                                                        { company_id: value },
                                                        {
                                                            preserveState: false,
                                                            preserveScroll: false,
                                                            onSuccess: () => {
                                                                // Will redirect to dashboard and fully reload the application
                                                            },
                                                            onError: (errors) => {
                                                                console.error('Company switch error:', errors)
                                                            }
                                                        }
                                                    )
                                                }}
                                            >
                                                <SelectTrigger className="h-auto p-0 border-0 bg-transparent text-xs text-muted-foreground focus:ring-0 hover:text-foreground cursor-pointer">
                                                    <SelectValue placeholder="Firma auswählen">
                                                        {availableCompanies.find((c) => c.id === selectedCompanyId)?.name || user.company?.name || "Keine Firma"}
                                                    </SelectValue>
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {availableCompanies.map((company) => (
                                                        <SelectItem key={company.id} value={company.id}>
                                                            {company.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        ) : (
                                            <span className="truncate text-xs">{user.company?.name || "Keine Firma"}</span>
                                        )}
                                    </div>
                                </Link>
                            </SidebarMenuButton>
                        </div>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="overflow-x-hidden">
                {/* Main Navigation */}
                <SidebarGroup>
                    <SidebarGroupContent>
                        <SidebarMenu>
                            {mainNavigation.map((item) => (
                                <SidebarMenuItem key={item.title}>
                                    <SidebarMenuButton asChild isActive={item.isActive}>
                                        <Link href={item.url}>
                                            <item.icon />
                                            <span className="truncate">{item.title}</span>
                                        </Link>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            ))}
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>

                <SidebarSeparator />

                {/* Quick Actions - Most Used */}
                <SidebarGroup>
                    <SidebarGroupLabel>Schnellzugriff</SidebarGroupLabel>
                    <SidebarGroupContent>
                        <SidebarMenu>
                            <SidebarMenuItem>
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <SidebarMenuButton>
                                            <Plus />
                                            <span>Neu erstellen</span>
                                            <ChevronDown className="ml-auto" />
                                        </SidebarMenuButton>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent side="right" align="start" className="w-48">
                                        <DropdownMenuLabel>Erstellen</DropdownMenuLabel>
                                        <DropdownMenuSeparator />
                                        {quickActions.map((item) => (
                                            <DropdownMenuItem key={item.title} asChild>
                                                <Link href={item.url}>
                                                    <item.icon className="mr-2 h-4 w-4" />
                                                    {item.title}
                                                </Link>
                                            </DropdownMenuItem>
                                        ))}
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </SidebarMenuItem>
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>

                {/* Core Modules - Always Visible */}
                <SidebarGroup>
                    <SidebarGroupContent>
                        <SidebarMenu>
                            <SidebarMenuItem>
                                <SidebarMenuButton asChild isActive={isActive("/invoices")}>
                                    <Link href="/invoices" className="flex items-center min-w-0">
                                        <ReceiptText className="shrink-0" />
                                        <span className="truncate">Rechnungen</span>
                                        {stats?.invoices?.draft && stats.invoices.draft > 0 && (
                                            <Badge variant="secondary" className="ml-auto h-5 w-5 shrink-0 items-center justify-center rounded-full p-0 text-xs">
                                                {stats.invoices.draft}
                                            </Badge>
                                        )}
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                            <SidebarMenuItem>
                                <SidebarMenuButton asChild isActive={isActive("/offers")}>
                                    <Link href="/offers" className="flex items-center min-w-0">
                                        <FileText className="shrink-0" />
                                        <span className="truncate">Angebote</span>
                                        {stats?.offers?.draft && stats.offers.draft > 0 && (
                                            <Badge variant="secondary" className="ml-auto h-5 w-5 shrink-0 items-center justify-center rounded-full p-0 text-xs">
                                                {stats.offers.draft}
                                            </Badge>
                                        )}
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                            <SidebarMenuItem>
                                <SidebarMenuButton asChild isActive={isActive("/customers")}>
                                    <Link href="/customers">
                                        <Users />
                                        <span className="truncate">Kunden</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                            <SidebarMenuItem>
                                <SidebarMenuButton asChild isActive={isActive("/payments")}>
                                    <Link href="/payments">
                                        <CreditCard />
                                        <span className="truncate">Zahlungen</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                            <SidebarMenuItem>
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <SidebarMenuButton isActive={isActive("/expenses")}>
                                            <ReceiptEuro />
                                            <span>Ausgaben</span>
                                            <ChevronDown className="ml-auto" />
                                        </SidebarMenuButton>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent side="right" align="start" className="w-56">
                                        <DropdownMenuLabel>Ausgaben</DropdownMenuLabel>
                                        <DropdownMenuSeparator />
                                        {expenseManagement.map((item) => (
                                            <DropdownMenuItem key={item.title} asChild>
                                                <Link href={item.url}>
                                                    <item.icon className="mr-2 h-4 w-4" />
                                                    {item.title}
                                                </Link>
                                            </DropdownMenuItem>
                                        ))}
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </SidebarMenuItem>
                            <SidebarMenuItem>
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <SidebarMenuButton isActive={isActive("/products")} className="flex items-center min-w-0">
                                            <Package className="shrink-0" />
                                            <span className="truncate">Produkte</span>
                                            {stats?.products?.low_stock && stats.products.low_stock > 0 && (
                                                <Badge variant="destructive" className="ml-auto h-5 w-5 shrink-0 items-center justify-center rounded-full p-0 text-xs">
                                                    {stats.products.low_stock}
                                                </Badge>
                                            )}
                                            <ChevronDown className="ml-auto" />
                                        </SidebarMenuButton>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent side="right" align="start" className="w-56">
                                        <DropdownMenuLabel>Produkte</DropdownMenuLabel>
                                        <DropdownMenuSeparator />
                                        {productManagement.map((item) => (
                                            <DropdownMenuItem key={item.title} asChild>
                                                <Link href={item.url} className="flex items-center">
                                                    <item.icon className="mr-2 h-4 w-4" />
                                                    {item.title}
                                                    {item.badge && (
                                                        <Badge variant={item.badgeVariant || "default"} className="ml-auto h-5 w-5 shrink-0 items-center justify-center rounded-full p-0 text-xs">
                                                            {item.badge}
                                                        </Badge>
                                                    )}
                                                </Link>
                                            </DropdownMenuItem>
                                        ))}
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </SidebarMenuItem>
                            <SidebarMenuItem>
                                <SidebarMenuButton asChild isActive={isActive("/calendar")}>
                                    <Link href="/calendar">
                                        <Calendar />
                                        <span className="truncate">Kalender</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                            <SidebarMenuItem>
                                <SidebarMenuButton asChild isActive={isActive("/settings/documents")}>
                                    <Link href="/settings/documents">
                                        <Folder />
                                        <span className="truncate">Dokumente</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>

                {/* Reports - Dropdown */}
                <SidebarGroup>
                    <SidebarGroupContent>
                        <SidebarMenu>
                            <SidebarMenuItem>
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <SidebarMenuButton>
                                            <BarChart3 />
                                            <span>Berichte</span>
                                            <ChevronDown className="ml-auto" />
                                        </SidebarMenuButton>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent side="right" align="start" className="w-56">
                                        <DropdownMenuLabel>Berichte & Analysen</DropdownMenuLabel>
                                        <DropdownMenuSeparator />
                                        {reportsAndAnalytics.map((item) => (
                                            <DropdownMenuItem key={item.title} asChild>
                                                <Link href={item.url}>
                                                    <item.icon className="mr-2 h-4 w-4" />
                                                    {item.title}
                                                </Link>
                                            </DropdownMenuItem>
                                        ))}
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </SidebarMenuItem>
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>
            </SidebarContent>

            <SidebarFooter>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <SidebarMenuButton
                                    size="lg"
                                    className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                                >
                                    <Avatar className="h-8 w-8 rounded-lg">
                                        <AvatarFallback className="rounded-lg">
                                            {user.name
                                                .split(" ")
                                                .map((n) => n[0])
                                                .join("")
                                                .toUpperCase()}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div className="grid flex-1 text-left text-sm leading-tight min-w-0">
                                        <span className="truncate font-semibold">{user.name}</span>
                                        <span className="truncate text-xs">{user.email}</span>
                                    </div>
                                    <ChevronUp className="ml-auto size-4 shrink-0" />
                                </SidebarMenuButton>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent
                                className="w-[--radix-dropdown-menu-trigger-width] min-w-56 rounded-lg"
                                side="bottom"
                                align="end"
                                sideOffset={4}
                            >
                                <DropdownMenuItem asChild>
                                    <Link href="/settings/profile">
                                        <Users className="mr-2 h-4 w-4" />
                                        Profil bearbeiten
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                    <Link href="/help">
                                        <HelpCircle className="mr-2 h-4 w-4" />
                                        Hilfe
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem asChild>
                                    <Link href="/logout" method="post" as="button" className="w-full">
                                        <LogOut className="mr-2 h-4 w-4" />
                                        Abmelden
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarFooter>
            <SidebarRail />
        </Sidebar>
    )
}
