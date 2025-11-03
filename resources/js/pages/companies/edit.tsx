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
import { ArrowLeft, Save, Building2, Mail, Landmark, Settings } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { User } from "@/types"

interface Company {
    id: string
    name: string
    email: string
    phone?: string
    address?: string
    postal_code?: string
    city?: string
    country?: string
    tax_number?: string
    vat_number?: string
    commercial_register?: string
    managing_director?: string
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

export default function Edit({ auth, company }: EditProps) {
    const { data, setData, post, processing, errors, transform } = useForm({
        name: company.name || "",
        email: company.email || "",
        phone: company.phone || "",
        address: company.address || "",
        postal_code: company.postal_code || "",
        city: company.city || "",
        country: company.country || "Deutschland",
        tax_number: company.tax_number || "",
        vat_number: company.vat_number || "",
        commercial_register: company.commercial_register || "",
        managing_director: company.managing_director || "",
        bank_name: company.bank_name || "",
        bank_iban: company.bank_iban || "",
        bank_bic: company.bank_bic || "",
        website: company.website || "",
        status: company.status || "active",
        logo: null as File | null,
    })

    const [activeTab, setActiveTab] = useState("basic")

    const [logoPreview, setLogoPreview] = useState<string | null>(null)

    const handleLogoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0]
        if (file) {
            setData("logo", file)
            setLogoPreview(URL.createObjectURL(file))
        }
    }

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        // Use POST + method spoofing for reliable file uploads across browsers/servers
        transform((data) => ({ ...data, _method: "put" }))
        post(route("companies.update", company.id), { forceFormData: true, preserveScroll: true })
    }

    return (
        <AppLayout user={auth.user}>
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
                            <h1 className="text-3xl font-bold tracking-tight">Firma bearbeiten</h1>
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
                                    <CardDescription>
                                        Grundlegende Informationen über die Firma
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="grid gap-4 md:grid-cols-2">
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
                                            {errors.name && (
                                                <p className="text-sm text-red-500">{errors.name}</p>
                                            )}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="status">
                                                Status <span className="text-red-500">*</span>
                                            </Label>
                                            <Select value={data.status} onValueChange={(value) => setData("status", value)}>
                                                <SelectTrigger>
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="active">Aktiv</SelectItem>
                                                    <SelectItem value="inactive">Inaktiv</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            {errors.status && (
                                                <p className="text-sm text-red-500">{errors.status}</p>
                                            )}
                                        </div>
                                    </div>

                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="tax_number">Steuernummer</Label>
                                            <Input
                                                id="tax_number"
                                                value={data.tax_number}
                                                onChange={(e) => setData("tax_number", e.target.value)}
                                                placeholder="12/345/67890"
                                            />
                                            {errors.tax_number && (
                                                <p className="text-sm text-red-500">{errors.tax_number}</p>
                                            )}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="vat_number">USt-IdNr.</Label>
                                            <Input
                                                id="vat_number"
                                                value={data.vat_number}
                                                onChange={(e) => setData("vat_number", e.target.value)}
                                                placeholder="DE123456789"
                                            />
                                            {errors.vat_number && (
                                                <p className="text-sm text-red-500">{errors.vat_number}</p>
                                            )}
                                        </div>
                                    </div>

                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="commercial_register">Handelsregister</Label>
                                            <Input
                                                id="commercial_register"
                                                value={data.commercial_register}
                                                onChange={(e) => setData("commercial_register", e.target.value)}
                                                placeholder="HRB 12345"
                                            />
                                            {errors.commercial_register && (
                                                <p className="text-sm text-red-500">{errors.commercial_register}</p>
                                            )}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="managing_director">Geschäftsführer</Label>
                                            <Input
                                                id="managing_director"
                                                value={data.managing_director}
                                                onChange={(e) => setData("managing_director", e.target.value)}
                                                placeholder="Max Mustermann"
                                            />
                                            {errors.managing_director && (
                                                <p className="text-sm text-red-500">{errors.managing_director}</p>
                                            )}
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Logo */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Logo</CardTitle>
                                    <CardDescription>Firmenlogo hochladen (optional)</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {(company.logo || logoPreview) && (
                                        <div className="flex items-center gap-4">
                                            <img
                                                src={logoPreview || (company.logo ? `/storage/${company.logo}` : "")}
                                                alt="Aktuelles Firmenlogo"
                                                className="h-16 w-16 rounded object-contain border"
                                            />
                                            <div className="text-sm text-muted-foreground">Aktuelles Logo</div>
                                        </div>
                                    )}
                                    <div>
                                        <Label htmlFor="logo">Logo-Datei</Label>
                                        <Input
                                            id="logo"
                                            type="file"
                                            accept="image/jpeg,image/png,image/jpg,image/gif"
                                            onChange={handleLogoChange}
                                            className={errors.logo ? "border-red-500" : ""}
                                        />
                                        {errors.logo && <p className="text-sm text-red-500 mt-1">{errors.logo}</p>}
                                        <p className="text-sm text-gray-500 mt-1">
                                            Maximale Dateigröße: 2MB. Erlaubte Formate: JPEG, PNG, JPG, GIF
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* Contact Information */}
                        <TabsContent value="contact" className="space-y-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Kontaktdaten</CardTitle>
                                    <CardDescription>
                                        E-Mail, Telefon, Adresse und Website der Firma
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="grid gap-4 md:grid-cols-2">
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
                                            {errors.email && (
                                                <p className="text-sm text-red-500">{errors.email}</p>
                                            )}
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
                                            {errors.phone && (
                                                <p className="text-sm text-red-500">{errors.phone}</p>
                                            )}
                                        </div>
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
                                        {errors.website && (
                                            <p className="text-sm text-red-500">{errors.website}</p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="address">Adresse</Label>
                                        <Textarea
                                            id="address"
                                            value={data.address}
                                            onChange={(e) => setData("address", e.target.value)}
                                            placeholder="Musterstraße 123"
                                            rows={3}
                                        />
                                        {errors.address && (
                                            <p className="text-sm text-red-500">{errors.address}</p>
                                        )}
                                    </div>

                                    <div className="grid gap-4 md:grid-cols-3">
                                        <div className="space-y-2">
                                            <Label htmlFor="postal_code">PLZ</Label>
                                            <Input
                                                id="postal_code"
                                                value={data.postal_code}
                                                onChange={(e) => setData("postal_code", e.target.value)}
                                                placeholder="12345"
                                            />
                                            {errors.postal_code && (
                                                <p className="text-sm text-red-500">{errors.postal_code}</p>
                                            )}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="city">Stadt</Label>
                                            <Input
                                                id="city"
                                                value={data.city}
                                                onChange={(e) => setData("city", e.target.value)}
                                                placeholder="München"
                                            />
                                            {errors.city && (
                                                <p className="text-sm text-red-500">{errors.city}</p>
                                            )}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="country">
                                                Land <span className="text-red-500">*</span>
                                            </Label>
                                            <Input
                                                id="country"
                                                value={data.country}
                                                onChange={(e) => setData("country", e.target.value)}
                                                placeholder="Deutschland"
                                                required
                                            />
                                            {errors.country && (
                                                <p className="text-sm text-red-500">{errors.country}</p>
                                            )}
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* Banking Information */}
                        <TabsContent value="banking" className="space-y-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Bankverbindung</CardTitle>
                                    <CardDescription>
                                        Bankdaten für Rechnungen und Zahlungen
                                    </CardDescription>
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
                                        {errors.bank_name && (
                                            <p className="text-sm text-red-500">{errors.bank_name}</p>
                                        )}
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
                                        {errors.bank_iban && (
                                            <p className="text-sm text-red-500">{errors.bank_iban}</p>
                                        )}
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
                                        {errors.bank_bic && (
                                            <p className="text-sm text-red-500">{errors.bank_bic}</p>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        {/* Settings */}
                        <TabsContent value="settings" className="space-y-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Einstellungen</CardTitle>
                                    <CardDescription>
                                        Weitere Einstellungen für diese Firma
                                    </CardDescription>
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
