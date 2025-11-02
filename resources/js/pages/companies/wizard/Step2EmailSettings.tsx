import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { AlertCircle } from "lucide-react"

export default function Step2EmailSettings({ data, setData, errors }: any) {
    return (
        <div className="space-y-6">
            <Alert variant="destructive">
                <AlertCircle className="h-4 w-4" />
                <AlertDescription>
                    <strong>Wichtig:</strong> Die E-Mail-Konfiguration ist erforderlich, um Rechnungen und Angebote an Kunden zu versenden.
                    Ohne korrekte SMTP-Einstellungen können keine E-Mails versendet werden.
                </AlertDescription>
            </Alert>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <Label htmlFor="smtp_host">SMTP Host *</Label>
                    <Input
                        id="smtp_host"
                        value={data.email_settings?.smtp_host || ''}
                        onChange={(e) => setData('email_settings', { ...data.email_settings, smtp_host: e.target.value })}
                        placeholder="smtp.gmail.com"
                    />
                    {errors?.['email_settings.smtp_host'] && (
                        <p className="text-sm text-red-500 mt-1">{errors['email_settings.smtp_host']}</p>
                    )}
                </div>

                <div>
                    <Label htmlFor="smtp_port">SMTP Port *</Label>
                    <Input
                        id="smtp_port"
                        type="number"
                        value={data.email_settings?.smtp_port || 587}
                        onChange={(e) => setData('email_settings', { ...data.email_settings, smtp_port: parseInt(e.target.value) })}
                    />
                    {errors?.['email_settings.smtp_port'] && (
                        <p className="text-sm text-red-500 mt-1">{errors['email_settings.smtp_port']}</p>
                    )}
                </div>

                <div>
                    <Label htmlFor="smtp_username">SMTP Benutzername *</Label>
                    <Input
                        id="smtp_username"
                        value={data.email_settings?.smtp_username || ''}
                        onChange={(e) => setData('email_settings', { ...data.email_settings, smtp_username: e.target.value })}
                        placeholder="deine-email@gmail.com"
                    />
                    {errors?.['email_settings.smtp_username'] && (
                        <p className="text-sm text-red-500 mt-1">{errors['email_settings.smtp_username']}</p>
                    )}
                </div>

                <div>
                    <Label htmlFor="smtp_password">SMTP Passwort *</Label>
                    <Input
                        id="smtp_password"
                        type="password"
                        value={data.email_settings?.smtp_password || ''}
                        onChange={(e) => setData('email_settings', { ...data.email_settings, smtp_password: e.target.value })}
                        placeholder="••••••••"
                    />
                    {errors?.['email_settings.smtp_password'] && (
                        <p className="text-sm text-red-500 mt-1">{errors['email_settings.smtp_password']}</p>
                    )}
                </div>

                <div>
                    <Label htmlFor="smtp_encryption">Verschlüsselung *</Label>
                    <Select
                        value={data.email_settings?.smtp_encryption || 'tls'}
                        onValueChange={(value) => setData('email_settings', { ...data.email_settings, smtp_encryption: value })}
                    >
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="tls">TLS (empfohlen)</SelectItem>
                            <SelectItem value="ssl">SSL</SelectItem>
                            <SelectItem value="none">Keine</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div>
                    <Label htmlFor="smtp_from_address">Absender E-Mail *</Label>
                    <Input
                        id="smtp_from_address"
                        type="email"
                        value={data.email_settings?.smtp_from_address || ''}
                        onChange={(e) => setData('email_settings', { ...data.email_settings, smtp_from_address: e.target.value })}
                        placeholder="noreply@musterfirma.de"
                    />
                    {errors?.['email_settings.smtp_from_address'] && (
                        <p className="text-sm text-red-500 mt-1">{errors['email_settings.smtp_from_address']}</p>
                    )}
                </div>

                <div className="md:col-span-2">
                    <Label htmlFor="smtp_from_name">Absender Name *</Label>
                    <Input
                        id="smtp_from_name"
                        value={data.email_settings?.smtp_from_name || ''}
                        onChange={(e) => setData('email_settings', { ...data.email_settings, smtp_from_name: e.target.value })}
                        placeholder="Musterfirma GmbH"
                    />
                    {errors?.['email_settings.smtp_from_name'] && (
                        <p className="text-sm text-red-500 mt-1">{errors['email_settings.smtp_from_name']}</p>
                    )}
                </div>
            </div>

            <Alert>
                <AlertDescription>
                    <strong>Hinweis:</strong> Häufige SMTP-Anbieter:
                    <ul className="mt-2 ml-4 list-disc space-y-1 text-sm">
                        <li>Gmail: smtp.gmail.com (Port 587)</li>
                        <li>Outlook: smtp-mail.outlook.com (Port 587)</li>
                        <li>1&1/IONOS: smtp.ionos.de (Port 587)</li>
                        <li>Strato: smtp.strato.de (Port 465)</li>
                    </ul>
                </AlertDescription>
            </Alert>
        </div>
    )
}


