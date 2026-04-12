import type React from "react"
import { Link, usePage } from "@inertiajs/react"
import { FileText, LogOut, User } from "lucide-react"
import { Button } from "@/components/ui/button"
import { route } from "ziggy-js"

interface EmployeeLayoutProps {
    children: React.ReactNode
}

export default function EmployeeLayout({ children }: EmployeeLayoutProps) {
    const { props } = usePage() as any
    const user = props.auth?.user || props.user

    return (
        <div className="min-h-screen bg-muted/30">
            {/* Top navigation bar */}
            <header className="border-b bg-background shadow-sm">
                <div className="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
                    <div className="flex items-center gap-2">
                        <FileText className="h-5 w-5 text-primary" />
                        <span className="font-semibold text-lg">Mitarbeiterportal</span>
                    </div>
                    <div className="flex items-center gap-4">
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            <User className="h-4 w-4" />
                            <span>{user?.name}</span>
                        </div>
                        <Button variant="ghost" size="sm" className="gap-2" asChild>
                            <Link href={route("logout")} method="post">
                                <LogOut className="h-4 w-4" />
                                Abmelden
                            </Link>
                        </Button>
                    </div>
                </div>
            </header>

            {/* Page content */}
            <main className="mx-auto max-w-5xl px-4 py-8">
                {children}
            </main>
        </div>
    )
}
