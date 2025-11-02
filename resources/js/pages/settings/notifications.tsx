"use client"

import { Head } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Label } from "@/components/ui/label"
import { Switch } from "@/components/ui/switch"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem } from "@/types"

interface NotificationsSettings {
    notify_on_invoice_created: boolean
    notify_on_invoice_sent: boolean
    notify_on_payment_received: boolean
    notify_on_offer_created: boolean
    notify_on_offer_accepted: boolean
    notify_on_offer_rejected: boolean
    email_notifications_enabled: boolean
}

interface NotificationsPageProps {
    settings: NotificationsSettings
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Einstellungen", href: "/settings" },
    { title: "Benachrichtigungen" },
]

export default function NotificationsSettings({ settings }: NotificationsPageProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Benachrichtigungen" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">Benachrichtigungen</h1>
                    <p className="text-gray-600">Konfigurieren Sie E-Mail-Benachrichtigungen</p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>E-Mail-Benachrichtigungen</CardTitle>
                        <CardDescription>Legen Sie fest, wann E-Mail-Benachrichtigungen gesendet werden sollen</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex items-center justify-between">
                            <div className="space-y-0.5">
                                <Label>E-Mail-Benachrichtigungen aktivieren</Label>
                                <p className="text-sm text-gray-600">Alle E-Mail-Benachrichtigungen ein- oder ausschalten</p>
                            </div>
                            <Switch defaultChecked={settings.email_notifications_enabled} />
                        </div>

                        <div className="border-t pt-4 space-y-4">
                            <h3 className="font-semibold">Rechnungen</h3>
                            
                            <div className="flex items-center justify-between">
                                <div className="space-y-0.5">
                                    <Label>Rechnung erstellt</Label>
                                    <p className="text-sm text-gray-600">Benachrichtigung senden, wenn eine neue Rechnung erstellt wird</p>
                                </div>
                                <Switch defaultChecked={settings.notify_on_invoice_created} />
                            </div>

                            <div className="flex items-center justify-between">
                                <div className="space-y-0.5">
                                    <Label>Rechnung versendet</Label>
                                    <p className="text-sm text-gray-600">Benachrichtigung senden, wenn eine Rechnung an den Kunden gesendet wird</p>
                                </div>
                                <Switch defaultChecked={settings.notify_on_invoice_sent} />
                            </div>

                            <div className="flex items-center justify-between">
                                <div className="space-y-0.5">
                                    <Label>Zahlung erhalten</Label>
                                    <p className="text-sm text-gray-600">Benachrichtigung senden, wenn eine Zahlung eingeht</p>
                                </div>
                                <Switch defaultChecked={settings.notify_on_payment_received} />
                            </div>
                        </div>

                        <div className="border-t pt-4 space-y-4">
                            <h3 className="font-semibold">Angebote</h3>
                            
                            <div className="flex items-center justify-between">
                                <div className="space-y-0.5">
                                    <Label>Angebot erstellt</Label>
                                    <p className="text-sm text-gray-600">Benachrichtigung senden, wenn ein neues Angebot erstellt wird</p>
                                </div>
                                <Switch defaultChecked={settings.notify_on_offer_created} />
                            </div>

                            <div className="flex items-center justify-between">
                                <div className="space-y-0.5">
                                    <Label>Angebot angenommen</Label>
                                    <p className="text-sm text-gray-600">Benachrichtigung senden, wenn ein Angebot angenommen wird</p>
                                </div>
                                <Switch defaultChecked={settings.notify_on_offer_accepted} />
                            </div>

                            <div className="flex items-center justify-between">
                                <div className="space-y-0.5">
                                    <Label>Angebot abgelehnt</Label>
                                    <p className="text-sm text-gray-600">Benachrichtigung senden, wenn ein Angebot abgelehnt wird</p>
                                </div>
                                <Switch defaultChecked={settings.notify_on_offer_rejected} />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    )
}

