"use client"

import { useState } from "react"
import { Head, Link, useForm } from "@inertiajs/react"
import { route } from "ziggy-js"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Checkbox } from "@/components/ui/checkbox"
import { ArrowLeft, Save, Building2, Mail, Landmark, Settings, Upload, X } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { User } from "@/types"

interface Company {
    id: string
    name: string
    email: string
    phone?: string
    fax?: string
    address?: string
    postal_code?: string
    city?: string
    country?: string
    tax_number?: string
    tax_office?: string
    vat_number?: string
    is_small_business?: boolean
    commercial_register?: string
    managing_director?: string
    legal_form?: string
    manager_title_override?: string
    bank_name?: string
    bank_iban?: string
    bank_bic?: string
    website?: string
    logo?: string
    status: string
}

interface EditProps {
    auth: { user: User }
    company: Company
}

const TITLE_BY_FORM: Record<string, string> = {
    einzelunternehmen: "Inhaber",
    freiberufler:      "Inhaber",
    gbr:               "Gesellschafter",
    ohg:               "Gesellschafter",
    kg:                "Komplementär",
    gmbh_co_kg:        "Komplementär",
    gmbh:              "Geschäftsführer",
    ug:                "Geschäftsführer",
    ag:                "Vorstand",
}

export default function Edit({ auth, company }: EditProps) {
    const { data, setData, post, processing, errors, transform } = useForm({
        name: company.name || "",
        email: company.email || "",
        phone: company.phone || "",
        fax: company.fax || "",
        address: company.address || "",
        postal_code: company.postal_code || "",
        city: company.city || "",
        country: company.country || "Deutschland",
        tax_number: company.tax_number || "",
        tax_office: company.tax_office || "",
        vat_number: company.vat_number || "",
        is_small_business: company.is_small_business || false,
        commercial_register: company.commercial_register || "",
        managing_director: company.managing_director || "",
        legal_form: company.legal_form || "",
        manager_title_override: company.manager_title_override || "",
        bank_name: company.bank_name || "",
        bank_iban: company.bank_iban || "",
        bank_bic: company.bank_bic || "",
        website: company.website || "",
        status: company.status || "active",
        logo: null as File | null,
    })

    const [activeTab, setActiveTab] = useState("basic")
    const [logoPreview, setLogoPreview] = useState<string | null>(null)
    const [removedLogo, setRemovedLogo] = useState(false)

    const handleLogoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0]
        if (file) {
            setData("logo", file)
            setLogoPreview(URL.createObjectURL(file))
            setRemovedLogo(false)
        }
    }

    const handleRemoveLogo = () => {
        setData("logo", null)
        setLogoPreview(null)
        setRemovedLogo(true)
    }

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        transform((d) => ({ ...d, _method: "put", remove_logo: removedLogo ? "1" : "0" }))
        post(route("companies.update", company.id), { forceFormData: true, preserveScroll: true })
    }

    const currentLogoSrc = company.logo
        ? (company.logo.startsWith("/") || company.logo.startsWith("http") ? company.logo : `/storage/${company.logo}`)
        : null
    const displayLogo = logoPreview || (removedLogo ? null : currentLogoSrc)

    const derivedTitle =
        data.manager_title_override ||
        TITLE_BY_FORM[data.legal_form] ||
        "Geschäftsführung"

    return (
        <AppLayout>
            <Head title={`${company.name} bearbeiten`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={`/companies/${company.id}`}>
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Zurück
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-xl font-bold text-gray-900 dark:text-gray-100">Firma bearbeiten</h1>
                            <p className="text-muted-foreground">{company.name}</p>
                        </div>
                    </div>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmit}>
                    <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-4">
                        <TabsList>
                            <TabsTrigger value="basic">
                                <Building2 className="h-4 w-4 mr-2" />
                                Grundinformationen
                            </TabsTrigger>
                            <TabsTrigger value="contact">
                                <Mail className="h-4 w-4 mr-2" />
                                Kontaktdaten
                            </TabsTrigger>
                            <TabsTrigger value="banking">
                                <Landmark className="h-4 w-4 mr-2" />
                                Bankverbindung
                            </TabsTrigger>
                            <TabsTrigger value="settings">
                                <Settings className="h-4 w-4 mr-2" />
                                Einstellungen
                            </TabsTrigger>
                        </TabsList>

                        {/* Basic Information */}
                        <TabsContent value="basic" className="space-y-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Grundinformationen</CardTitle>
                                    <CardDescription>Grundlegende Informationen über die Firma</CardDescription>
                                </CardHeader>
                                <CardContent className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="name">
                                            Firmenname <span className="text-red-500">*</span>
                                        </Label>
                                        <Input
                                            id="name"
                                            value={data.name}
                                            onChange={(e) => setData("name", e.target.value)}
                                            placeholder="Muster GmbH"
                                            required
                                        />
                                        {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="status">
                                            Status <span className="text-red-500">*</span>
                                        </Label>
                                        <Select value={data.status} onValueChange={(v) => setData("status", v)}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="active">Aktiv</SelectItem>
                                                <SelectItem value="inactive">Inaktiv</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        {errors.status && <p className="text-sm text-red-500">{errors.status}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="legal_form">Rechtsform</Label>
                                        <Select
                                            value={data.legal_form || undefined}
                                            onValueChange={(v) => setData("legal_form", v)}
                                        >
                                            <SelectTrigger id="legal_form">
                                                <SelectValue placeholder="Rechtsform wählen" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="einzelunternehmen">Einzelunternehmen</SelectItem>
                                                <SelectItem value="freiberufler">Freiberufler</SelectItem>
                                                <SelectItem value="gbr">GbR</SelectItem>
                                                <SelectItem value="ohg">OHG</SelectItem>
                                                <SelectItem value="kg">KG</SelectItem>
                                                <SelectItem value="gmbh">GmbH</SelectItem>
                                                <SelectItem value="ug">UG (haftungsbeschränkt)</SelectItem>
                                                <SelectItem value="gmbh_co_kg">GmbH &amp; Co. KG</SelectItem>
                                                <SelectItem value="ag">AG</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p className="text-xs text-muted-foreground">
                                            Pflichtangabe auf Rechnungen (HGB §37a / GmbHG §35a).
                                        </p>
                                        {errors.legal_form && <p className="text-sm text-red-500">{errors.legal_form}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="managing_director">{derivedTitle}</Label>
                                        <Input
                                            id="managing_director"
                                            value={data.managing_director}
                                            onChange={(e) => setData("managing_director", e.target.value)}
                                            placeholder="Vor- und Nachname"
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            Wird als „{derivedTitle}" im Rechnungs-Footer ausgewiesen.
                                        </p>
                                        {errors.managing_director && <p className="text-sm text-red-500">{errors.managing_director}</p>}
                                    </div>

                                    <div className="space-y-2 md:col-span-2">
                                        <Label htmlFor="manager_title_override">Titel überschreiben (optional)</Label>
                                        <Input
                                            id="manager_title_override"
                                            value={data.manager_title_override}
                                            onChange={(e) => setData("manager_title_override", e.target.value)}
                                            placeholder="z.B. Prokurist, Vorsitzender"
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            Nur ausfüllen, wenn der automatisch abgeleitete Titel nicht passt.
                                        </p>
                                        {errors.manager_title_override && <p className="text-sm text-red-500">{errors.manager_title_override}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="tax_number">Steuernummer (St.Nr.)</Label>
                                        <Input
                                            id="tax_number"
                                            value={data.tax_number}
                                            onChange={(e) => setData("tax_number", e.target.value)}
                                            placeholder="12/345/67890"
                                        />
                                        {errors.tax_number && <p className="text-sm text-red-500">{errors.tax_number}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="tax_office">Finanzamt</Label>
                                        <Input
                                            id="tax_office"
                                            value={data.tax_office}
                                            onChange={(e) => setData("tax_office", e.target.value)}
                                            placeholder="z.B. Finanzamt Gießen"
                                        />
                                        {errors.tax_office && <p className="text-sm text-red-500">{errors.tax_office}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="vat_number">Umsatzsteuer-ID</Label>
                                        <Input
                                            id="vat_number"
                                            value={data.vat_number}
                                            onChange={(e) => setData("vat_number", e.target.value)}
                                            placeholder="DE123456789"
                                        />
                                        {errors.vat_number && <p className="text-sm text-red-500">{errors.vat_number}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="commercial_register">Handelsregister</Label>
                                        <Input
                                            id="commercial_register"
                                            value={data.commercial_register}
                                            onChange={(e) => setData("commercial_register", e.target.value)}
                                            placeholder="HRB 12345"
                                        />
                                        {errors.commercial_register && <p className="text-sm text-red-500">{errors.commercial_register}</p>}
                                    </div>

                                    <div className="flex items-center space-x-2 md:col-span-2 pt-2">
                                        <Checkbox
                                            id="is_small_business"
                                            checked={Boolean(data.is_small_business)}
                                            onCheckedChange={(checked) => setData("is_small_business", checked === true)}
                                        />
                                        <Label htmlFor="is_small_business" className="cursor-pointer">
                                            Kleinunternehmerregelung (§19 UStG) – keine Umsatzsteuer ausweisen
                                        </Label>
                                    </div>
                                    {errors.is_small_business && <p className="text-sm text-red-500">{errors.is_small_business}</p>}
                                </CardContent>
                            </Card>

                            {/* Logo */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Firmenlogo</CardTitle>
                                    <CardDescription>Logo hochladen (PNG/JPG, max. 2 MB)</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="flex items-start gap-4">
                                        {displayLogo && (
                                            <div className="relative">
                                                <img
                                                    src={displayLogo}
                                                    alt="Firmenlogo"
                                                    className="h-24 w-24 object-contain rounded border"
                                                    onError={(e) => {
                                                        ;(e.target as HTMLImageElement).style.display = "none"
                                                    }}
                                                />
                                                <Button
                                                    type="button"
                                                    variant="destructive"
                                                    size="icon"
                                                    className="absolute -top-2 -right-2 h-6 w-6"
                                                    onClick={handleRemoveLogo}
                                                >
                                                    <X className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        )}
                                        <div className="flex-1">
                                            <Label htmlFor="logo" className="cursor-pointer">
                                                <div className="flex items-center gap-2">
                                                    <Button type="button" variant="outline" size="sm" asChild>
                                                        <span>
                                                            <Upload className="h-4 w-4 mr-2" />
                                                            Logo hochladen
                                                        </span>
                                                    </Button>
                                                </div>
                                                <Input
                                                    id="logo"
                                                    type="file"
                                                    accept="image/jpeg,image/png,image/jpg,image/gif"
                                                    className="hidden"
                                                    onChange={handleLogoChange}
                                                />
                                            </Label>
                                            {errors.logo && <p className="text-sm text-red-500 mt-2">{errors.logo}</p>}
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* Contact Information */}
                        <TabsContent value="contact" className="space-y-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Kontaktdaten</CardTitle>
                                    <CardDescription>E-Mail, Telefon, Adresse und Website der Firma</CardDescription>
                                </CardHeader>
                                <CardContent className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="email">
                                            E-Mail <span className="text-red-500">*</span>
                                        </Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            value={data.email}
                                            onChange={(e) => setData("email", e.target.value)}
                                            placeholder="info@firma.de"
                                            required
                                        />
                                        {errors.email && <p className="text-sm text-red-500">{errors.email}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="phone">Telefon</Label>
                                        <Input
                                            id="phone"
                                            type="tel"
                                            value={data.phone}
                                            onChange={(e) => setData("phone", e.target.value)}
                                            placeholder="+49 123 456789"
                                        />
                                        {errors.phone && <p className="text-sm text-red-500">{errors.phone}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="fax">Fax</Label>
                                        <Input
                                            id="fax"
                                            value={data.fax}
                                            onChange={(e) => setData("fax", e.target.value)}
                                            placeholder="+49 123 456789"
                                        />
                                        {errors.fax && <p className="text-sm text-red-500">{errors.fax}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="website">Website</Label>
                                        <Input
                                            id="website"
                                            type="url"
                                            value={data.website}
                                            onChange={(e) => setData("website", e.target.value)}
                                            placeholder="https://www.firma.de"
                                        />
                                        {errors.website && <p className="text-sm text-red-500">{errors.website}</p>}
                                    </div>

                                    <div className="space-y-2 md:col-span-2">
                                        <Label htmlFor="address">Straße und Hausnummer</Label>
                                        <Input
                                            id="address"
                                            value={data.address}
                                            onChange={(e) => setData("address", e.target.value)}
                                            placeholder="Musterstraße 123"
                                        />
                                        {errors.address && <p className="text-sm text-red-500">{errors.address}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="postal_code">Postleitzahl</Label>
                                        <Input
                                            id="postal_code"
                                            value={data.postal_code}
                                            onChange={(e) => setData("postal_code", e.target.value)}
                                            placeholder="12345"
                                        />
                                        {errors.postal_code && <p className="text-sm text-red-500">{errors.postal_code}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="city">Stadt</Label>
                                        <Input
                                            id="city"
                                            value={data.city}
                                            onChange={(e) => setData("city", e.target.value)}
                                            placeholder="München"
                                        />
                                        {errors.city && <p className="text-sm text-red-500">{errors.city}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="country">
                                            Land <span className="text-red-500">*</span>
                                        </Label>
                                        <Select value={data.country} onValueChange={(v) => setData("country", v)}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="Deutschland">Deutschland</SelectItem>
                                                <SelectItem value="Österreich">Österreich</SelectItem>
                                                <SelectItem value="Schweiz">Schweiz</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        {errors.country && <p className="text-sm text-red-500">{errors.country}</p>}
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* Banking Information */}
                        <TabsContent value="banking" className="space-y-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Bankverbindung</CardTitle>
                                    <CardDescription>Bankdaten für Rechnungen und Zahlungen</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="bank_name">Bankname</Label>
                                        <Input
                                            id="bank_name"
                                            value={data.bank_name}
                                            onChange={(e) => setData("bank_name", e.target.value)}
                                            placeholder="Sparkasse München"
                                        />
                                        {errors.bank_name && <p className="text-sm text-red-500">{errors.bank_name}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="bank_iban">IBAN</Label>
                                        <Input
                                            id="bank_iban"
                                            value={data.bank_iban}
                                            onChange={(e) => setData("bank_iban", e.target.value)}
                                            placeholder="DE89 3704 0044 0532 0130 00"
                                            className="font-mono"
                                        />
                                        {errors.bank_iban && <p className="text-sm text-red-500">{errors.bank_iban}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="bank_bic">BIC</Label>
                                        <Input
                                            id="bank_bic"
                                            value={data.bank_bic}
                                            onChange={(e) => setData("bank_bic", e.target.value)}
                                            placeholder="COBADEFFXXX"
                                            className="font-mono"
                                        />
                                        {errors.bank_bic && <p className="text-sm text-red-500">{errors.bank_bic}</p>}
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* Settings */}
                        <TabsContent value="settings" className="space-y-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Einstellungen</CardTitle>
                                    <CardDescription>Weitere Einstellungen für diese Firma</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        <p className="text-sm text-muted-foreground">
                                            E-Mail- und Rechnungseinstellungen können in den jeweiligen Bereichen konfiguriert werden:
                                        </p>
                                        <div className="space-y-2">
                                            <Button variant="outline" asChild className="w-full justify-start">
                                                <Link href="/settings/email">
                                                    <Mail className="h-4 w-4 mr-2" />
                                                    E-Mail Einstellungen
                                                </Link>
                                            </Button>
                                            <Button variant="outline" asChild className="w-full justify-start">
                                                <Link href="/settings/reminders">
                                                    <Settings className="h-4 w-4 mr-2" />
                                                    Mahnungseinstellungen
                                                </Link>
                                            </Button>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </Tabs>

                    {/* Action Buttons */}
                    <div className="flex justify-end space-x-2 mt-6">
                        <Button type="button" variant="outline" asChild>
                            <Link href={`/companies/${company.id}`}>Abbrechen</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Save className="h-4 w-4 mr-2" />
                            {processing ? "Speichern..." : "Speichern"}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    )
}
