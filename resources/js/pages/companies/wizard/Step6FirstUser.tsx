import { useTranslation } from "react-i18next"
import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Switch } from "@/components/ui/switch"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { User } from "lucide-react"

export default function Step6FirstUser({ data, setData, errors }: any) {
    const { t } = useTranslation()
    return (
        <div className="space-y-6">
            <Alert>
                <User className="h-4 w-4" />
                <AlertDescription>
                    {t('settings.firstUserOptionalDesc')}
                </AlertDescription>
            </Alert>

            <div className="flex items-center justify-between p-4 border rounded-lg bg-gray-50">
                <div className="space-y-0.5">
                    <Label className="text-base">{t('settings.firstUserCreateLabel')}</Label>
                    <p className="text-sm text-muted-foreground">{t('settings.firstUserAdminDesc')}</p>
                </div>
                <Switch
                    checked={data.first_user?.create_user || false}
                    onCheckedChange={(checked) => setData('first_user', {
                        ...data.first_user,
                        create_user: checked,
                        // Keep payload clean to avoid backend validation noise
                        ...(checked ? {} : { name: '', email: '', password: '', send_welcome_email: true }),
                    })}
                />
            </div>

            {data.first_user?.create_user && (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <Label htmlFor="user_name">Name *</Label>
                        <Input
                            id="user_name"
                            value={data.first_user?.name || ''}
                            onChange={(e) => setData('first_user', { ...data.first_user, name: e.target.value })}
                            placeholder="Max Mustermann"
                        />
                        {errors?.['first_user.name'] && (
                            <p className="text-sm text-red-500 mt-1">{errors['first_user.name']}</p>
                        )}
                    </div>

                    <div>
                        <Label htmlFor="user_email">E-Mail *</Label>
                        <Input
                            id="user_email"
                            type="email"
                            value={data.first_user?.email || ''}
                            onChange={(e) => setData('first_user', { ...data.first_user, email: e.target.value })}
                            placeholder="max@musterfirma.de"
                        />
                        {errors?.['first_user.email'] && (
                            <p className="text-sm text-red-500 mt-1">{errors['first_user.email']}</p>
                        )}
                    </div>

                    <div className="md:col-span-2">
                        <Label htmlFor="user_password">Passwort *</Label>
                        <Input
                            id="user_password"
                            type="password"
                            value={data.first_user?.password || ''}
                            onChange={(e) => setData('first_user', { ...data.first_user, password: e.target.value })}
                            placeholder="Mindestens 8 Zeichen"
                        />
                        {errors?.['first_user.password'] && (
                            <p className="text-sm text-red-500 mt-1">{errors['first_user.password']}</p>
                        )}
                    </div>

                    <div className="md:col-span-2">
                        <div className="flex items-center justify-between p-3 border rounded-lg">
                            <div className="space-y-0.5">
                                <Label>Willkommens-E-Mail senden</Label>
                                <p className="text-xs text-muted-foreground">
                                    Dem Benutzer eine E-Mail mit Zugangsdaten senden
                                </p>
                            </div>
                            <Switch
                                checked={data.first_user?.send_welcome_email !== false}
                                onCheckedChange={(checked) => setData('first_user', { ...data.first_user, send_welcome_email: checked })}
                            />
                        </div>
                    </div>
                </div>
            )}
        </div>
    )
}


