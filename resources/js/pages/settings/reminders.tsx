import AppLayout from "@/layouts/app-layout"
import { BreadcrumbItem } from "@/types"
import { useForm, usePage } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { Switch } from "@/components/ui/switch"
import { Bell, AlertTriangle, Info } from "lucide-react"
import { Alert, AlertDescription } from "@/components/ui/alert"

interface ReminderSettingsProps {
    settings: {
        reminder_friendly_days: number
        reminder_mahnung1_days: number
        reminder_mahnung2_days: number
        reminder_mahnung3_days: number
        reminder_inkasso_days: number
        reminder_mahnung1_fee: number
        reminder_mahnung2_fee: number
        reminder_mahnung3_fee: number
        reminder_interest_rate: number
        reminder_auto_send: boolean
    }
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Einstellungen", href: "/settings" },
    { title: "Mahnungseinstellungen" },
]

export default function ReminderSettings() {
    const { settings } = usePage<ReminderSettingsProps>().props

    const { data, setData, post, processing, errors } = useForm({
        reminder_friendly_days: settings.reminder_friendly_days,
        reminder_mahnung1_days: settings.reminder_mahnung1_days,
        reminder_mahnung2_days: settings.reminder_mahnung2_days,
        reminder_mahnung3_days: settings.reminder_mahnung3_days,
        reminder_inkasso_days: settings.reminder_inkasso_days,
        reminder_mahnung1_fee: settings.reminder_mahnung1_fee,
        reminder_mahnung2_fee: settings.reminder_mahnung2_fee,
        reminder_mahnung3_fee: settings.reminder_mahnung3_fee,
        reminder_interest_rate: settings.reminder_interest_rate,
        reminder_auto_send: settings.reminder_auto_send,
    })

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        post(route("settings.reminders.update"))
    }

    return (
        <AppLayout title="Mahnungseinstellungen" breadcrumbs={breadcrumbs}>
            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-bold flex items-center gap-2">
                        <Bell className="h-8 w-8" />
                        Mahnungseinstellungen
                    </h1>
                    <p className="text-muted-foreground">
                        Konfigurieren Sie Intervalle und Gebühren für den deutschen Mahnprozess
                    </p>
                </div>

                <Alert>
                    <Info className="h-4 w-4" />
                    <AlertDescription>
                        Diese Einstellungen steuern den automatischen Eskalationsprozess für überfällige Rechnungen: Freundliche Erinnerung → 1. Mahnung → 2. Mahnung → 3. Mahnung → Inkasso
                    </AlertDescription>
                </Alert>

                <form onSubmit={handleSubmit}>
                    <div className="space-y-6">
                        {/* Reminder Intervals */}
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
                                        <Label htmlFor="reminder_friendly_days">
                                            Freundliche Erinnerung (Tage)
                                        </Label>
                                        <Input
                                            id="reminder_friendly_days"
                                            type="number"
                                            min="1"
                                            max="90"
                                            value={data.reminder_friendly_days}
                                            onChange={(e) => setData("reminder_friendly_days", parseInt(e.target.value))}
                                        />
                                        {errors.reminder_friendly_days && (
                                            <p className="text-sm text-red-500">{errors.reminder_friendly_days}</p>
                                        )}
                                    </div>

                                    <div>
                                        <Label htmlFor="reminder_mahnung1_days">
                                            1. Mahnung (Tage)
                                        </Label>
                                        <Input
                                            id="reminder_mahnung1_days"
                                            type="number"
                                            min="1"
                                            max="90"
                                            value={data.reminder_mahnung1_days}
                                            onChange={(e) => setData("reminder_mahnung1_days", parseInt(e.target.value))}
                                        />
                                        {errors.reminder_mahnung1_days && (
                                            <p className="text-sm text-red-500">{errors.reminder_mahnung1_days}</p>
                                        )}
                                    </div>

                                    <div>
                                        <Label htmlFor="reminder_mahnung2_days">
                                            2. Mahnung (Tage)
                                        </Label>
                                        <Input
                                            id="reminder_mahnung2_days"
                                            type="number"
                                            min="1"
                                            max="90"
                                            value={data.reminder_mahnung2_days}
                                            onChange={(e) => setData("reminder_mahnung2_days", parseInt(e.target.value))}
                                        />
                                        {errors.reminder_mahnung2_days && (
                                            <p className="text-sm text-red-500">{errors.reminder_mahnung2_days}</p>
                                        )}
                                    </div>

                                    <div>
                                        <Label htmlFor="reminder_mahnung3_days">
                                            3. Mahnung (Tage)
                                        </Label>
                                        <Input
                                            id="reminder_mahnung3_days"
                                            type="number"
                                            min="1"
                                            max="90"
                                            value={data.reminder_mahnung3_days}
                                            onChange={(e) => setData("reminder_mahnung3_days", parseInt(e.target.value))}
                                        />
                                        {errors.reminder_mahnung3_days && (
                                            <p className="text-sm text-red-500">{errors.reminder_mahnung3_days}</p>
                                        )}
                                    </div>

                                    <div>
                                        <Label htmlFor="reminder_inkasso_days">
                                            Inkasso (Tage)
                                        </Label>
                                        <Input
                                            id="reminder_inkasso_days"
                                            type="number"
                                            min="1"
                                            max="365"
                                            value={data.reminder_inkasso_days}
                                            onChange={(e) => setData("reminder_inkasso_days", parseInt(e.target.value))}
                                        />
                                        {errors.reminder_inkasso_days && (
                                            <p className="text-sm text-red-500">{errors.reminder_inkasso_days}</p>
                                        )}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Reminder Fees */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Mahngebühren</CardTitle>
                                <CardDescription>
                                    Gebühren in Euro, die bei jeder Mahnstufe berechnet werden
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <Alert variant="default" className="bg-yellow-50 border-yellow-200">
                                    <AlertTriangle className="h-4 w-4 text-yellow-600" />
                                    <AlertDescription className="text-yellow-700">
                                        Mahngebühren müssen gesetzlich zulässig sein. Übliche Beträge: €5-15
                                    </AlertDescription>
                                </Alert>

                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <Label htmlFor="reminder_mahnung1_fee">
                                            1. Mahnung Gebühr (€)
                                        </Label>
                                        <Input
                                            id="reminder_mahnung1_fee"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            value={data.reminder_mahnung1_fee}
                                            onChange={(e) => setData("reminder_mahnung1_fee", parseFloat(e.target.value))}
                                        />
                                        {errors.reminder_mahnung1_fee && (
                                            <p className="text-sm text-red-500">{errors.reminder_mahnung1_fee}</p>
                                        )}
                                    </div>

                                    <div>
                                        <Label htmlFor="reminder_mahnung2_fee">
                                            2. Mahnung Gebühr (€)
                                        </Label>
                                        <Input
                                            id="reminder_mahnung2_fee"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            value={data.reminder_mahnung2_fee}
                                            onChange={(e) => setData("reminder_mahnung2_fee", parseFloat(e.target.value))}
                                        />
                                        {errors.reminder_mahnung2_fee && (
                                            <p className="text-sm text-red-500">{errors.reminder_mahnung2_fee}</p>
                                        )}
                                    </div>

                                    <div>
                                        <Label htmlFor="reminder_mahnung3_fee">
                                            3. Mahnung Gebühr (€)
                                        </Label>
                                        <Input
                                            id="reminder_mahnung3_fee"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            value={data.reminder_mahnung3_fee}
                                            onChange={(e) => setData("reminder_mahnung3_fee", parseFloat(e.target.value))}
                                        />
                                        {errors.reminder_mahnung3_fee && (
                                            <p className="text-sm text-red-500">{errors.reminder_mahnung3_fee}</p>
                                        )}
                                    </div>
                                </div>

                                <div className="max-w-xs">
                                    <Label htmlFor="reminder_interest_rate">
                                        Verzugszinssatz (% pro Jahr)
                                    </Label>
                                    <Input
                                        id="reminder_interest_rate"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="20"
                                        value={data.reminder_interest_rate}
                                        onChange={(e) => setData("reminder_interest_rate", parseFloat(e.target.value))}
                                    />
                                    {errors.reminder_interest_rate && (
                                        <p className="text-sm text-red-500">{errors.reminder_interest_rate}</p>
                                    )}
                                    <p className="text-xs text-muted-foreground mt-1">
                                        Üblich: 9% über Basiszinssatz
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Automation */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Automatisierung</CardTitle>
                                <CardDescription>
                                    Automatisches Versenden von Mahnungen aktivieren/deaktivieren
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="flex items-center space-x-2">
                                    <Switch
                                        id="reminder_auto_send"
                                        checked={data.reminder_auto_send}
                                        onCheckedChange={(checked) => setData("reminder_auto_send", checked)}
                                    />
                                    <Label htmlFor="reminder_auto_send" className="cursor-pointer">
                                        Mahnungen automatisch versenden (täglich um 9:00 Uhr)
                                    </Label>
                                </div>
                                {errors.reminder_auto_send && (
                                    <p className="text-sm text-red-500 mt-2">{errors.reminder_auto_send}</p>
                                )}
                                
                                {!data.reminder_auto_send && (
                                    <Alert className="mt-4">
                                        <Info className="h-4 w-4" />
                                        <AlertDescription>
                                            Automatische Mahnungen sind deaktiviert. Sie können Mahnungen nur manuell von der Rechnungsübersicht aus versenden.
                                        </AlertDescription>
                                    </Alert>
                                )}
                            </CardContent>
                        </Card>

                        <div className="flex justify-end">
                            <Button type="submit" disabled={processing}>
                                {processing ? "Wird gespeichert..." : "Einstellungen speichern"}
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </AppLayout>
    )
}


