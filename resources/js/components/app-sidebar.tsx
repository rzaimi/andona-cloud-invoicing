"use client"

import type * as React from "react"
import { Link } from "@inertiajs/react"
import {
    Building2,
    Users,
    FileText,
    Receipt,
    Settings,
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

    const productManagement = [
        {
            title: "Alle Produkte",
            url: "/products",
            icon: Package,
            isActive: isActive("/products"),
            badge: stats?.products?.low_stock > 0 ? stats.products.low_stock : null,
            badgeVariant: "destructive" as const,
        },
        {
            title: "Neues Produkt",
            url: "/products/create",
            icon: Plus,
            isActive: isActive("/products/create"),
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
            badge: stats?.products?.out_of_stock > 0 ? stats.products.out_of_stock : null,
            badgeVariant: "destructive" as const,
        },
    ]

    const invoiceManagement = [
        {
            title: "Alle Rechnungen",
            url: "/invoices",
            icon: Receipt,
            isActive: isActive("/invoices"),
            badge: stats?.invoices?.draft > 0 ? stats.invoices.draft : null,
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
            badge: stats?.invoices?.overdue > 0 ? stats.invoices.overdue : null,
            badgeVariant: "destructive" as const,
        },
    ]

    const offerManagement = [
        {
            title: "Alle Angebote",
            url: "/offers",
            icon: FileText,
            isActive: isActive("/offers"),
            badge: stats?.offers?.draft > 0 ? stats.offers.draft : null,
        },
        {
            title: "Neues Angebot",
            url: "/offers/create",
            icon: Plus,
            isActive: isActive("/offers/create"),
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
    const settingsNavigation = [
        {
            title: "Firmeneinstellungen",
            url: "/settings",
            icon: Settings,
            isActive: isActive("/settings"),
        },
        {
            title: "E-Mail Einstellungen",
            url: "/settings/email",
            icon: Mail,
            isActive: isActive("/settings/email"),
        },
        {
            title: "Mahnungseinstellungen",
            url: "/settings/reminders",
            icon: Bell,
            isActive: isActive("/settings/reminders"),
        },
        {
            title: "E-Rechnung",
            url: "/settings/erechnung",
            icon: FileCheck,
            isActive: isActive("/settings/erechnung"),
        },
        {
            title: "E-Mail-Verlauf",
            url: "/settings/email-logs",
            icon: Mail,
            isActive: isActive("/settings/email-logs"),
        },
        {
            title: "Rechnungslayouts",
            url: "/invoice-layouts",
            icon: LayoutTemplate,
            isActive: isActive("/settings/invoice-layouts"),
        },
        {
            title: "Angebotslayouts",
            url: "/offer-layouts",
            icon: LayoutTemplate,
            isActive: isActive("/offer-layouts"),
        },
        {
            title: "Benachrichtigungen",
            url: "/settings/notifications",
            icon: Bell,
            isActive: isActive("/settings/notifications"),
        },
        {
            title: "Zahlungsmethoden",
            url: "/settings/payment-methods",
            icon: CreditCard,
            isActive: isActive("/settings/payment-methods"),
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
                                            <Receipt className="size-4" />
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

                {/* Customer Management */}
                <SidebarGroup>
                    <SidebarGroupLabel>Kundenverwaltung</SidebarGroupLabel>
                    <SidebarGroupContent>
                        <SidebarMenu>
                            {customerManagement.map((item) => (
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

                {/* Product Management */}
                <Collapsible defaultOpen className="group/collapsible">
                    <SidebarGroup>
                        <SidebarGroupLabel asChild>
                            <CollapsibleTrigger>
                                Produktverwaltung
                                <ChevronDown className="ml-auto transition-transform group-data-[state=open]/collapsible:rotate-180" />
                            </CollapsibleTrigger>
                        </SidebarGroupLabel>
                        <CollapsibleContent>
                            <SidebarGroupContent>
                                <SidebarMenu>
                                    {productManagement.map((item) => (
                                        <SidebarMenuItem key={item.title}>
                                            <SidebarMenuButton asChild isActive={item.isActive}>
                                                <Link href={item.url} className="flex items-center min-w-0">
                                                    <item.icon className="shrink-0" />
                                                    <span className="truncate">{item.title}</span>
                                                    {item.badge && (
                                                        <Badge
                                                            variant={item.badgeVariant || "secondary"}
                                                            className="ml-auto h-5 w-5 shrink-0 items-center justify-center rounded-full p-0 text-xs"
                                                        >
                                                            {item.badge}
                                                        </Badge>
                                                    )}
                                                </Link>
                                            </SidebarMenuButton>
                                        </SidebarMenuItem>
                                    ))}
                                </SidebarMenu>
                            </SidebarGroupContent>
                        </CollapsibleContent>
                    </SidebarGroup>
                </Collapsible>

                {/* Invoice Management */}
                <Collapsible defaultOpen className="group/collapsible">
                    <SidebarGroup>
                        <SidebarGroupLabel asChild>
                            <CollapsibleTrigger>
                                Rechnungen
                                <ChevronDown className="ml-auto transition-transform group-data-[state=open]/collapsible:rotate-180" />
                            </CollapsibleTrigger>
                        </SidebarGroupLabel>
                        <CollapsibleContent>
                            <SidebarGroupContent>
                                <SidebarMenu>
                                    {invoiceManagement.map((item) => (
                                        <SidebarMenuItem key={item.title}>
                                            <SidebarMenuButton asChild isActive={item.isActive}>
                                                <Link href={item.url} className="flex items-center min-w-0">
                                                    <item.icon className="shrink-0" />
                                                    <span className="truncate">{item.title}</span>
                                                    {item.badge && (
                                                        <Badge
                                                            variant={item.badgeVariant || "secondary"}
                                                            className="ml-auto h-5 w-5 shrink-0 items-center justify-center rounded-full p-0 text-xs"
                                                        >
                                                            {item.badge}
                                                        </Badge>
                                                    )}
                                                </Link>
                                            </SidebarMenuButton>
                                        </SidebarMenuItem>
                                    ))}
                                </SidebarMenu>
                            </SidebarGroupContent>
                        </CollapsibleContent>
                    </SidebarGroup>
                </Collapsible>

                {/* Offer Management */}
                <Collapsible defaultOpen className="group/collapsible">
                    <SidebarGroup>
                        <SidebarGroupLabel asChild>
                            <CollapsibleTrigger>
                                Angebote
                                <ChevronDown className="ml-auto transition-transform group-data-[state=open]/collapsible:rotate-180" />
                            </CollapsibleTrigger>
                        </SidebarGroupLabel>
                        <CollapsibleContent>
                            <SidebarGroupContent>
                                <SidebarMenu>
                                    {offerManagement.map((item) => (
                                        <SidebarMenuItem key={item.title}>
                                            <SidebarMenuButton asChild isActive={item.isActive}>
                                                <Link href={item.url} className="flex items-center min-w-0">
                                                    <item.icon className="shrink-0" />
                                                    <span className="truncate">{item.title}</span>
                                                    {item.badge && (
                                                        <Badge
                                                            variant="secondary"
                                                            className="ml-auto h-5 w-5 shrink-0 items-center justify-center rounded-full p-0 text-xs"
                                                        >
                                                            {item.badge}
                                                        </Badge>
                                                    )}
                                                </Link>
                                            </SidebarMenuButton>
                                        </SidebarMenuItem>
                                    ))}
                                </SidebarMenu>
                            </SidebarGroupContent>
                        </CollapsibleContent>
                    </SidebarGroup>
                </Collapsible>

                {/* Reports & Analytics */}
                <Collapsible className="group/collapsible">
                    <SidebarGroup>
                        <SidebarGroupLabel asChild>
                            <CollapsibleTrigger>
                                Berichte & Analysen
                                <ChevronDown className="ml-auto transition-transform group-data-[state=open]/collapsible:rotate-180" />
                            </CollapsibleTrigger>
                        </SidebarGroupLabel>
                        <CollapsibleContent>
                            <SidebarGroupContent>
                                <SidebarMenu>
                                    {reportsAndAnalytics.map((item) => (
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
                        </CollapsibleContent>
                    </SidebarGroup>
                </Collapsible>
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
                                        <AvatarImage src="/placeholder.svg" alt={user.name} />
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
