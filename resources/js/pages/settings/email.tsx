import { Head, useForm, usePage } from "@inertiajs/react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { 
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog"
import { Mail, Save, AlertCircle, CheckCircle2, Eye, ExternalLink } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { User } from "@/types"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { useState } from "react"
import { route } from "ziggy-js"

interface EmailSettingsProps {
    user: User
    settings: {
        smtp_host: string
        smtp_port: number
        smtp_username: string
        smtp_password: string
        smtp_encryption: string
        smtp_from_address: string
        smtp_from_name: string
    }
}

interface EmailTemplate {
    id: string
    name: string
    description: string
    route: string
    category: 'invoice' | 'offer' | 'reminder' | 'general'
}

const EMAIL_TEMPLATES: EmailTemplate[] = [
    {
        id: 'invoice-sent',
        name: 'Rechnung versendet',
        description: 'E-Mail-Vorlage für versendete Rechnungen',
        route: 'settings.emails.preview.invoice-sent',
        category: 'invoice',
    },
    {
        id: 'invoice-reminder',
        name: 'Rechnungserinnerung',
        description: 'Allgemeine Erinnerung für Rechnungen',
        route: 'settings.emails.preview.invoice-reminder',
        category: 'invoice',
    },
    {
        id: 'offer-sent',
        name: 'Angebot versendet',
        description: 'E-Mail-Vorlage für versendete Angebote',
        route: 'settings.emails.preview.offer-sent',
        category: 'offer',
    },
    {
        id: 'offer-accepted',
        name: 'Angebot angenommen',
        description: 'E-Mail-Vorlage für angenommene Angebote',
        route: 'settings.emails.preview.offer-accepted',
        category: 'offer',
    },
    {
        id: 'offer-reminder',
        name: 'Angebotserinnerung',
        description: 'Erinnerung für ablaufende Angebote',
        route: 'settings.emails.preview.offer-reminder',
        category: 'offer',
    },
    {
        id: 'payment-received',
        name: 'Zahlung erhalten',
        description: 'Bestätigung bei erhaltenen Zahlungen',
        route: 'settings.emails.preview.payment-received',
        category: 'invoice',
    },
    {
        id: 'welcome',
        name: 'Willkommens-E-Mail',
        description: 'Willkommensnachricht für neue Kunden',
        route: 'settings.emails.preview.welcome',
        category: 'general',
    },
    {
        id: 'friendly-reminder',
        name: 'Freundliche Erinnerung',
        description: 'Erste freundliche Erinnerung bei überfälligen Rechnungen',
        route: 'settings.emails.preview.friendly-reminder',
        category: 'reminder',
    },
    {
        id: 'mahnung-1',
        name: '1. Mahnung',
        description: 'Erste offizielle Mahnung',
        route: 'settings.emails.preview.mahnung-1',
        category: 'reminder',
    },
    {
        id: 'mahnung-2',
        name: '2. Mahnung',
        description: 'Zweite Mahnung',
        route: 'settings.emails.preview.mahnung-2',
        category: 'reminder',
    },
    {
        id: 'mahnung-3',
        name: '3. Mahnung',
        description: 'Dritte Mahnung',
        route: 'settings.emails.preview.mahnung-3',
        category: 'reminder',
    },
    {
        id: 'inkasso',
        name: 'Inkasso',
        description: 'Letzte Mahnung vor Inkasso',
        route: 'settings.emails.preview.inkasso',
        category: 'reminder',
    },
]

export default function EmailSettings({ settings }: Omit<EmailSettingsProps, 'user'>) {
    const { props } = usePage()
    const user = (props as any).auth?.user || (props as any).user
    const [showPassword, setShowPassword] = useState(false)
    const [previewTemplate, setPreviewTemplate] = useState<EmailTemplate | null>(null)
    const [isPreviewOpen, setIsPreviewOpen] = useState(false)

    const handlePreview = (template: EmailTemplate) => {
        setPreviewTemplate(template)
        setIsPreviewOpen(true)
    }

    const handleClosePreview = () => {
        setIsPreviewOpen(false)
        setPreviewTemplate(null)
    }

    const { data, setData, post, processing, errors, recentlySuccessful } = useForm({
        smtp_host: settings.smtp_host || "",
        smtp_port: settings.smtp_port || 587,
        smtp_username: settings.smtp_username || "",
        smtp_password: settings.smtp_password || "",
        smtp_encryption: settings.smtp_encryption || "tls",
        smtp_from_address: settings.smtp_from_address || "",
        smtp_from_name: settings.smtp_from_name || "",
    })

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        post(route("settings.email.update"))
    }

    return (
        <AppLayout user={user}>
            <Head title="E-Mail Einstellungen" />

            <div className="container mx-auto py-6 max-w-4xl">
                <div className="mb-6">
                    <h1 className="text-3xl font-bold tracking-tight flex items-center gap-2">
                        <Mail className="h-8 w-8" />
                        E-Mail Einstellungen
                    </h1>
                    <p className="text-muted-foreground mt-2">
                        Konfigurieren Sie Ihre SMTP-Einstellungen für den Versand von Rechnungen und Angeboten per E-Mail
                    </p>
                </div>

                {recentlySuccessful && (
                    <Alert className="mb-6 border-green-500 bg-green-50">
                        <CheckCircle2 className="h-4 w-4 text-green-600" />
                        <AlertDescription className="text-green-600">
                            E-Mail Einstellungen wurden erfolgreich aktualisiert.
                        </AlertDescription>
                    </Alert>
                )}

                <form onSubmit={handleSubmit}>
                    <Card>
                        <CardHeader>
                            <CardTitle>SMTP Server Konfiguration</CardTitle>
                            <CardDescription>
                                Geben Sie die Daten Ihres E-Mail-Providers ein. Bei Gmail verwenden Sie z.B.: smtp.gmail.com (Port 587, TLS)
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            {/* SMTP Host */}
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
                                {errors.smtp_host && (
                                    <p className="text-sm text-red-600">{errors.smtp_host}</p>
                                )}
                            </div>

                            {/* SMTP Port & Encryption */}
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
                                    {errors.smtp_port && (
                                        <p className="text-sm text-red-600">{errors.smtp_port}</p>
                                    )}
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
                                    {errors.smtp_encryption && (
                                        <p className="text-sm text-red-600">{errors.smtp_encryption}</p>
                                    )}
                                </div>
                            </div>

                            {/* SMTP Username */}
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
                                {errors.smtp_username && (
                                    <p className="text-sm text-red-600">{errors.smtp_username}</p>
                                )}
                            </div>

                            {/* SMTP Password */}
                            <div className="space-y-2">
                                <Label htmlFor="smtp_password">Passwort</Label>
                                <Input
                                    id="smtp_password"
                                    type={showPassword ? "text" : "password"}
                                    placeholder={data.smtp_password.includes("••") ? "Bereits gespeichert" : "Ihr SMTP Passwort"}
                                    value={data.smtp_password}
                                    onChange={(e) => setData("smtp_password", e.target.value)}
                                />
                                {errors.smtp_password && (
                                    <p className="text-sm text-red-600">{errors.smtp_password}</p>
                                )}
                                <p className="text-xs text-muted-foreground">
                                    Lassen Sie dieses Feld leer, um das gespeicherte Passwort beizubehalten.
                                </p>
                            </div>

                            {/* From Address */}
                            <div className="space-y-2">
                                <Label htmlFor="smtp_from_address">Absender E-Mail *</Label>
                                <Input
                                    id="smtp_from_address"
                                    type="email"
                                    placeholder="z.B. rechnungen@ihre-firma.de"
                                    value={data.smtp_from_address}
                                    onChange={(e) => setData("smtp_from_address", e.target.value)}
                                    required
                                />
                                {errors.smtp_from_address && (
                                    <p className="text-sm text-red-600">{errors.smtp_from_address}</p>
                                )}
                                <p className="text-xs text-muted-foreground">
                                    Diese E-Mail-Adresse erscheint als Absender bei Ihren Kunden.
                                </p>
                            </div>

                            {/* From Name */}
                            <div className="space-y-2">
                                <Label htmlFor="smtp_from_name">Absender Name *</Label>
                                <Input
                                    id="smtp_from_name"
                                    type="text"
                                    placeholder="z.B. Ihre Firma GmbH"
                                    value={data.smtp_from_name}
                                    onChange={(e) => setData("smtp_from_name", e.target.value)}
                                    required
                                />
                                {errors.smtp_from_name && (
                                    <p className="text-sm text-red-600">{errors.smtp_from_name}</p>
                                )}
                            </div>

                            <Alert>
                                <AlertCircle className="h-4 w-4" />
                                <AlertDescription>
                                    <strong>Hinweis:</strong> Bei Gmail müssen Sie möglicherweise ein "App-spezifisches Passwort" 
                                    erstellen. Bei Microsoft 365 können Sie Ihre normalen Zugangsdaten verwenden.
                                </AlertDescription>
                            </Alert>

                            <div className="flex justify-end">
                                <Button type="submit" disabled={processing}>
                                    <Save className="mr-2 h-4 w-4" />
                                    {processing ? "Speichern..." : "Einstellungen speichern"}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </form>

                {/* Common SMTP Settings Reference */}
                <Card className="mt-6">
                    <CardHeader>
                        <CardTitle>Häufig verwendete SMTP Einstellungen</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="border rounded-lg p-4">
                                    <h4 className="font-semibold mb-2">Gmail</h4>
                                    <p className="text-sm text-muted-foreground">
                                        <strong>Host:</strong> smtp.gmail.com<br />
                                        <strong>Port:</strong> 587<br />
                                        <strong>Verschlüsselung:</strong> TLS<br />
                                        <strong>Hinweis:</strong> App-Passwort erforderlich
                                    </p>
                                </div>

                                <div className="border rounded-lg p-4">
                                    <h4 className="font-semibold mb-2">Microsoft 365 / Outlook</h4>
                                    <p className="text-sm text-muted-foreground">
                                        <strong>Host:</strong> smtp.office365.com<br />
                                        <strong>Port:</strong> 587<br />
                                        <strong>Verschlüsselung:</strong> TLS
                                    </p>
                                </div>

                                <div className="border rounded-lg p-4">
                                    <h4 className="font-semibold mb-2">Yahoo Mail</h4>
                                    <p className="text-sm text-muted-foreground">
                                        <strong>Host:</strong> smtp.mail.yahoo.com<br />
                                        <strong>Port:</strong> 587<br />
                                        <strong>Verschlüsselung:</strong> TLS
                                    </p>
                                </div>

                                <div className="border rounded-lg p-4">
                                    <h4 className="font-semibold mb-2">Andere Provider</h4>
                                    <p className="text-sm text-muted-foreground">
                                        Kontaktieren Sie Ihren E-Mail-Provider für die korrekten SMTP-Einstellungen.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Email Template Previews */}
                <Card className="mt-6">
                    <CardHeader>
                        <CardTitle>E-Mail-Vorlagen Vorschau</CardTitle>
                        <CardDescription>
                            Vorschau aller verfügbaren E-Mail-Vorlagen mit Beispieldaten
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-6">
                            {/* Rechnungen */}
                            <div>
                                <h3 className="font-semibold text-lg mb-3">Rechnungen</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    {EMAIL_TEMPLATES.filter(t => t.category === 'invoice').map((template) => (
                                        <div key={template.id} className="border rounded-lg p-4 flex items-center justify-between">
                                            <div className="flex-1">
                                                <h4 className="font-medium">{template.name}</h4>
                                                <p className="text-sm text-muted-foreground">{template.description}</p>
                                            </div>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handlePreview(template)}
                                            >
                                                <Eye className="mr-2 h-4 w-4" />
                                                Vorschau
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Angebote */}
                            <div>
                                <h3 className="font-semibold text-lg mb-3">Angebote</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    {EMAIL_TEMPLATES.filter(t => t.category === 'offer').map((template) => (
                                        <div key={template.id} className="border rounded-lg p-4 flex items-center justify-between">
                                            <div className="flex-1">
                                                <h4 className="font-medium">{template.name}</h4>
                                                <p className="text-sm text-muted-foreground">{template.description}</p>
                                            </div>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handlePreview(template)}
                                            >
                                                <Eye className="mr-2 h-4 w-4" />
                                                Vorschau
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Mahnungen */}
                            <div>
                                <h3 className="font-semibold text-lg mb-3">Mahnungen</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    {EMAIL_TEMPLATES.filter(t => t.category === 'reminder').map((template) => (
                                        <div key={template.id} className="border rounded-lg p-4 flex items-center justify-between">
                                            <div className="flex-1">
                                                <h4 className="font-medium">{template.name}</h4>
                                                <p className="text-sm text-muted-foreground">{template.description}</p>
                                            </div>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handlePreview(template)}
                                            >
                                                <Eye className="mr-2 h-4 w-4" />
                                                Vorschau
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Allgemein */}
                            <div>
                                <h3 className="font-semibold text-lg mb-3">Allgemein</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    {EMAIL_TEMPLATES.filter(t => t.category === 'general').map((template) => (
                                        <div key={template.id} className="border rounded-lg p-4 flex items-center justify-between">
                                            <div className="flex-1">
                                                <h4 className="font-medium">{template.name}</h4>
                                                <p className="text-sm text-muted-foreground">{template.description}</p>
                                            </div>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handlePreview(template)}
                                            >
                                                <Eye className="mr-2 h-4 w-4" />
                                                Vorschau
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Preview Dialog */}
                <Dialog open={isPreviewOpen} onOpenChange={setIsPreviewOpen}>
                    <DialogContent className="max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
                        <DialogHeader>
                            <DialogTitle>E-Mail Vorschau: {previewTemplate?.name}</DialogTitle>
                            <DialogDescription>
                                Vorschau der E-Mail-Vorlage mit Beispieldaten
                            </DialogDescription>
                        </DialogHeader>

                        {previewTemplate ? (
                            <div className="flex-1 overflow-hidden border rounded-lg bg-white">
                                <iframe
                                    src={route(previewTemplate.route)}
                                    className="w-full h-full min-h-[600px] border-0"
                                    title={`Email Preview: ${previewTemplate.name}`}
                                    style={{ minHeight: '600px' }}
                                />
                            </div>
                        ) : (
                            <div className="flex items-center justify-center py-8">
                                <p>Vorlage wird geladen...</p>
                            </div>
                        )}

                        <DialogFooter>
                            <Button variant="outline" onClick={handleClosePreview}>
                                Schließen
                            </Button>
                            {previewTemplate && (
                                <Button 
                                    variant="outline"
                                    onClick={() => window.open(route(previewTemplate.route), "_blank")}
                                >
                                    <ExternalLink className="mr-2 h-4 w-4" />
                                    In neuem Tab öffnen
                                </Button>
                            )}
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    )
}


