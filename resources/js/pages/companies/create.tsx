"use client"

import type React from "react"
import { Head, Link, useForm } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { ArrowLeft, Building2, Save, Upload } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import { route } from "ziggy-js"
import { Alert, AlertDescription } from "@/components/ui/alert"

export default function CompaniesCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: "",
        email: "",
        phone: "",
        address: "",
        postal_code: "",
        city: "",
        country: "Deutschland",
        tax_number: "",
        vat_number: "",
        commercial_register: "",
        managing_director: "",
        bank_name: "",
        bank_iban: "",
        bank_bic: "",
        website: "",
        logo: null as File | null,
    })

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        post(route("companies.store"), {
            forceFormData: true,
            preserveScroll: true,
        })
    }

    const handleLogoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0]
        if (file) {
            setData("logo", file)
        }
    }

    const countries = [
        "Deutschland",
        "Österreich",
        "Schweiz",
        "Niederlande",
        "Belgien",
        "Frankreich",
        "Italien",
        "Spanien",
        "Polen",
        "Tschechien",
        "Großbritannien",
        "USA",
    ]

    return (
        <AppLayout
            breadcrumbs={[
                { title: "Dashboard", href: "/dashboard" },
                { title: "Firmenverwaltung", href: route("companies.index") },
                { title: "Neue Firma" },
            ]}
        >
            <Head title="Neue Firma erstellen" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center gap-4">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={route("companies.index")}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Zurück
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-1xl font-bold text-gray-900">Neue Firma erstellen</h1>
                        <p className="text-gray-600">Erstellen Sie eine neue Firma im System</p>
                    </div>
                </div>

                {/* Error Alert */}
                {Object.keys(errors).length > 0 && (
                    <Alert variant="destructive">
                        <AlertDescription>
                            Bitte korrigieren Sie die Fehler im Formular.
                        </AlertDescription>
                    </Alert>
                )}

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Basic Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Grundinformationen</CardTitle>
                            <CardDescription>Grundlegende Informationen zur Firma</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="name">
                                        Firmenname <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData("name", e.target.value)}
                                        className={errors.name ? "border-red-500" : ""}
                                        required
                                    />
                                    {errors.name && <p className="text-sm text-red-500 mt-1">{errors.name}</p>}
                                </div>

                                <div>
                                    <Label htmlFor="email">
                                        E-Mail <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData("email", e.target.value)}
                                        className={errors.email ? "border-red-500" : ""}
                                        required
                                    />
                                    {errors.email && <p className="text-sm text-red-500 mt-1">{errors.email}</p>}
                                </div>

                                <div>
                                    <Label htmlFor="phone">Telefon</Label>
                                    <Input
                                        id="phone"
                                        type="tel"
                                        value={data.phone}
                                        onChange={(e) => setData("phone", e.target.value)}
                                        className={errors.phone ? "border-red-500" : ""}
                                    />
                                    {errors.phone && <p className="text-sm text-red-500 mt-1">{errors.phone}</p>}
                                </div>

                                <div>
                                    <Label htmlFor="website">Website</Label>
                                    <Input
                                        id="website"
                                        type="url"
                                        value={data.website}
                                        onChange={(e) => setData("website", e.target.value)}
                                        placeholder="https://example.com"
                                        className={errors.website ? "border-red-500" : ""}
                                    />
                                    {errors.website && <p className="text-sm text-red-500 mt-1">{errors.website}</p>}
                                </div>
                            </div>

                            <div>
                                <Label htmlFor="address">Adresse</Label>
                                <Textarea
                                    id="address"
                                    value={data.address}
                                    onChange={(e) => setData("address", e.target.value)}
                                    rows={2}
                                    className={errors.address ? "border-red-500" : ""}
                                />
                                {errors.address && <p className="text-sm text-red-500 mt-1">{errors.address}</p>}
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <Label htmlFor="postal_code">Postleitzahl</Label>
                                    <Input
                                        id="postal_code"
                                        value={data.postal_code}
                                        onChange={(e) => setData("postal_code", e.target.value)}
                                        className={errors.postal_code ? "border-red-500" : ""}
                                    />
                                    {errors.postal_code && <p className="text-sm text-red-500 mt-1">{errors.postal_code}</p>}
                                </div>

                                <div>
                                    <Label htmlFor="city">Stadt</Label>
                                    <Input
                                        id="city"
                                        value={data.city}
                                        onChange={(e) => setData("city", e.target.value)}
                                        className={errors.city ? "border-red-500" : ""}
                                    />
                                    {errors.city && <p className="text-sm text-red-500 mt-1">{errors.city}</p>}
                                </div>

                                <div>
                                    <Label htmlFor="country">
                                        Land <span className="text-red-500">*</span>
                                    </Label>
                                    <select
                                        id="country"
                                        value={data.country}
                                        onChange={(e) => setData("country", e.target.value)}
                                        className={`flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 ${
                                            errors.country ? "border-red-500" : ""
                                        }`}
                                        required
                                    >
                                        {countries.map((country) => (
                                            <option key={country} value={country}>
                                                {country}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.country && <p className="text-sm text-red-500 mt-1">{errors.country}</p>}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Tax & Legal Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Steuer- und Rechtsinformationen</CardTitle>
                            <CardDescription>Steuernummern und rechtliche Angaben</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="tax_number">Steuernummer</Label>
                                    <Input
                                        id="tax_number"
                                        value={data.tax_number}
                                        onChange={(e) => setData("tax_number", e.target.value)}
                                        className={errors.tax_number ? "border-red-500" : ""}
                                    />
                                    {errors.tax_number && <p className="text-sm text-red-500 mt-1">{errors.tax_number}</p>}
                                </div>

                                <div>
                                    <Label htmlFor="vat_number">Umsatzsteuer-ID (USt-IdNr.)</Label>
                                    <Input
                                        id="vat_number"
                                        value={data.vat_number}
                                        onChange={(e) => setData("vat_number", e.target.value)}
                                        className={errors.vat_number ? "border-red-500" : ""}
                                    />
                                    {errors.vat_number && <p className="text-sm text-red-500 mt-1">{errors.vat_number}</p>}
                                </div>

                                <div>
                                    <Label htmlFor="commercial_register">Handelsregister</Label>
                                    <Input
                                        id="commercial_register"
                                        value={data.commercial_register}
                                        onChange={(e) => setData("commercial_register", e.target.value)}
                                        className={errors.commercial_register ? "border-red-500" : ""}
                                    />
                                    {errors.commercial_register && (
                                        <p className="text-sm text-red-500 mt-1">{errors.commercial_register}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="managing_director">Geschäftsführer</Label>
                                    <Input
                                        id="managing_director"
                                        value={data.managing_director}
                                        onChange={(e) => setData("managing_director", e.target.value)}
                                        className={errors.managing_director ? "border-red-500" : ""}
                                    />
                                    {errors.managing_director && (
                                        <p className="text-sm text-red-500 mt-1">{errors.managing_director}</p>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Banking Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Bankverbindung</CardTitle>
                            <CardDescription>Bankinformationen für Zahlungen</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <Label htmlFor="bank_name">Bankname</Label>
                                    <Input
                                        id="bank_name"
                                        value={data.bank_name}
                                        onChange={(e) => setData("bank_name", e.target.value)}
                                        className={errors.bank_name ? "border-red-500" : ""}
                                    />
                                    {errors.bank_name && <p className="text-sm text-red-500 mt-1">{errors.bank_name}</p>}
                                </div>

                                <div>
                                    <Label htmlFor="bank_iban">IBAN</Label>
                                    <Input
                                        id="bank_iban"
                                        value={data.bank_iban}
                                        onChange={(e) => setData("bank_iban", e.target.value.toUpperCase())}
                                        placeholder="DE89 3704 0044 0532 0130 00"
                                        className={errors.bank_iban ? "border-red-500" : ""}
                                    />
                                    {errors.bank_iban && <p className="text-sm text-red-500 mt-1">{errors.bank_iban}</p>}
                                </div>

                                <div>
                                    <Label htmlFor="bank_bic">BIC</Label>
                                    <Input
                                        id="bank_bic"
                                        value={data.bank_bic}
                                        onChange={(e) => setData("bank_bic", e.target.value.toUpperCase())}
                                        placeholder="COBADEFFXXX"
                                        className={errors.bank_bic ? "border-red-500" : ""}
                                    />
                                    {errors.bank_bic && <p className="text-sm text-red-500 mt-1">{errors.bank_bic}</p>}
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

                    {/* Actions */}
                    <div className="flex items-center justify-end gap-4">
                        <Button variant="outline" type="button" asChild>
                            <Link href={route("companies.index")}>Abbrechen</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Save className="mr-2 h-4 w-4" />
                            {processing ? "Wird erstellt..." : "Firma erstellen"}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    )
}
