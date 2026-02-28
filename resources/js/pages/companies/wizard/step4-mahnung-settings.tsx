import { useState, useEffect } from "react"
import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Switch } from "@/components/ui/switch"
import { Bell, Clock, Euro, Percent } from "lucide-react"
import { Alert, AlertDescription } from "@/components/ui/alert"

interface Step4Props {
    data: any
    updateData: (key: string, data: any) => void
}

export default function Step4MahnungSettings({ data, updateData }: Step4Props) {
    const [formData, setFormData] = useState(data.mahnung_settings || {
        reminder_friendly_days: 7,
        reminder_mahnung1_days: 14,
        reminder_mahnung2_days: 21,
        reminder_mahnung3_days: 30,
        reminder_inkasso_days: 45,
        reminder_mahnung1_fee: 5.00,
        reminder_mahnung2_fee: 10.00,
        reminder_mahnung3_fee: 15.00,
        reminder_interest_rate: 9.00,
        reminder_auto_send: true,
    })

    useEffect(() => {
        updateData("mahnung_settings", formData)
    }, [formData])

    const handleChange = (field: string, value: any) => {
        setFormData({ ...formData, [field]: value })
    }

    return (
        <div className="space-y-6">
            <Alert>
                <Bell className="h-4 w-4" />
                <AlertDescription>
                    {t('pages.companies.mahnungDesc1')}.
                    {t('pages.companies.mahnungDesc2')}
                </AlertDescription>
            </Alert>

            <div className="space-y-6">
                {/* Friendly Reminder */}
                <div className="border rounded-lg p-4 space-y-3">
                    <h4 className="font-semibold flex items-center gap-2">
                        <Clock className="h-4 w-4" />
                        Freundliche Erinnerung
                    </h4>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="reminder_friendly_days">{t('settings.daysAfterDue')}</Label>
                            <Input
                                id="reminder_friendly_days"
                                type="number"
                                min="1"
                                value={formData.reminder_friendly_days}
                                onChange={(e) => handleChange("reminder_friendly_days", parseInt(e.target.value))}
                            />
                        </div>
                        <div className="flex items-end pb-2">
                            <span className="text-sm text-muted-foreground">{t('settings.noFee')}</span>
                        </div>
                    </div>
                </div>

                {/* Mahnung 1 */}
                <div className="border rounded-lg p-4 space-y-3 bg-yellow-50">
                    <h4 className="font-semibold flex items-center gap-2">
                        <Bell className="h-4 w-4 text-yellow-600" />
                        1. Mahnung
                    </h4>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="reminder_mahnung1_days">{t('settings.daysAfterDue')}</Label>
                            <Input
                                id="reminder_mahnung1_days"
                                type="number"
                                min="1"
                                value={formData.reminder_mahnung1_days}
                                onChange={(e) => handleChange("reminder_mahnung1_days", parseInt(e.target.value))}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="reminder_mahnung1_fee" className="flex items-center gap-2">
                                <Euro className="h-4 w-4" />
                                {t('settings.mahnungFee')}
                            </Label>
                            <Input
                                id="reminder_mahnung1_fee"
                                type="number"
                                step="0.01"
                                min="0"
                                value={formData.reminder_mahnung1_fee}
                                onChange={(e) => handleChange("reminder_mahnung1_fee", parseFloat(e.target.value))}
                            />
                        </div>
                    </div>
                </div>

                {/* Mahnung 2 */}
                <div className="border rounded-lg p-4 space-y-3 bg-orange-50">
                    <h4 className="font-semibold flex items-center gap-2">
                        <Bell className="h-4 w-4 text-orange-600" />
                        2. Mahnung
                    </h4>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="reminder_mahnung2_days">{t('settings.daysAfterDue')}</Label>
                            <Input
                                id="reminder_mahnung2_days"
                                type="number"
                                min="1"
                                value={formData.reminder_mahnung2_days}
                                onChange={(e) => handleChange("reminder_mahnung2_days", parseInt(e.target.value))}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="reminder_mahnung2_fee" className="flex items-center gap-2">
                                <Euro className="h-4 w-4" />
                                {t('settings.mahnungFee')}
                            </Label>
                            <Input
                                id="reminder_mahnung2_fee"
                                type="number"
                                step="0.01"
                                min="0"
                                value={formData.reminder_mahnung2_fee}
                                onChange={(e) => handleChange("reminder_mahnung2_fee", parseFloat(e.target.value))}
                            />
                        </div>
                    </div>
                </div>

                {/* Mahnung 3 */}
                <div className="border rounded-lg p-4 space-y-3 bg-red-50">
                    <h4 className="font-semibold flex items-center gap-2">
                        <Bell className="h-4 w-4 text-red-600" />
                        3. Mahnung (Letzte Warnung)
                    </h4>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="reminder_mahnung3_days">{t('settings.daysAfterDue')}</Label>
                            <Input
                                id="reminder_mahnung3_days"
                                type="number"
                                min="1"
                                value={formData.reminder_mahnung3_days}
                                onChange={(e) => handleChange("reminder_mahnung3_days", parseInt(e.target.value))}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="reminder_mahnung3_fee" className="flex items-center gap-2">
                                <Euro className="h-4 w-4" />
                                {t('settings.mahnungFee')}
                            </Label>
                            <Input
                                id="reminder_mahnung3_fee"
                                type="number"
                                step="0.01"
                                min="0"
                                value={formData.reminder_mahnung3_fee}
                                onChange={(e) => handleChange("reminder_mahnung3_fee", parseFloat(e.target.value))}
                            />
                        </div>
                    </div>
                </div>

                {/* Inkasso */}
                <div className="border rounded-lg p-4 space-y-3 bg-purple-50">
                    <h4 className="font-semibold flex items-center gap-2">
                        <Bell className="h-4 w-4 text-purple-600" />
                        {t('settings.inkassoTitle')}
                    </h4>
                    <div className="space-y-2">
                        <Label htmlFor="reminder_inkasso_days">{t('settings.daysAfterDue')}</Label>
                        <Input
                            id="reminder_inkasso_days"
                            type="number"
                            min="1"
                            value={formData.reminder_inkasso_days}
                            onChange={(e) => handleChange("reminder_inkasso_days", parseInt(e.target.value))}
                        />
                        <p className="text-xs text-muted-foreground">
                            {t('settings.inkassoWarning')}
                        </p>
                    </div>
                </div>

                {/* Interest Rate */}
                <div className="border rounded-lg p-4 space-y-3">
                    <h4 className="font-semibold flex items-center gap-2">
                        <Percent className="h-4 w-4" />
                        Verzugszinsen
                    </h4>
                    <div className="space-y-2">
                        <Label htmlFor="reminder_interest_rate">Zinssatz (% p.a.)</Label>
                        <Input
                            id="reminder_interest_rate"
                            type="number"
                            step="0.01"
                            min="0"
                            value={formData.reminder_interest_rate}
                            onChange={(e) => handleChange("reminder_interest_rate", parseFloat(e.target.value))}
                        />
                        <p className="text-xs text-muted-foreground">
                            {t('settings.legalInterestRate')}
                        </p>
                    </div>
                </div>

                {/* Auto Send */}
                <div className="border rounded-lg p-4 space-y-3">
                    <div className="flex items-center justify-between">
                        <div className="space-y-1">
                            <Label htmlFor="reminder_auto_send" className="text-base">
                                Automatischer Versand
                            </Label>
                            <p className="text-sm text-muted-foreground">
                                {t('settings.remindersAutoDaily')}
                            </p>
                        </div>
                        <Switch
                            id="reminder_auto_send"
                            checked={formData.reminder_auto_send}
                            onCheckedChange={(checked) => handleChange("reminder_auto_send", checked)}
                        />
                    </div>
                </div>
            </div>

            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p className="text-sm text-blue-800">
                    <strong>{t('common.recommendation')}:</strong> {t('settings.mahnungRecommendation')}
                    {t('settings.adjustAnytime')}
                </p>
            </div>
        </div>
    )
}

