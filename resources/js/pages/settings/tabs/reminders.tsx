"use client"

import { useForm } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Switch } from "@/components/ui/switch"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Info } from "lucide-react"
import { route } from "ziggy-js"

interface RemindersSettingsTabProps {
    reminderSettings: any
}

export default function RemindersSettingsTab({ reminderSettings }: RemindersSettingsTabProps) {
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

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            <Alert>
                <Info className="h-4 w-4" />
                <AlertDescription>
                    Diese Einstellungen steuern den automatischen Eskalationsprozess für überfällige Rechnungen: Freundliche Erinnerung → 1. Mahnung → 2. Mahnung → 3. Mahnung → Inkasso
                </AlertDescription>
            </Alert>

            <Card>
                <CardHeader>
                    <CardTitle>Mahnintervalle</CardTitle>
                    <CardDescription>
                        Tage nach Fälligkeitsdatum, an denen Mahnungen automatisch versendet werden
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="reminder_friendly_days">Freundliche Erinnerung (Tage)</Label>
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
                            <Label htmlFor="reminder_mahnung1_days">1. Mahnung (Tage)</Label>
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
                            <Label htmlFor="reminder_mahnung2_days">2. Mahnung (Tage)</Label>
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
                            <Label htmlFor="reminder_mahnung3_days">3. Mahnung (Tage)</Label>
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
                            <Label htmlFor="reminder_inkasso_days">Inkasso (Tage)</Label>
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
                    <CardTitle>Mahngebühren</CardTitle>
                    <CardDescription>
                        Gebühren in Euro, die bei jeder Mahnstufe berechnet werden
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <Label htmlFor="reminder_mahnung1_fee">1. Mahnung Gebühr (€)</Label>
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
                            <Label htmlFor="reminder_mahnung2_fee">2. Mahnung Gebühr (€)</Label>
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
                            <Label htmlFor="reminder_mahnung3_fee">3. Mahnung Gebühr (€)</Label>
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
    )
}

