import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Switch } from "@/components/ui/switch"
import { AlertCircle, Mail } from "lucide-react"

export default function Step2EmailSettings({ data, setData, errors }: any) {
    const es = data.email_settings ?? {}
    // Treat "true" string (from FormData round-trip) and boolean true as active
    const configureSmtp = es.configure_smtp === true || es.configure_smtp === "true"

    const set = (field: string, value: any) =>
        setData("email_settings", { ...es, [field]: value })

    return (
        <div className="space-y-6">
            {/* Optional SMTP toggle */}
            <div className="flex items-center justify-between p-4 border rounded-lg">
                <div className="space-y-0.5">
                    <Label className="text-base font-semibold cursor-pointer">
                        E-Mail-Konfiguration aktivieren
                    </Label>
                    <p className="text-sm text-muted-foreground">
                        Ermöglicht den automatischen Versand von Rechnungen und Mahnungen.
                        Kann auch später in den Einstellungen eingerichtet werden.
                    </p>
                </div>
                <Switch
                    checked={configureSmtp}
                    onCheckedChange={(checked) => set("configure_smtp", checked)}
                />
            </div>

            {!configureSmtp && (
                <Alert>
                    <Mail className="h-4 w-4" />
                    <AlertDescription>
                        Der E-Mail-Versand ist deaktiviert. Sie können Rechnungen weiterhin manuell
                        herunterladen und versenden. Die SMTP-Einstellungen können jederzeit unter
                        <strong> Einstellungen → E-Mail</strong> nachgetragen werden.
                    </AlertDescription>
                </Alert>
            )}

            {configureSmtp && (
                <>
                    <Alert>
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>
                            <strong>Wichtig:</strong> Korrekte SMTP-Einstellungen sind erforderlich, damit
                            Rechnungen und Mahnungen automatisch versendet werden können.
                        </AlertDescription>
                    </Alert>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <Label htmlFor="smtp_host">SMTP Host *</Label>
                            <Input
                                id="smtp_host"
                                value={es.smtp_host || ""}
                                onChange={(e) => set("smtp_host", e.target.value)}
                                placeholder="smtp.gmail.com"
                            />
                            {errors?.["email_settings.smtp_host"] && (
                                <p className="text-sm text-red-500 mt-1">{errors["email_settings.smtp_host"]}</p>
                            )}
                        </div>

                        <div>
                            <Label htmlFor="smtp_port">SMTP Port *</Label>
                            <Input
                                id="smtp_port"
                                type="number"
                                value={es.smtp_port ?? 587}
                                onChange={(e) => set("smtp_port", parseInt(e.target.value) || 587)}
                            />
                            {errors?.["email_settings.smtp_port"] && (
                                <p className="text-sm text-red-500 mt-1">{errors["email_settings.smtp_port"]}</p>
                            )}
                        </div>

                        <div>
                            <Label htmlFor="smtp_username">SMTP Benutzername *</Label>
                            <Input
                                id="smtp_username"
                                value={es.smtp_username || ""}
                                onChange={(e) => set("smtp_username", e.target.value)}
                                placeholder="deine-email@gmail.com"
                            />
                            {errors?.["email_settings.smtp_username"] && (
                                <p className="text-sm text-red-500 mt-1">{errors["email_settings.smtp_username"]}</p>
                            )}
                        </div>

                        <div>
                            <Label htmlFor="smtp_password">SMTP Passwort *</Label>
                            <Input
                                id="smtp_password"
                                type="password"
                                value={es.smtp_password || ""}
                                onChange={(e) => set("smtp_password", e.target.value)}
                                placeholder="••••••••"
                            />
                            {errors?.["email_settings.smtp_password"] && (
                                <p className="text-sm text-red-500 mt-1">{errors["email_settings.smtp_password"]}</p>
                            )}
                        </div>

                        <div>
                            <Label htmlFor="smtp_encryption">Verschlüsselung *</Label>
                            <Select
                                value={es.smtp_encryption || "tls"}
                                onValueChange={(v) => set("smtp_encryption", v)}
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
                            {errors?.["email_settings.smtp_encryption"] && (
                                <p className="text-sm text-red-500 mt-1">{errors["email_settings.smtp_encryption"]}</p>
                            )}
                        </div>

                        <div>
                            <Label htmlFor="smtp_from_address">Absender E-Mail *</Label>
                            <Input
                                id="smtp_from_address"
                                type="email"
                                value={es.smtp_from_address || ""}
                                onChange={(e) => set("smtp_from_address", e.target.value)}
                                placeholder="noreply@musterfirma.de"
                            />
                            {errors?.["email_settings.smtp_from_address"] && (
                                <p className="text-sm text-red-500 mt-1">{errors["email_settings.smtp_from_address"]}</p>
                            )}
                        </div>

                        <div className="md:col-span-2">
                            <Label htmlFor="smtp_from_name">Absender Name *</Label>
                            <Input
                                id="smtp_from_name"
                                value={es.smtp_from_name || ""}
                                onChange={(e) => set("smtp_from_name", e.target.value)}
                                placeholder="Musterfirma GmbH"
                            />
                            {errors?.["email_settings.smtp_from_name"] && (
                                <p className="text-sm text-red-500 mt-1">{errors["email_settings.smtp_from_name"]}</p>
                            )}
                        </div>
                    </div>

                    <Alert>
                        <AlertDescription>
                            <strong>Häufige SMTP-Anbieter:</strong>
                            <ul className="mt-2 ml-4 list-disc space-y-1 text-sm">
                                <li>Gmail: smtp.gmail.com (Port 587, TLS)</li>
                                <li>Outlook: smtp-mail.outlook.com (Port 587, TLS)</li>
                                <li>1&1 / IONOS: smtp.ionos.de (Port 587, TLS)</li>
                                <li>Strato: smtp.strato.de (Port 465, SSL)</li>
                            </ul>
                        </AlertDescription>
                    </Alert>
                </>
            )}
        </div>
    )
}
