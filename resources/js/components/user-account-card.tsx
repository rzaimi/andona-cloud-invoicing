"use client"

import { Link } from "@inertiajs/react"
import { LogOut, UserRound } from "lucide-react"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Button } from "@/components/ui/button"
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from "@/components/ui/dropdown-menu"
import { useInitials } from "@/hooks/use-initials"
import type { User } from "@/types"

export function UserAccountCard({ user }: { user: User }) {
    const initials = useInitials()(user.name)

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="h-9 w-9 rounded-full"
                    aria-label="Konto"
                >
                    <Avatar className="h-8 w-8 ring-2 ring-background">
                        <AvatarImage src={user.avatar} alt={user.name} />
                        <AvatarFallback className="bg-muted text-xs font-medium">
                            {initials}
                        </AvatarFallback>
                    </Avatar>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" sideOffset={8} className="w-72 overflow-hidden p-0">
                <div className="bg-muted/60 px-4 pb-4 pt-5">
                    <Avatar className="h-11 w-11">
                        <AvatarImage src={user.avatar} alt={user.name} />
                        <AvatarFallback className="bg-background text-sm font-medium">
                            {initials}
                        </AvatarFallback>
                    </Avatar>
                    <p className="mt-3 truncate text-sm font-semibold">{user.name}</p>
                    <p className="truncate text-xs text-muted-foreground">{user.email}</p>
                    {user.company?.name && (
                        <p className="mt-1 truncate text-xs text-muted-foreground">{user.company.name}</p>
                    )}
                </div>
                <div className="grid grid-cols-2 gap-2 p-3">
                    <Button variant="secondary" size="sm" className="justify-center" asChild>
                        <Link href="/settings/profile">
                            <UserRound className="mr-1.5 h-4 w-4" />
                            Profil
                        </Link>
                    </Button>
                    <Button variant="outline" size="sm" className="justify-center" asChild>
                        <Link href="/logout" method="post" as="button">
                            <LogOut className="mr-1.5 h-4 w-4" />
                            Abmelden
                        </Link>
                    </Button>
                </div>
            </DropdownMenuContent>
        </DropdownMenu>
    )
}
