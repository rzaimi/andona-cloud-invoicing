"use client"

import { useForm } from "@inertiajs/react"
import { useTranslation } from "react-i18next"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Label } from "@/components/ui/label"
import { Switch } from "@/components/ui/switch"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { CheckCircle2 } from "lucide-react"
import { route } from "ziggy-js"

interface NotificationsSettingsTabProps {
    notificationSettings: any
}

export default function NotificationsSettingsTab({
    notificationSettings }: NotificationsSettingsTabProps) {
    const { t } = useTranslation()
    const { data, setData, post, processing, errors, recentlySuccessful } = useForm({
        notify_on_invoice_created: notificationSettings?.notify_on_invoice_created ?? false,
        notify_on_invoice_sent: notificationSettings?.notify_on_invoice_sent ?? true,
        notify_on_payment_received: notificationSettings?.notify_on_payment_received ?? true,
        notify_on_offer_created: notificationSettings?.notify_on_offer_created ?? false,
        notify_on_offer_accepted: notificationSettings?.notify_on_offer_accepted ?? true,
        notify_on_offer_rejected: notificationSettings?.notify_on_offer_rejected ?? false,
        email_notifications_enabled: notificationSettings?.email_notifications_enabled ?? true,
    })

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        post(route("settings.notifications.update"))
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            {recentlySuccessful && (
                <Alert className="border-green-500 bg-green-50">
                    <CheckCircle2 className="h-4 w-4 text-green-600" />
                    <AlertDescription className="text-green-600">
                        {t('pages.settings.saved')}
                    </AlertDescription>
                </Alert>
            )}

            <Card>
                <CardHeader>
                    <CardTitle>{t('settings.emailNotifications')}</CardTitle>
                    <CardDescription>
                        {t('settings.emailNotificationsDesc')}
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                    <div className="flex items-center justify-between">
                        <div className="space-y-0.5">
                            <Label htmlFor="email_notifications_enabled">{t('settings.enableEmailNotifications')}</Label>
                            <p className="text-sm text-muted-foreground">
                                {t('settings.enableEmailNotificationsDesc')}
                            </p>
                        </div>
                        <Switch
                            id="email_notifications_enabled"
                            checked={data.email_notifications_enabled}
                            onCheckedChange={(checked) => setData("email_notifications_enabled", checked)}
                        />
                    </div>

                    <div className="flex items-center justify-between">
                        <div className="space-y-0.5">
                            <Label htmlFor="notify_on_invoice_created">{t('settings.notifyOnInvoiceCreate')}</Label>
                            <p className="text-sm text-muted-foreground">
                                {t('settings.notifyOnInvoiceCreateDesc')}
                            </p>
                        </div>
                        <Switch
                            id="notify_on_invoice_created"
                            checked={data.notify_on_invoice_created}
                            onCheckedChange={(checked) => setData("notify_on_invoice_created", checked)}
                        />
                    </div>

                    <div className="flex items-center justify-between">
                        <div className="space-y-0.5">
                            <Label htmlFor="notify_on_invoice_sent">{t('settings.notifyOnInvoiceSend')}</Label>
                            <p className="text-sm text-muted-foreground">
                                {t('settings.notifyOnInvoiceSendDesc')}
                            </p>
                        </div>
                        <Switch
                            id="notify_on_invoice_sent"
                            checked={data.notify_on_invoice_sent}
                            onCheckedChange={(checked) => setData("notify_on_invoice_sent", checked)}
                        />
                    </div>

                    <div className="flex items-center justify-between">
                        <div className="space-y-0.5">
                            <Label htmlFor="notify_on_payment_received">{t('settings.notifyOnPayment')}</Label>
                            <p className="text-sm text-muted-foreground">
                                Benachrichtigung wenn eine Zahlung eingegangen ist
                            </p>
                        </div>
                        <Switch
                            id="notify_on_payment_received"
                            checked={data.notify_on_payment_received}
                            onCheckedChange={(checked) => setData("notify_on_payment_received", checked)}
                        />
                    </div>

                    <div className="flex items-center justify-between">
                        <div className="space-y-0.5">
                            <Label htmlFor="notify_on_offer_created">{t('settings.notifyOnOfferCreate')}</Label>
                            <p className="text-sm text-muted-foreground">
                                {t('settings.notifyOnOfferCreateDesc')}
                            </p>
                        </div>
                        <Switch
                            id="notify_on_offer_created"
                            checked={data.notify_on_offer_created}
                            onCheckedChange={(checked) => setData("notify_on_offer_created", checked)}
                        />
                    </div>

                    <div className="flex items-center justify-between">
                        <div className="space-y-0.5">
                            <Label htmlFor="notify_on_offer_accepted">{t('settings.notifyOnOfferAccept')}</Label>
                            <p className="text-sm text-muted-foreground">
                                {t('settings.notifyOnOfferAcceptDesc')}
                            </p>
                        </div>
                        <Switch
                            id="notify_on_offer_accepted"
                            checked={data.notify_on_offer_accepted}
                            onCheckedChange={(checked) => setData("notify_on_offer_accepted", checked)}
                        />
                    </div>

                    <div className="flex items-center justify-between">
                        <div className="space-y-0.5">
                            <Label htmlFor="notify_on_offer_rejected">{t('settings.notifyOnOfferReject')}</Label>
                            <p className="text-sm text-muted-foreground">
                                {t('settings.notifyOnOfferRejectDesc')}
                            </p>
                        </div>
                        <Switch
                            id="notify_on_offer_rejected"
                            checked={data.notify_on_offer_rejected}
                            onCheckedChange={(checked) => setData("notify_on_offer_rejected", checked)}
                        />
                    </div>
                </CardContent>
            </Card>

            <div className="flex justify-end">
                <Button type="submit" disabled={processing}>
                    {processing ? "Speichert..." : "Einstellungen speichern"}
                </Button>
            </div>
        </form>
    )
}



