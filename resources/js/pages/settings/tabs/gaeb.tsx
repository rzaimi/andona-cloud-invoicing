import type React from "react"
import { useForm } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Label } from "@/components/ui/label"
import { Switch } from "@/components/ui/switch"
import { Save } from "lucide-react"
import { route } from "ziggy-js"

interface GaebSettingsTabProps {
    enabled: boolean
}

export default function GaebSettingsTab({ enabled }: GaebSettingsTabProps) {
    const { data, setData, post, processing } = useForm({
        gaeb_import_enabled: enabled,
    })

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        post(route("settings.gaeb.update"))
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>GAEB-Import</CardTitle>
                    <CardDescription>
                        Leistungsverzeichnisse im GAEB-DA-XML-Format (.X81, .X83, .X84) können in Rechnungen und Angebote übernommen werden. Es werden nur die Positionen als benutzerdefinierte Zeilen eingefügt.
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="flex items-center justify-between gap-4">
                        <div className="space-y-0.5">
                            <Label htmlFor="gaeb_import_enabled">GAEB-Import aktivieren</Label>
                            <p className="text-sm text-muted-foreground">
                                Danach erscheint „GAEB importieren“ neben „Position hinzufügen“ beim Anlegen und Bearbeiten von Rechnungen und Angeboten.
                            </p>
                        </div>
                        <Switch
                            id="gaeb_import_enabled"
                            checked={data.gaeb_import_enabled}
                            onCheckedChange={(checked) => setData("gaeb_import_enabled", checked)}
                        />
                    </div>
                </CardContent>
            </Card>

            <div className="flex justify-end">
                <Button type="submit" disabled={processing}>
                    <Save className="mr-2 h-4 w-4" />
                    {processing ? "Speichert..." : "Einstellungen speichern"}
                </Button>
            </div>
        </form>
    )
}
