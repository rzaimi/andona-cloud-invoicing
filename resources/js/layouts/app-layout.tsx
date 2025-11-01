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
import { usePage } from "@inertiajs/react"

interface AppLayoutProps {
    children: React.ReactNode
    breadcrumbs?: { title: string; href?: string }[]
}

export default function AppLayout({ children, breadcrumbs = [] }: AppLayoutProps) {
    const { props } = usePage()
    const user = props.auth?.user || props.user

    if (!user) {
        return <div>Loading...</div>
    }

    return (
        <SidebarProvider>
            <AppSidebar user={user} />
            <SidebarInset>
                <header className="flex h-16 shrink-0 items-center gap-2 transition-[width,height] ease-linear group-has-[[data-collapsible=icon]]/sidebar-wrapper:h-12">
                    <div className="flex items-center gap-2 px-4">
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
                </header>
                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">{children}</div>
            </SidebarInset>
        </SidebarProvider>
    )
}
