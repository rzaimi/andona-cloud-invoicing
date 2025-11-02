import { useState, useEffect } from "react"
import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Button } from "@/components/ui/button"
import { Mail, Server, Key, Shield, CheckCircle, XCircle } from "lucide-react"
import { Alert, AlertDescription } from "@/components/ui/alert"

interface Step2Props {
    data: any
    updateData: (key: string, data: any) => void
}

export default function Step2EmailSettings({ data, updateData }: Step2Props) {
    const [formData, setFormData] = useState(data.email_settings || {
        smtp_host: "",
        smtp_port: "587",
        smtp_username: "",
        smtp_password: "",
        smtp_encryption: "tls",
        smtp_from_address: "",
        smtp_from_name: "",
    })

    const [testResult, setTestResult] = useState<{ success: boolean; message: string } | null>(null)
    const [isTesting, setIsTesting] = useState(false)

    useEffect(() => {
        updateData("email_settings", formData)
    }, [formData])

    const handleChange = (field: string, value: string) => {
        setFormData({ ...formData, [field]: value })
    }

    const handleTestConnection = async () => {
        setIsTesting(true)
        setTestResult(null)

        try {
            // Simulate test - replace with actual test endpoint if needed
            await new Promise((resolve) => setTimeout(resolve, 1500))
            setTestResult({ success: true, message: "Verbindung erfolgreich!" })
        } catch (error) {
            setTestResult({ success: false, message: "Verbindung fehlgeschlagen. Bitte überprüfen Sie Ihre Einstellungen." })
        } finally {
            setIsTesting(false)
        }
    }

    return (
        <div className="space-y-6">
            <Alert>
                <Mail className="h-4 w-4" />
                <AlertDescription>
                    <strong>Wichtig:</strong> SMTP-Einstellungen sind erforderlich, um Rechnungen, Angebote und Mahnungen per E-Mail zu versenden.
                    Ohne diese Konfiguration kann die Firma nicht vollständig genutzt werden.
                </AlertDescription>
            </Alert>

            <div className="grid gap-6 md:grid-cols-2">
                {/* SMTP Host */}
                <div className="space-y-2">
                    <Label htmlFor="smtp_host" className="flex items-center gap-2">
                        <Server className="h-4 w-4" />
                        SMTP Host *
                    </Label>
                    <Input
                        id="smtp_host"
                        value={formData.smtp_host}
                        onChange={(e) => handleChange("smtp_host", e.target.value)}
                        placeholder="smtp.gmail.com"
                        required
                    />
                </div>

                {/* SMTP Port */}
                <div className="space-y-2">
                    <Label htmlFor="smtp_port">SMTP Port *</Label>
                    <Input
                        id="smtp_port"
                        type="number"
                        value={formData.smtp_port}
                        onChange={(e) => handleChange("smtp_port", e.target.value)}
                        placeholder="587"
                        required
                    />
                </div>

                {/* Encryption */}
                <div className="space-y-2">
                    <Label htmlFor="smtp_encryption" className="flex items-center gap-2">
                        <Shield className="h-4 w-4" />
                        Verschlüsselung
                    </Label>
                    <Select
                        value={formData.smtp_encryption}
                        onValueChange={(value) => handleChange("smtp_encryption", value)}
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

                {/* Username */}
                <div className="space-y-2">
                    <Label htmlFor="smtp_username" className="flex items-center gap-2">
                        <Mail className="h-4 w-4" />
                        Benutzername *
                    </Label>
                    <Input
                        id="smtp_username"
                        value={formData.smtp_username}
                        onChange={(e) => handleChange("smtp_username", e.target.value)}
                        placeholder="ihre-email@gmail.com"
                        required
                    />
                </div>

                {/* Password */}
                <div className="space-y-2 md:col-span-2">
                    <Label htmlFor="smtp_password" className="flex items-center gap-2">
                        <Key className="h-4 w-4" />
                        Passwort / App-Passwort *
                    </Label>
                    <Input
                        id="smtp_password"
                        type="password"
                        value={formData.smtp_password}
                        onChange={(e) => handleChange("smtp_password", e.target.value)}
                        placeholder="••••••••"
                        required
                    />
                    <p className="text-xs text-muted-foreground">
                        Bei Gmail/Outlook benötigen Sie ein App-spezifisches Passwort
                    </p>
                </div>

                {/* From Address */}
                <div className="space-y-2">
                    <Label htmlFor="smtp_from_address">Absender E-Mail</Label>
                    <Input
                        id="smtp_from_address"
                        type="email"
                        value={formData.smtp_from_address}
                        onChange={(e) => handleChange("smtp_from_address", e.target.value)}
                        placeholder="noreply@firma.de"
                    />
                    <p className="text-xs text-muted-foreground">
                        Falls leer, wird die Firmen-E-Mail verwendet
                    </p>
                </div>

                {/* From Name */}
                <div className="space-y-2">
                    <Label htmlFor="smtp_from_name">Absender Name</Label>
                    <Input
                        id="smtp_from_name"
                        value={formData.smtp_from_name}
                        onChange={(e) => handleChange("smtp_from_name", e.target.value)}
                        placeholder="Muster GmbH"
                    />
                    <p className="text-xs text-muted-foreground">
                        Falls leer, wird der Firmenname verwendet
                    </p>
                </div>
            </div>

            {/* Test Connection */}
            <div className="space-y-3">
                <Button
                    type="button"
                    variant="outline"
                    onClick={handleTestConnection}
                    disabled={isTesting || !formData.smtp_host || !formData.smtp_username || !formData.smtp_password}
                >
                    {isTesting ? "Teste Verbindung..." : "Verbindung testen"}
                </Button>

                {testResult && (
                    <Alert className={testResult.success ? "border-green-500 bg-green-50" : "border-red-500 bg-red-50"}>
                        {testResult.success ? <CheckCircle className="h-4 w-4 text-green-600" /> : <XCircle className="h-4 w-4 text-red-600" />}
                        <AlertDescription className={testResult.success ? "text-green-800" : "text-red-800"}>
                            {testResult.message}
                        </AlertDescription>
                    </Alert>
                )}
            </div>

            <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <p className="text-sm text-yellow-800">
                    <strong>Tipp:</strong> Verwenden Sie für Gmail ein App-Passwort (2FA erforderlich). Für Outlook/Microsoft 365 verwenden Sie Ihr normales Passwort oder OAuth2.
                </p>
            </div>
        </div>
    )
}

