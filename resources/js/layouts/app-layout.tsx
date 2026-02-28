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
import { Settings, Users, Building2, HelpCircle, Calendar, LayoutTemplate, Activity } from "lucide-react"
import { Link, usePage } from "@inertiajs/react"
import { useTranslation } from "react-i18next"
import AppearanceToggleDropdown from "@/components/appearance-dropdown"
import LanguageSwitcher from "@/components/language-switcher"

interface AppLayoutProps {
    children: React.ReactNode
    breadcrumbs?: { title: string; href?: string }[]
}

export default function AppLayout({ children, breadcrumbs = [] }: AppLayoutProps) {
    const { props, url } = usePage() as any
    const user = props.auth?.user || props.user
    const { t } = useTranslation()

    if (!user) {
        return <div>Loading...</div>
    }

    const isActive = (path: string) => {
        return url.startsWith(path)
    }

    const adminNavigation = [
        {
            title: t('nav.manageUsers'),
            url: "/users",
            icon: Users,
            isActive: isActive("/users"),
        },
        {
            title: t('nav.manageCompanies'),
            url: "/companies",
            icon: Building2,
            isActive: isActive("/companies"),
            adminOnly: true,
        },
        {
            title: t('nav.systemHealth'),
            url: "/system-health",
            icon: Activity,
            isActive: isActive("/system-health"),
            adminOnly: true,
        },
    ]

    const supportNavigation = [
        {
            title: t('nav.helpSupport'),
            url: "/help",
            icon: HelpCircle,
            isActive: isActive("/help"),
        },
        {
            title: t('nav.calendar'),
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
                        <LanguageSwitcher />
                        <AppearanceToggleDropdown />
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" size="sm" className="h-8 w-8 p-0 shrink-0">
                                    <Settings className="h-4 w-4" />
                                    <span className="sr-only">{t('nav.settings')}</span>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-56">
                                <DropdownMenuLabel>{t('nav.settings')}</DropdownMenuLabel>
                                <DropdownMenuGroup>
                                    <DropdownMenuItem asChild>
                                        <Link href="/settings">
                                            <Settings className="mr-2 h-4 w-4" />
                                            {t('nav.settings')}
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem asChild>
                                        <Link href="/invoice-layouts">
                                            <LayoutTemplate className="mr-2 h-4 w-4" />
                                            {t('nav.invoiceLayouts')}
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem asChild>
                                        <Link href="/offer-layouts">
                                            <LayoutTemplate className="mr-2 h-4 w-4" />
                                            {t('nav.offerLayouts')}
                                        </Link>
                                    </DropdownMenuItem>
                                </DropdownMenuGroup>

                                {((user.permissions?.includes("manage_users") || user.permissions?.includes("manage_companies")) || user.roles?.includes("super_admin")) && (
                                    <>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuLabel>{t('nav.administration')}</DropdownMenuLabel>
                                        <DropdownMenuGroup>
                                            {adminNavigation
                                                .filter((item) => {
                                                    if (item.adminOnly && !user.permissions?.includes("manage_companies") && !user.roles?.includes("super_admin")) {
                                                        return false
                                                    }
                                                    return true
                                                })
                                                .map((item) => (
                                                    <DropdownMenuItem key={item.title} asChild>
                                                        <Link href={item.url}>
                                                            <item.icon className="mr-2 h-4 w-4" />
                                                            {item.title}
                                                        </Link>
                                                    </DropdownMenuItem>
                                                ))}
                                        </DropdownMenuGroup>
                                    </>
                                )}

                                <DropdownMenuSeparator />
                                <DropdownMenuItem asChild>
                                    <Link href="/help">
                                        <HelpCircle className="mr-2 h-4 w-4" />
                                        {t('nav.helpSupport')}
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </header>
                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">{children}</div>
            </SidebarInset>
        </SidebarProvider>
    )
}
