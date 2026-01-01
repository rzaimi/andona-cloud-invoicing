"use client"

import { useForm } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Switch } from "@/components/ui/switch"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { route } from "ziggy-js"

interface ERechnungSettingsTabProps {
    erechnungSettings: any
}

export default function ERechnungSettingsTab({ erechnungSettings }: ERechnungSettingsTabProps) {
    const { data, setData, post, processing, errors } = useForm({
        erechnung_enabled: erechnungSettings?.erechnung_enabled ?? false,
        xrechnung_enabled: erechnungSettings?.xrechnung_enabled ?? true,
        zugferd_enabled: erechnungSettings?.zugferd_enabled ?? true,
        zugferd_profile: erechnungSettings?.zugferd_profile || 'EN16931',
        business_process_id: erechnungSettings?.business_process_id || '',
        electronic_address_scheme: erechnungSettings?.electronic_address_scheme || 'EM',
        electronic_address: erechnungSettings?.electronic_address || '',
    })

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        post(route("settings.erechnung.update"))
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>E-Rechnung Einstellungen</CardTitle>
                    <CardDescription>
                        Konfigurieren Sie die Einstellungen für elektronische Rechnungen (XRechnung, ZUGFeRD)
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                    <div className="flex items-center justify-between">
                        <div className="space-y-0.5">
                            <Label htmlFor="erechnung_enabled">E-Rechnung aktivieren</Label>
                            <p className="text-sm text-muted-foreground">
                                Aktivieren Sie die Generierung von elektronischen Rechnungen
                            </p>
                        </div>
                        <Switch
                            id="erechnung_enabled"
                            checked={data.erechnung_enabled}
                            onCheckedChange={(checked) => setData("erechnung_enabled", checked)}
                        />
                    </div>

                    <div className="flex items-center justify-between">
                        <div className="space-y-0.5">
                            <Label htmlFor="xrechnung_enabled">XRechnung aktivieren</Label>
                            <p className="text-sm text-muted-foreground">
                                Generieren Sie Rechnungen im XRechnung-Format
                            </p>
                        </div>
                        <Switch
                            id="xrechnung_enabled"
                            checked={data.xrechnung_enabled}
                            onCheckedChange={(checked) => setData("xrechnung_enabled", checked)}
                        />
                    </div>

                    <div className="flex items-center justify-between">
                        <div className="space-y-0.5">
                            <Label htmlFor="zugferd_enabled">ZUGFeRD aktivieren</Label>
                            <p className="text-sm text-muted-foreground">
                                Generieren Sie Rechnungen im ZUGFeRD-Format
                            </p>
                        </div>
                        <Switch
                            id="zugferd_enabled"
                            checked={data.zugferd_enabled}
                            onCheckedChange={(checked) => setData("zugferd_enabled", checked)}
                        />
                    </div>

                    {data.zugferd_enabled && (
                        <div className="space-y-2">
                            <Label htmlFor="zugferd_profile">ZUGFeRD Profil</Label>
                            <Select value={data.zugferd_profile} onValueChange={(value) => setData("zugferd_profile", value)}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="MINIMUM">MINIMUM</SelectItem>
                                    <SelectItem value="BASIC">BASIC</SelectItem>
                                    <SelectItem value="EN16931">EN16931</SelectItem>
                                    <SelectItem value="EXTENDED">EXTENDED</SelectItem>
                                    <SelectItem value="XRECHNUNG">XRECHNUNG</SelectItem>
                                </SelectContent>
                            </Select>
                            {errors.zugferd_profile && <p className="text-sm text-red-600">{errors.zugferd_profile}</p>}
                        </div>
                    )}

                    <div className="space-y-2">
                        <Label htmlFor="business_process_id">Geschäftsprozess-ID</Label>
                        <Input
                            id="business_process_id"
                            value={data.business_process_id}
                            onChange={(e) => setData("business_process_id", e.target.value)}
                            placeholder="z.B. urn:fdc:peppol.eu:2017:poacc:billing:01:1.0"
                        />
                        {errors.business_process_id && <p className="text-sm text-red-600">{errors.business_process_id}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="electronic_address_scheme">Elektronische Adresse Schema</Label>
                        <Input
                            id="electronic_address_scheme"
                            value={data.electronic_address_scheme}
                            onChange={(e) => setData("electronic_address_scheme", e.target.value)}
                            placeholder="z.B. EM"
                        />
                        {errors.electronic_address_scheme && <p className="text-sm text-red-600">{errors.electronic_address_scheme}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="electronic_address">Elektronische Adresse</Label>
                        <Input
                            id="electronic_address"
                            value={data.electronic_address}
                            onChange={(e) => setData("electronic_address", e.target.value)}
                            placeholder="z.B. DE123456789"
                        />
                        {errors.electronic_address && <p className="text-sm text-red-600">{errors.electronic_address}</p>}
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

