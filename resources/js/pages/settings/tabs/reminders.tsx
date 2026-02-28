"use client"

import { useState } from "react"
import { useForm } from "@inertiajs/react"
import { useTranslation } from "react-i18next"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Switch } from "@/components/ui/switch"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog"
import { Info, Eye, ExternalLink } from "lucide-react"
import { route } from "ziggy-js"

interface RemindersSettingsTabProps {
    reminderSettings: any
}

export default function RemindersSettingsTab({
    reminderSettings }: RemindersSettingsTabProps) {
    const { t } = useTranslation()
    const [previewTemplate, setPreviewTemplate] = useState<string | null>(null)
    const { data, setData, post, processing, errors } = useForm({
        reminder_friendly_days: reminderSettings?.reminder_friendly_days || 7,
        reminder_mahnung1_days: reminderSettings?.reminder_mahnung1_days || 14,
        reminder_mahnung2_days: reminderSettings?.reminder_mahnung2_days || 21,
        reminder_mahnung3_days: reminderSettings?.reminder_mahnung3_days || 30,
        reminder_inkasso_days: reminderSettings?.reminder_inkasso_days || 45,
        reminder_mahnung1_fee: reminderSettings?.reminder_mahnung1_fee || 5.00,
        reminder_mahnung2_fee: reminderSettings?.reminder_mahnung2_fee || 10.00,
        reminder_mahnung3_fee: reminderSettings?.reminder_mahnung3_fee || 15.00,
        reminder_interest_rate: reminderSettings?.reminder_interest_rate || 9.00,
        reminder_auto_send: reminderSettings?.reminder_auto_send ?? true,
    })

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        post(route("settings.reminders.update"))
    }

    const mahnungTemplates = [
        { id: 'mahnung-1', name: '1. Mahnung', route: 'settings.emails.preview.mahnung-1' },
        { id: 'mahnung-2', name: '2. Mahnung', route: 'settings.emails.preview.mahnung-2' },
        { id: 'mahnung-3', name: '3. Mahnung', route: 'settings.emails.preview.mahnung-3' },
        { id: 'friendly-reminder', name: 'Freundliche Erinnerung', route: 'settings.emails.preview.friendly-reminder' },
        { id: 'inkasso', name: 'Inkasso', route: 'settings.emails.preview.inkasso' },
    ]

    return (
        <>
        <form onSubmit={handleSubmit} className="space-y-6">
            <Alert>
                <Info className="h-4 w-4" />
                <AlertDescription>
                    {t('settings.remindersDesc')}iche Erinnerung → 1. Mahnung → 2. Mahnung → 3. Mahnung → Inkasso
                </AlertDescription>
            </Alert>

            <Card>
                <CardHeader>
                    <CardTitle>Mahnintervalle</CardTitle>
                    <CardDescription>
                        {t('settings.remindersDaysHint')}
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <div className="flex items-center justify-between mb-2">
                                <Label htmlFor="reminder_friendly_days">Freundliche Erinnerung (Tage)</Label>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => setPreviewTemplate('friendly-reminder')}
                                    className="h-7 text-xs"
                                >
                                    <Eye className="h-3 w-3 mr-1" />
                                    {t('common.preview')}
                                </Button>
                            </div>
                            <Input
                                id="reminder_friendly_days"
                                type="number"
                                min="1"
                                max="90"
                                value={data.reminder_friendly_days}
                                onChange={(e) => setData("reminder_friendly_days", parseInt(e.target.value))}
                            />
                            {errors.reminder_friendly_days && <p className="text-sm text-red-500">{errors.reminder_friendly_days}</p>}
                        </div>

                        <div>
                            <div className="flex items-center justify-between mb-2">
                                <Label htmlFor="reminder_mahnung1_days">1. Mahnung (Tage)</Label>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => setPreviewTemplate('mahnung-1')}
                                    className="h-7 text-xs"
                                >
                                    <Eye className="h-3 w-3 mr-1" />
                                    {t('common.preview')}
                                </Button>
                            </div>
                            <Input
                                id="reminder_mahnung1_days"
                                type="number"
                                min="1"
                                max="90"
                                value={data.reminder_mahnung1_days}
                                onChange={(e) => setData("reminder_mahnung1_days", parseInt(e.target.value))}
                            />
                            {errors.reminder_mahnung1_days && <p className="text-sm text-red-500">{errors.reminder_mahnung1_days}</p>}
                        </div>

                        <div>
                            <div className="flex items-center justify-between mb-2">
                                <Label htmlFor="reminder_mahnung2_days">2. Mahnung (Tage)</Label>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => setPreviewTemplate('mahnung-2')}
                                    className="h-7 text-xs"
                                >
                                    <Eye className="h-3 w-3 mr-1" />
                                    {t('common.preview')}
                                </Button>
                            </div>
                            <Input
                                id="reminder_mahnung2_days"
                                type="number"
                                min="1"
                                max="90"
                                value={data.reminder_mahnung2_days}
                                onChange={(e) => setData("reminder_mahnung2_days", parseInt(e.target.value))}
                            />
                            {errors.reminder_mahnung2_days && <p className="text-sm text-red-500">{errors.reminder_mahnung2_days}</p>}
                        </div>

                        <div>
                            <div className="flex items-center justify-between mb-2">
                                <Label htmlFor="reminder_mahnung3_days">3. Mahnung (Tage)</Label>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => setPreviewTemplate('mahnung-3')}
                                    className="h-7 text-xs"
                                >
                                    <Eye className="h-3 w-3 mr-1" />
                                    {t('common.preview')}
                                </Button>
                            </div>
                            <Input
                                id="reminder_mahnung3_days"
                                type="number"
                                min="1"
                                max="90"
                                value={data.reminder_mahnung3_days}
                                onChange={(e) => setData("reminder_mahnung3_days", parseInt(e.target.value))}
                            />
                            {errors.reminder_mahnung3_days && <p className="text-sm text-red-500">{errors.reminder_mahnung3_days}</p>}
                        </div>

                        <div>
                            <div className="flex items-center justify-between mb-2">
                                <Label htmlFor="reminder_inkasso_days">Inkasso (Tage)</Label>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => setPreviewTemplate('inkasso')}
                                    className="h-7 text-xs"
                                >
                                    <Eye className="h-3 w-3 mr-1" />
                                    {t('common.preview')}
                                </Button>
                            </div>
                            <Input
                                id="reminder_inkasso_days"
                                type="number"
                                min="1"
                                max="365"
                                value={data.reminder_inkasso_days}
                                onChange={(e) => setData("reminder_inkasso_days", parseInt(e.target.value))}
                            />
                            {errors.reminder_inkasso_days && <p className="text-sm text-red-500">{errors.reminder_inkasso_days}</p>}
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{t('settings.reminderFees')}</CardTitle>
                    <CardDescription>
                        {t('settings.remindersFeesHint')}
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <Label htmlFor="reminder_mahnung1_fee">{t('settings.mahnung1Fee')}</Label>
                            <Input
                                id="reminder_mahnung1_fee"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                value={data.reminder_mahnung1_fee}
                                onChange={(e) => setData("reminder_mahnung1_fee", parseFloat(e.target.value))}
                            />
                            {errors.reminder_mahnung1_fee && <p className="text-sm text-red-500">{errors.reminder_mahnung1_fee}</p>}
                        </div>

                        <div>
                            <Label htmlFor="reminder_mahnung2_fee">{t('settings.mahnung2Fee')}</Label>
                            <Input
                                id="reminder_mahnung2_fee"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                value={data.reminder_mahnung2_fee}
                                onChange={(e) => setData("reminder_mahnung2_fee", parseFloat(e.target.value))}
                            />
                            {errors.reminder_mahnung2_fee && <p className="text-sm text-red-500">{errors.reminder_mahnung2_fee}</p>}
                        </div>

                        <div>
                            <Label htmlFor="reminder_mahnung3_fee">{t('settings.mahnung3Fee')}</Label>
                            <Input
                                id="reminder_mahnung3_fee"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                value={data.reminder_mahnung3_fee}
                                onChange={(e) => setData("reminder_mahnung3_fee", parseFloat(e.target.value))}
                            />
                            {errors.reminder_mahnung3_fee && <p className="text-sm text-red-500">{errors.reminder_mahnung3_fee}</p>}
                        </div>
                    </div>

                    <div>
                        <Label htmlFor="reminder_interest_rate">Verzugszinsen (%)</Label>
                        <Input
                            id="reminder_interest_rate"
                            type="number"
                            step="0.01"
                            min="0"
                            max="20"
                            value={data.reminder_interest_rate}
                            onChange={(e) => setData("reminder_interest_rate", parseFloat(e.target.value))}
                        />
                        {errors.reminder_interest_rate && <p className="text-sm text-red-500">{errors.reminder_interest_rate}</p>}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Automatisierung</CardTitle>
                    <CardDescription>
                        Automatisches Versenden von Mahnungen aktivieren
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="flex items-center justify-between">
                        <div className="space-y-0.5">
                            <Label htmlFor="reminder_auto_send">Automatisches Versenden</Label>
                            <p className="text-sm text-muted-foreground">
                                Mahnungen werden automatisch nach den konfigurierten Intervallen versendet
                            </p>
                        </div>
                        <Switch
                            id="reminder_auto_send"
                            checked={data.reminder_auto_send}
                            onCheckedChange={(checked) => setData("reminder_auto_send", checked)}
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

        {/* Email Preview Dialog */}
        <Dialog open={previewTemplate !== null} onOpenChange={(open) => !open && setPreviewTemplate(null)}>
            <DialogContent className="max-w-4xl max-h-[90vh] overflow-hidden">
                <DialogHeader>
                    <DialogTitle>
                        E-Mail Vorschau: {mahnungTemplates.find(t => t.id === previewTemplate)?.name || ''}
                    </DialogTitle>
                </DialogHeader>
                <div className="overflow-auto max-h-[calc(90vh-100px)]">
                    {previewTemplate && (
                        <iframe
                            src={route(mahnungTemplates.find(t => t.id === previewTemplate)?.route || '')}
                            className="w-full h-[600px] border-0"
                            title={`Email Preview: ${mahnungTemplates.find(t => t.id === previewTemplate)?.name}`}
                        />
                    )}
                </div>
                {previewTemplate && (
                    <div className="flex justify-end gap-2 pt-4 border-t">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => window.open(route(mahnungTemplates.find(t => t.id === previewTemplate)?.route || ''), "_blank")}
                        >
                            <ExternalLink className="h-4 w-4 mr-2" />
                            {t('common.openInNewTab')}
                        </Button>
                        <Button variant="outline" size="sm" onClick={() => setPreviewTemplate(null)}>
                            {t('common.close')}
                        </Button>
                    </div>
                )}
            </DialogContent>
        </Dialog>
        </>
    )
}



