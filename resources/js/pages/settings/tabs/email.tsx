"use client"

import { useState } from "react"
import { useForm } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { CheckCircle2, Eye, EyeOff } from "lucide-react"
import { route } from "ziggy-js"

interface EmailSettingsTabProps {
    emailSettings: any
}

export default function EmailSettingsTab({ emailSettings }: EmailSettingsTabProps) {
    const [showPassword, setShowPassword] = useState(false)
    const { data, setData, post, processing, errors, recentlySuccessful } = useForm({
        smtp_host: emailSettings?.smtp_host || "",
        smtp_port: emailSettings?.smtp_port || 587,
        smtp_username: emailSettings?.smtp_username || "",
        smtp_password: emailSettings?.smtp_password || "",
        smtp_encryption: emailSettings?.smtp_encryption || "tls",
        smtp_from_address: emailSettings?.smtp_from_address || "",
        smtp_from_name: emailSettings?.smtp_from_name || "",
    })

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        post(route("settings.email.update"))
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            {recentlySuccessful && (
                <Alert className="border-green-500 bg-green-50">
                    <CheckCircle2 className="h-4 w-4 text-green-600" />
                    <AlertDescription className="text-green-600">
                        E-Mail Einstellungen wurden erfolgreich aktualisiert.
                    </AlertDescription>
                </Alert>
            )}

            <Card>
                <CardHeader>
                    <CardTitle>SMTP Server Konfiguration</CardTitle>
                    <CardDescription>
                        Geben Sie die Daten Ihres E-Mail-Providers ein. Bei Gmail verwenden Sie z.B.: smtp.gmail.com (Port 587, TLS)
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                    <div className="space-y-2">
                        <Label htmlFor="smtp_host">SMTP Server *</Label>
                        <Input
                            id="smtp_host"
                            type="text"
                            placeholder="z.B. smtp.gmail.com, smtp.office365.com"
                            value={data.smtp_host}
                            onChange={(e) => setData("smtp_host", e.target.value)}
                            required
                        />
                        {errors.smtp_host && <p className="text-sm text-red-600">{errors.smtp_host}</p>}
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="smtp_port">Port *</Label>
                            <Input
                                id="smtp_port"
                                type="number"
                                min="1"
                                max="65535"
                                value={data.smtp_port}
                                onChange={(e) => setData("smtp_port", parseInt(e.target.value))}
                                required
                            />
                            {errors.smtp_port && <p className="text-sm text-red-600">{errors.smtp_port}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="smtp_encryption">Verschlüsselung *</Label>
                            <Select
                                value={data.smtp_encryption}
                                onValueChange={(value) => setData("smtp_encryption", value)}
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
                            {errors.smtp_encryption && <p className="text-sm text-red-600">{errors.smtp_encryption}</p>}
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="smtp_username">Benutzername *</Label>
                        <Input
                            id="smtp_username"
                            type="text"
                            placeholder="z.B. ihre-email@example.com"
                            value={data.smtp_username}
                            onChange={(e) => setData("smtp_username", e.target.value)}
                            required
                        />
                        {errors.smtp_username && <p className="text-sm text-red-600">{errors.smtp_username}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="smtp_password">Passwort</Label>
                        <div className="relative">
                            <Input
                                id="smtp_password"
                                type={showPassword ? "text" : "password"}
                                placeholder={data.smtp_password.includes("••") ? "Bereits gespeichert" : "Ihr SMTP Passwort"}
                                value={data.smtp_password}
                                onChange={(e) => setData("smtp_password", e.target.value)}
                            />
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                className="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                                onClick={() => setShowPassword(!showPassword)}
                            >
                                {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                            </Button>
                        </div>
                        {errors.smtp_password && <p className="text-sm text-red-600">{errors.smtp_password}</p>}
                        <p className="text-xs text-muted-foreground">
                            Lassen Sie das Feld leer, wenn Sie das Passwort nicht ändern möchten.
                        </p>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="smtp_from_address">Absender E-Mail *</Label>
                        <Input
                            id="smtp_from_address"
                            type="email"
                            value={data.smtp_from_address}
                            onChange={(e) => setData("smtp_from_address", e.target.value)}
                            required
                        />
                        {errors.smtp_from_address && <p className="text-sm text-red-600">{errors.smtp_from_address}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="smtp_from_name">Absender Name *</Label>
                        <Input
                            id="smtp_from_name"
                            type="text"
                            value={data.smtp_from_name}
                            onChange={(e) => setData("smtp_from_name", e.target.value)}
                            required
                        />
                        {errors.smtp_from_name && <p className="text-sm text-red-600">{errors.smtp_from_name}</p>}
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



