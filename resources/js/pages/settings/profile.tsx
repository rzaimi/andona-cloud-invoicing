"use client"

import { Head, usePage } from "@inertiajs/react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem } from "@/types"
import ProfileSettingsTab from "./tabs/profile"
import PasswordSettingsTab from "./tabs/password"

interface ProfilePageProps {
    mustVerifyEmail?: boolean
    status?: string
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Einstellungen", href: "/settings" },
    { title: "Profil" },
]

export default function ProfilePage() {
    const { auth } = usePage<{ auth: { user: any } }>().props

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Profil" />

            <div className="flex flex-1 flex-col gap-6 max-w-2xl">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Profil</h1>
                    <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Verwalten Sie Ihre persönlichen Daten und Ihr Passwort.
                    </p>
                </div>

                <ProfileSettingsTab user={auth?.user} />
                <PasswordSettingsTab />
            </div>
        </AppLayout>
    )
}
