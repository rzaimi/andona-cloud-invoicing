"use client"

import type React from "react"
import { Head, Link, useForm } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { ArrowLeft, Building2, User } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem } from "@/types"
import { route } from "ziggy-js"

export default function CustomersCreate({ breadcrumbs }: { breadcrumbs?: BreadcrumbItem[] }) {
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
        contact_person: "",
        customer_type: "business" as "business" | "private",
    })

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        post(route("customers.store"))
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
    ]

    return (
        <AppLayout breadcrumbs={breadcrumbs || [
            { title: "Dashboard", href: route("dashboard") },
            { title: "Kunden", href: route("customers.index") },
            { title: "Neuer Kunde" },
        ]}>
            <Head title="Neuer Kunde" />

            <div className="flex flex-1 flex-col gap-6">
                {/* Header */}
                <div className="flex items-center gap-4">
                    <Link href={route("customers.index")}>
                        <Button variant="outline" size="sm">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Zurück
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-1xl font-bold text-foreground">Neuer Kunde</h1>
                        <p className="text-muted-foreground">Erstellen Sie einen neuen Kunden</p>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Customer Type */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Kundentyp</CardTitle>
                            <CardDescription>Wählen Sie den Typ des Kunden aus</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-2 gap-4">
                                <div
                                    className={`p-4 border rounded-lg cursor-pointer transition-colors ${
                                        data.customer_type === "business"
                                            ? "border-blue-500 bg-blue-50"
                                            : "border-gray-200 hover:border-gray-300"
                                    }`}
                                    onClick={() => setData("customer_type", "business")}
                                >
                                    <div className="flex items-center gap-3">
                                        <Building2 className="h-6 w-6" />
                                        <div>
                                            <h3 className="font-medium">Unternehmen</h3>
                                            <p className="text-sm text-gray-500">Geschäftskunde mit Steuernummer</p>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    className={`p-4 border rounded-lg cursor-pointer transition-colors ${
                                        data.customer_type === "private"
                                            ? "border-blue-500 bg-blue-50"
                                            : "border-gray-200 hover:border-gray-300"
                                    }`}
                                    onClick={() => setData("customer_type", "private")}
                                >
                                    <div className="flex items-center gap-3">
                                        <User className="h-6 w-6" />
                                        <div>
                                            <h3 className="font-medium">Privatkunde</h3>
                                            <p className="text-sm text-gray-500">Privatperson ohne Steuernummer</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {errors.customer_type && <p className="text-red-600 text-sm mt-2">{errors.customer_type}</p>}
                        </CardContent>
                    </Card>

                    {/* Basic Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Grundinformationen</CardTitle>
                            <CardDescription>Grundlegende Informationen über den Kunden</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="name">{data.customer_type === "business" ? "Firmenname" : "Name"} *</Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData("name", e.target.value)}
                                        placeholder={data.customer_type === "business" ? "z.B. Mustermann GmbH" : "z.B. Max Mustermann"}
                                        required
                                    />
                                    {errors.name && <p className="text-red-600 text-sm">{errors.name}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="email">E-Mail-Adresse *</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData("email", e.target.value)}
                                        placeholder="z.B. kunde@beispiel.de"
                                        required
                                    />
                                    {errors.email && <p className="text-red-600 text-sm">{errors.email}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="phone">Telefonnummer</Label>
                                    <Input
                                        id="phone"
                                        value={data.phone}
                                        onChange={(e) => setData("phone", e.target.value)}
                                        placeholder="z.B. +49 30 12345678"
                                    />
                                    {errors.phone && <p className="text-red-600 text-sm">{errors.phone}</p>}
                                </div>

                                {data.customer_type === "business" && (
                                    <div className="space-y-2">
                                        <Label htmlFor="contact_person">Ansprechpartner</Label>
                                        <Input
                                            id="contact_person"
                                            value={data.contact_person}
                                            onChange={(e) => setData("contact_person", e.target.value)}
                                            placeholder="z.B. Herr Müller"
                                        />
                                        {errors.contact_person && <p className="text-red-600 text-sm">{errors.contact_person}</p>}
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Address Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Adressinformationen</CardTitle>
                            <CardDescription>Rechnungsadresse des Kunden</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="address">Straße und Hausnummer</Label>
                                <Textarea
                                    id="address"
                                    value={data.address}
                                    onChange={(e) => setData("address", e.target.value)}
                                    placeholder="z.B. Musterstraße 123"
                                    rows={2}
                                />
                                {errors.address && <p className="text-red-600 text-sm">{errors.address}</p>}
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="postal_code">Postleitzahl</Label>
                                    <Input
                                        id="postal_code"
                                        value={data.postal_code}
                                        onChange={(e) => setData("postal_code", e.target.value)}
                                        placeholder="z.B. 12345"
                                    />
                                    {errors.postal_code && <p className="text-red-600 text-sm">{errors.postal_code}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="city">Stadt</Label>
                                    <Input
                                        id="city"
                                        value={data.city}
                                        onChange={(e) => setData("city", e.target.value)}
                                        placeholder="z.B. Berlin"
                                    />
                                    {errors.city && <p className="text-red-600 text-sm">{errors.city}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="country">Land</Label>
                                    <Select value={data.country} onValueChange={(value) => setData("country", value)}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {countries.map((country) => (
                                                <SelectItem key={country} value={country}>
                                                    {country}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.country && <p className="text-red-600 text-sm">{errors.country}</p>}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Tax Information */}
                    {data.customer_type === "business" && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Steuerinformationen</CardTitle>
                                <CardDescription>Steuernummern für Geschäftskunden</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="tax_number">Steuernummer</Label>
                                        <Input
                                            id="tax_number"
                                            value={data.tax_number}
                                            onChange={(e) => setData("tax_number", e.target.value)}
                                            placeholder="z.B. 12/345/67890"
                                        />
                                        {errors.tax_number && <p className="text-red-600 text-sm">{errors.tax_number}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="vat_number">Umsatzsteuer-Identifikationsnummer</Label>
                                        <Input
                                            id="vat_number"
                                            value={data.vat_number}
                                            onChange={(e) => setData("vat_number", e.target.value)}
                                            placeholder="z.B. DE123456789"
                                        />
                                        {errors.vat_number && <p className="text-red-600 text-sm">{errors.vat_number}</p>}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Actions */}
                    <div className="flex justify-end space-x-2">
                        <Link href={route("customers.index")}>
                            <Button type="button" variant="outline">
                                Abbrechen
                            </Button>
                        </Link>
                        <Button type="submit" disabled={processing}>
                            {processing ? "Wird erstellt..." : "Kunde erstellen"}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    )
}
