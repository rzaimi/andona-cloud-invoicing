import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Switch } from "@/components/ui/switch"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Bell } from "lucide-react"

export default function Step4MahnungSettings({ data, setData, errors }: any) {
    const ms = data.mahnung_settings ?? {}
    const set = (field: string, value: any) =>
        setData('mahnung_settings', { ...ms, [field]: value })

    return (
        <div className="space-y-6">
            <Alert>
                <Bell className="h-4 w-4" />
                <AlertDescription>
                    Das deutsche Mahnverfahren: Freundliche Erinnerung → 1. Mahnung → 2. Mahnung → 3. Mahnung → Inkasso
                </AlertDescription>
            </Alert>

            <div className="space-y-6">
                <div>
                    <h3 className="font-semibold mb-3">Mahnintervalle (Tage nach Fälligkeit)</h3>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <Label htmlFor="reminder_friendly_days">Freundliche Erinnerung</Label>
                            <Input
                                id="reminder_friendly_days"
                                type="number"
                                min="1"
                                value={ms.reminder_friendly_days ?? 7}
                                onChange={(e) => set('reminder_friendly_days', parseInt(e.target.value) || 1)}
                            />
                            <p className="text-xs text-muted-foreground mt-1">Standard: 7 Tage</p>
                            {errors?.['mahnung_settings.reminder_friendly_days'] && (
                                <p className="text-sm text-red-500 mt-1">{errors['mahnung_settings.reminder_friendly_days']}</p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="reminder_mahnung1_days">1. Mahnung</Label>
                            <Input
                                id="reminder_mahnung1_days"
                                type="number"
                                min="1"
                                value={ms.reminder_mahnung1_days ?? 14}
                                onChange={(e) => set('reminder_mahnung1_days', parseInt(e.target.value) || 1)}
                            />
                            <p className="text-xs text-muted-foreground mt-1">Standard: 14 Tage</p>
                            {errors?.['mahnung_settings.reminder_mahnung1_days'] && (
                                <p className="text-sm text-red-500 mt-1">{errors['mahnung_settings.reminder_mahnung1_days']}</p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="reminder_mahnung2_days">2. Mahnung</Label>
                            <Input
                                id="reminder_mahnung2_days"
                                type="number"
                                min="1"
                                value={ms.reminder_mahnung2_days ?? 21}
                                onChange={(e) => set('reminder_mahnung2_days', parseInt(e.target.value) || 1)}
                            />
                            <p className="text-xs text-muted-foreground mt-1">Standard: 21 Tage</p>
                            {errors?.['mahnung_settings.reminder_mahnung2_days'] && (
                                <p className="text-sm text-red-500 mt-1">{errors['mahnung_settings.reminder_mahnung2_days']}</p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="reminder_mahnung3_days">3. Mahnung</Label>
                            <Input
                                id="reminder_mahnung3_days"
                                type="number"
                                min="1"
                                value={ms.reminder_mahnung3_days ?? 30}
                                onChange={(e) => set('reminder_mahnung3_days', parseInt(e.target.value) || 1)}
                            />
                            <p className="text-xs text-muted-foreground mt-1">Standard: 30 Tage</p>
                            {errors?.['mahnung_settings.reminder_mahnung3_days'] && (
                                <p className="text-sm text-red-500 mt-1">{errors['mahnung_settings.reminder_mahnung3_days']}</p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="reminder_inkasso_days">Inkasso-Verfahren</Label>
                            <Input
                                id="reminder_inkasso_days"
                                type="number"
                                min="1"
                                value={ms.reminder_inkasso_days ?? 45}
                                onChange={(e) => set('reminder_inkasso_days', parseInt(e.target.value) || 1)}
                            />
                            <p className="text-xs text-muted-foreground mt-1">Standard: 45 Tage</p>
                            {errors?.['mahnung_settings.reminder_inkasso_days'] && (
                                <p className="text-sm text-red-500 mt-1">{errors['mahnung_settings.reminder_inkasso_days']}</p>
                            )}
                        </div>
                    </div>
                </div>

                <div>
                    <h3 className="font-semibold mb-3">Mahngebühren (€)</h3>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <Label htmlFor="reminder_mahnung1_fee">1. Mahnung Gebühr</Label>
                            <Input
                                id="reminder_mahnung1_fee"
                                type="number"
                                step="0.01"
                                min="0"
                                value={ms.reminder_mahnung1_fee ?? 5.00}
                                onChange={(e) => set('reminder_mahnung1_fee', parseFloat(e.target.value) || 0)}
                            />
                            <p className="text-xs text-muted-foreground mt-1">Standard: 5,00 €</p>
                            {errors?.['mahnung_settings.reminder_mahnung1_fee'] && (
                                <p className="text-sm text-red-500 mt-1">{errors['mahnung_settings.reminder_mahnung1_fee']}</p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="reminder_mahnung2_fee">2. Mahnung Gebühr</Label>
                            <Input
                                id="reminder_mahnung2_fee"
                                type="number"
                                step="0.01"
                                min="0"
                                value={ms.reminder_mahnung2_fee ?? 10.00}
                                onChange={(e) => set('reminder_mahnung2_fee', parseFloat(e.target.value) || 0)}
                            />
                            <p className="text-xs text-muted-foreground mt-1">Standard: 10,00 €</p>
                            {errors?.['mahnung_settings.reminder_mahnung2_fee'] && (
                                <p className="text-sm text-red-500 mt-1">{errors['mahnung_settings.reminder_mahnung2_fee']}</p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="reminder_mahnung3_fee">3. Mahnung Gebühr</Label>
                            <Input
                                id="reminder_mahnung3_fee"
                                type="number"
                                step="0.01"
                                min="0"
                                value={ms.reminder_mahnung3_fee ?? 15.00}
                                onChange={(e) => set('reminder_mahnung3_fee', parseFloat(e.target.value) || 0)}
                            />
                            <p className="text-xs text-muted-foreground mt-1">Standard: 15,00 €</p>
                            {errors?.['mahnung_settings.reminder_mahnung3_fee'] && (
                                <p className="text-sm text-red-500 mt-1">{errors['mahnung_settings.reminder_mahnung3_fee']}</p>
                            )}
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="reminder_interest_rate">Verzugszinssatz (% p.a.)</Label>
                        <Input
                            id="reminder_interest_rate"
                            type="number"
                            step="0.01"
                            min="0"
                            value={ms.reminder_interest_rate ?? 9.00}
                            onChange={(e) => set('reminder_interest_rate', parseFloat(e.target.value) || 0)}
                        />
                        <p className="text-xs text-muted-foreground mt-1">Gesetzlich: Basiszins + 9 % für Geschäftskunden</p>
                        {errors?.['mahnung_settings.reminder_interest_rate'] && (
                            <p className="text-sm text-red-500 mt-1">{errors['mahnung_settings.reminder_interest_rate']}</p>
                        )}
                    </div>

                    <div className="flex items-center justify-between p-4 border rounded-lg">
                        <div className="space-y-0.5">
                            <Label>Automatischer Versand</Label>
                            <p className="text-xs text-muted-foreground">Mahnungen automatisch versenden</p>
                        </div>
                        <Switch
                            checked={ms.reminder_auto_send !== false && ms.reminder_auto_send !== 'false' && ms.reminder_auto_send !== '0'}
                            onCheckedChange={(checked) => set('reminder_auto_send', checked)}
                        />
                    </div>
                </div>
            </div>
        </div>
    )
}


