"use client"

import { useState } from "react"
import { useForm } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Checkbox } from "@/components/ui/checkbox"
import { Save, Upload, X } from "lucide-react"
import { route } from "ziggy-js"

interface CompanyInfoTabProps {
    company: any
}

export default function CompanyInfoTab({ company }: CompanyInfoTabProps) {
    const { data, setData, post, processing, errors, transform } = useForm({
        name: company?.name || '',
        email: company?.email || '',
        phone: company?.phone || '',
        fax: company?.fax || '',
        address: company?.address || '',
        postal_code: company?.postal_code || '',
        city: company?.city || '',
        country: company?.country || 'Deutschland',
        tax_number: company?.tax_number || '',
        tax_office: company?.tax_office || '',
        vat_number: company?.vat_number || '',
        is_small_business: company?.is_small_business || false,
        commercial_register: company?.commercial_register || '',
        managing_director: company?.managing_director || '',
        website: company?.website || '',
        logo: null as File | null,
    })

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
        // Use POST + method spoofing for reliable file uploads
        transform((data) => ({ ...data, _method: "put", remove_logo: removedLogo ? "1" : "0" }))
        post(route("settings.company-info.update"), { 
            forceFormData: true, 
            preserveScroll: true 
        })
    }

    const currentLogo = company?.logo
    // Backend already returns a full Storage URL (e.g. /storage/tenants/.../logo/file.jpg)
    // Use logoPreview for newly selected file, otherwise existing logo (unless removed)
    const displayLogo = logoPreview || (removedLogo ? null : currentLogo) || null

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            {/* Logo Section */}
            <Card>
                <CardHeader>
                    <CardTitle>Firmenlogo</CardTitle>
                    <CardDescription>
                        Laden Sie Ihr Firmenlogo hoch. Empfohlen: PNG oder JPG, maximal 2MB
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="flex items-start gap-4">
                        {displayLogo && (
                            <div className="relative">
                                <img 
                                    src={displayLogo} 
                                    alt="Company Logo" 
                                    className="h-24 w-24 object-contain rounded border"
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
                                    accept="image/*"
                                    className="hidden"
                                    onChange={handleLogoChange}
                                />
                            </Label>
                            {errors.logo && <p className="text-sm text-red-600 mt-2">{errors.logo}</p>}
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Basic Information */}
            <Card>
                <CardHeader>
                    <CardTitle>Grundinformationen</CardTitle>
                    <CardDescription>Grundlegende Informationen über Ihre Firma</CardDescription>
                </CardHeader>
                <CardContent className="grid gap-6 md:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="name">Firmenname *</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData("name", e.target.value)}
                            required
                        />
                        {errors.name && <p className="text-red-600 text-sm">{errors.name}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="email">E-Mail *</Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData("email", e.target.value)}
                            required
                        />
                        {errors.email && <p className="text-red-600 text-sm">{errors.email}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="phone">Telefon</Label>
                        <Input
                            id="phone"
                            value={data.phone}
                            onChange={(e) => setData("phone", e.target.value)}
                        />
                        {errors.phone && <p className="text-red-600 text-sm">{errors.phone}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="fax">Fax</Label>
                        <Input
                            id="fax"
                            value={data.fax}
                            onChange={(e) => setData("fax", e.target.value)}
                        />
                        {errors.fax && <p className="text-red-600 text-sm">{errors.fax}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="website">Webseite</Label>
                        <Input
                            id="website"
                            type="url"
                            value={data.website}
                            onChange={(e) => setData("website", e.target.value)}
                            placeholder="https://example.com"
                        />
                        {errors.website && <p className="text-red-600 text-sm">{errors.website}</p>}
                    </div>

                    <div className="space-y-2 md:col-span-2">
                        <Label htmlFor="managing_director">Geschäftsführer</Label>
                        <Input
                            id="managing_director"
                            value={data.managing_director}
                            onChange={(e) => setData("managing_director", e.target.value)}
                        />
                        {errors.managing_director && <p className="text-red-600 text-sm">{errors.managing_director}</p>}
                    </div>
                </CardContent>
            </Card>

            {/* Address Information */}
            <Card>
                <CardHeader>
                    <CardTitle>Adresse</CardTitle>
                    <CardDescription>Firmenanschrift</CardDescription>
                </CardHeader>
                <CardContent className="grid gap-6 md:grid-cols-2">
                    <div className="space-y-2 md:col-span-2">
                        <Label htmlFor="address">Straße und Hausnummer</Label>
                        <Input
                            id="address"
                            value={data.address}
                            onChange={(e) => setData("address", e.target.value)}
                        />
                        {errors.address && <p className="text-red-600 text-sm">{errors.address}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="postal_code">Postleitzahl</Label>
                        <Input
                            id="postal_code"
                            value={data.postal_code}
                            onChange={(e) => setData("postal_code", e.target.value)}
                        />
                        {errors.postal_code && <p className="text-red-600 text-sm">{errors.postal_code}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="city">Stadt</Label>
                        <Input
                            id="city"
                            value={data.city}
                            onChange={(e) => setData("city", e.target.value)}
                        />
                        {errors.city && <p className="text-red-600 text-sm">{errors.city}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="country">Land *</Label>
                        <Select value={data.country} onValueChange={(value) => setData("country", value)}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="Deutschland">Deutschland</SelectItem>
                                <SelectItem value="Österreich">Österreich</SelectItem>
                                <SelectItem value="Schweiz">Schweiz</SelectItem>
                            </SelectContent>
                        </Select>
                        {errors.country && <p className="text-red-600 text-sm">{errors.country}</p>}
                    </div>
                </CardContent>
            </Card>

            {/* Tax Information */}
            <Card>
                <CardHeader>
                    <CardTitle>Steuerinformationen</CardTitle>
                    <CardDescription>Steuerliche Angaben Ihrer Firma</CardDescription>
                </CardHeader>
                <CardContent className="grid gap-6 md:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="tax_number">Steuernummer (St.Nr.)</Label>
                        <Input
                            id="tax_number"
                            value={data.tax_number}
                            onChange={(e) => setData("tax_number", e.target.value)}
                        />
                        {errors.tax_number && <p className="text-red-600 text-sm">{errors.tax_number}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="tax_office">Finanzamt</Label>
                        <Input
                            id="tax_office"
                            value={data.tax_office}
                            onChange={(e) => setData("tax_office", e.target.value)}
                            placeholder="z.B. Finanzamt Gießen"
                        />
                        {errors.tax_office && <p className="text-red-600 text-sm">{errors.tax_office}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="vat_number">Umsatzsteuer-ID</Label>
                        <Input
                            id="vat_number"
                            value={data.vat_number}
                            onChange={(e) => setData("vat_number", e.target.value)}
                            placeholder="DE123456789"
                        />
                        {errors.vat_number && <p className="text-red-600 text-sm">{errors.vat_number}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="commercial_register">Handelsregister</Label>
                        <Input
                            id="commercial_register"
                            value={data.commercial_register}
                            onChange={(e) => setData("commercial_register", e.target.value)}
                            placeholder="HRB 12345"
                        />
                        {errors.commercial_register && <p className="text-red-600 text-sm">{errors.commercial_register}</p>}
                    </div>

                    <div className="space-y-2 flex items-center gap-2">
                        <Checkbox
                            id="is_small_business"
                            checked={data.is_small_business}
                            onCheckedChange={(checked) => setData("is_small_business", checked as boolean)}
                        />
                        <Label htmlFor="is_small_business" className="cursor-pointer">
                            Kleinunternehmer (§19 UStG)
                        </Label>
                    </div>
                </CardContent>
            </Card>

            <div className="flex justify-end">
                <Button type="submit" disabled={processing}>
                    <Save className="mr-2 h-4 w-4" />
                    {processing ? "Speichert..." : "Firmendaten speichern"}
                </Button>
            </div>
        </form>
    )
}
