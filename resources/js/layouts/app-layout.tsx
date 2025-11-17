"use client"

import type React from "react"

import { AppSidebar } from "@/components/app-sidebar"
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from "@/components/ui/breadcrumb"
import { Separator } from "@/components/ui/separator"
import { SidebarInset, SidebarProvider, SidebarTrigger } from "@/components/ui/sidebar"
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
    DropdownMenuLabel,
    DropdownMenuGroup,
} from "@/components/ui/dropdown-menu"
import { Button } from "@/components/ui/button"
import { Settings, Users, Building2, Mail, Bell, FileCheck, LayoutTemplate, CreditCard, HelpCircle, Calendar, Download, FileText } from "lucide-react"
import { Link, usePage } from "@inertiajs/react"
import AppearanceToggleDropdown from "@/components/appearance-dropdown"

interface AppLayoutProps {
    children: React.ReactNode
    breadcrumbs?: { title: string; href?: string }[]
}

export default function AppLayout({ children, breadcrumbs = [] }: AppLayoutProps) {
    const { props, url } = usePage()
    const user = props.auth?.user || props.user

    if (!user) {
        return <div>Loading...</div>
    }

    const isActive = (path: string) => {
        return url.startsWith(path)
    }

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
        {
            title: "Import & Export",
            url: "/settings/import-export",
            icon: Download,
            isActive: isActive("/settings/import-export"),
        },
        {
            title: "Dokumente",
            url: "/settings/documents",
            icon: FileText,
            isActive: isActive("/settings/documents"),
        },
    ]

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
        <SidebarProvider>
            <AppSidebar user={user} />
            <SidebarInset>
                <header className="flex h-16 shrink-0 items-center gap-2 transition-[width,height] ease-linear group-has-[[data-collapsible=icon]]/sidebar-wrapper:h-12">
                    <div className="flex items-center gap-2 px-4 flex-1">
                        <SidebarTrigger className="-ml-1" />
                        <Separator orientation="vertical" className="mr-2 h-4" />
                        {breadcrumbs.length > 0 && (
                            <Breadcrumb>
                                <BreadcrumbList>
                                    {breadcrumbs.map((item, index) => (
                                        <div key={index} className="flex items-center">
                                            {index > 0 && <BreadcrumbSeparator className="hidden md:block" />}
                                            <BreadcrumbItem className="hidden md:block">
                                                {item.href ? (
                                                    <BreadcrumbLink href={item.href}>{item.title}</BreadcrumbLink>
                                                ) : (
                                                    <BreadcrumbPage>{item.title}</BreadcrumbPage>
                                                )}
                                            </BreadcrumbItem>
                                        </div>
                                    ))}
                                </BreadcrumbList>
                            </Breadcrumb>
                        )}
                    </div>
                    <div className="flex items-center gap-2 px-4">
                        <AppearanceToggleDropdown />
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" size="sm" className="h-8 w-8 p-0 shrink-0">
                                    <Settings className="h-4 w-4" />
                                    <span className="sr-only">Einstellungen</span>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-56">
                                {/* Administration Section */}
                                {(user.permissions?.includes("manage_users")) && (
                                    <>
                                        <DropdownMenuLabel>Administration</DropdownMenuLabel>
                                        <DropdownMenuGroup>
                                            {adminNavigation
                                                .filter((item) => !item.adminOnly || user.permissions?.includes("manage_companies"))
                                                .map((item) => (
                                                    <DropdownMenuItem key={item.title} asChild>
                                                        <Link href={item.url}>
                                                            <item.icon className="mr-2 h-4 w-4" />
                                                            {item.title}
                                                        </Link>
                                                    </DropdownMenuItem>
                                                ))}
                                        </DropdownMenuGroup>
                                        <DropdownMenuSeparator />
                                    </>
                                )}

                                {/* Settings Section */}
                                <DropdownMenuLabel>Einstellungen</DropdownMenuLabel>
                                <DropdownMenuGroup>
                                    {settingsNavigation.map((item) => (
                                        <DropdownMenuItem key={item.title} asChild>
                                            <Link href={item.url}>
                                                <item.icon className="mr-2 h-4 w-4" />
                                                {item.title}
                                            </Link>
                                        </DropdownMenuItem>
                                    ))}
                                </DropdownMenuGroup>

                                <DropdownMenuSeparator />

                                {/* Support Section */}
                                <DropdownMenuLabel>Support</DropdownMenuLabel>
                                <DropdownMenuGroup>
                                    {supportNavigation.map((item) => (
                                        <DropdownMenuItem key={item.title} asChild>
                                            <Link href={item.url}>
                                                <item.icon className="mr-2 h-4 w-4" />
                                                {item.title}
                                            </Link>
                                        </DropdownMenuItem>
                                    ))}
                                </DropdownMenuGroup>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </header>
                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">{children}</div>
            </SidebarInset>
        </SidebarProvider>
    )
}
