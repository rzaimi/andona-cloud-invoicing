import { useState, useEffect } from "react"
import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Switch } from "@/components/ui/switch"
import { User, Mail, Key, Send } from "lucide-react"
import { Alert, AlertDescription } from "@/components/ui/alert"

interface Step6Props {
    data: any
    updateData: (key: string, data: any) => void
}

export default function Step6FirstUser({ data, updateData }: Step6Props) {
    const [formData, setFormData] = useState(data.first_user || {
        create_user: false,
        name: "",
        email: "",
        password: "",
        password_confirmation: "",
        send_welcome_email: false,
    })

    useEffect(() => {
        updateData("first_user", formData)
    }, [formData])

    const handleChange = (field: string, value: any) => {
        setFormData({ ...formData, [field]: value })
    }

    return (
        <div className="space-y-6">
            <Alert>
                <User className="h-4 w-4" />
                <AlertDescription>
                    {t('settings.firstUserDesc1')}
                    {t('settings.firstUserDesc2')}
                </AlertDescription>
            </Alert>

            {/* Enable First User Creation */}
            <div className="border rounded-lg p-4 space-y-4">
                <div className="flex items-center justify-between">
                    <div className="space-y-1">
                        <Label htmlFor="create_user" className="text-base">
                            Ersten Benutzer erstellen
                        </Label>
                        <p className="text-sm text-muted-foreground">
                            {t('settings.firstUserAdminRole')}
                        </p>
                    </div>
                    <Switch
                        id="create_user"
                        checked={formData.create_user}
                        onCheckedChange={(checked) => handleChange("create_user", checked)}
                    />
                </div>
            </div>

            {formData.create_user && (
                <div className="space-y-6">
                    <div className="grid gap-6 md:grid-cols-2">
                        {/* Name */}
                        <div className="space-y-2 md:col-span-2">
                            <Label htmlFor="name" className="flex items-center gap-2">
                                <User className="h-4 w-4" />
                                Name *
                            </Label>
                            <Input
                                id="name"
                                value={formData.name}
                                onChange={(e) => handleChange("name", e.target.value)}
                                placeholder="Max Mustermann"
                                required={formData.create_user}
                            />
                        </div>

                        {/* Email */}
                        <div className="space-y-2 md:col-span-2">
                            <Label htmlFor="email" className="flex items-center gap-2">
                                <Mail className="h-4 w-4" />
                                E-Mail *
                            </Label>
                            <Input
                                id="email"
                                type="email"
                                value={formData.email}
                                onChange={(e) => handleChange("email", e.target.value)}
                                placeholder="max@firma.de"
                                required={formData.create_user}
                            />
                        </div>

                        {/* Password */}
                        <div className="space-y-2">
                            <Label htmlFor="password" className="flex items-center gap-2">
                                <Key className="h-4 w-4" />
                                Passwort *
                            </Label>
                            <Input
                                id="password"
                                type="password"
                                value={formData.password}
                                onChange={(e) => handleChange("password", e.target.value)}
                                placeholder="••••••••"
                                required={formData.create_user}
                            />
                            <p className="text-xs text-muted-foreground">
                                Mindestens 8 Zeichen
                            </p>
                        </div>

                        {/* Password Confirmation */}
                        <div className="space-y-2">
                            <Label htmlFor="password_confirmation">{t('auth.confirmPassword')} *</Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                value={formData.password_confirmation}
                                onChange={(e) => handleChange("password_confirmation", e.target.value)}
                                placeholder="••••••••"
                                required={formData.create_user}
                            />
                        </div>
                    </div>

                    {/* Password Match Warning */}
                    {formData.password &&
                        formData.password_confirmation &&
                        formData.password !== formData.password_confirmation && (
                            <Alert className="border-yellow-500 bg-yellow-50">
                                <AlertDescription className="text-yellow-800">
                                    {t('pages.users.passwordMismatch')}
                                </AlertDescription>
                            </Alert>
                        )}

                    {/* Send Welcome Email */}
                    <div className="border rounded-lg p-4 space-y-3">
                        <div className="flex items-center justify-between">
                            <div className="space-y-1">
                                <Label htmlFor="send_welcome_email" className="text-base flex items-center gap-2">
                                    <Send className="h-4 w-4" />
                                    Willkommens-E-Mail senden
                                </Label>
                                <p className="text-sm text-muted-foreground">
                                    {t('settings.firstUserEmailCredentials')}
                                </p>
                            </div>
                            <Switch
                                id="send_welcome_email"
                                checked={formData.send_welcome_email}
                                onCheckedChange={(checked) => handleChange("send_welcome_email", checked)}
                            />
                        </div>
                    </div>
                </div>
            )}

            {!formData.create_user && (
                <div className="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
                    <User className="h-12 w-12 mx-auto mb-3 text-gray-400" />
                    <p className="text-sm text-muted-foreground">
                        Aktivieren Sie den Schalter oben, um einen ersten Administrator zu erstellen.
                    </p>
                    <p className="text-sm text-muted-foreground mt-2">
                        {t('settings.firstUserSkipHint')}
                    </p>
                </div>
            )}
        </div>
    )
}

