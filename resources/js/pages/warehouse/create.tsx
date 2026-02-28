"use client"

import type React from "react"
import { Head, Link, useForm } from "@inertiajs/react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Checkbox } from "@/components/ui/checkbox"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { ArrowLeft, Save, AlertCircle } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem } from "@/types"

interface WarehouseFormData {
    name: string
    description: string
    street: string
    street_number: string
    postal_code: string
    city: string
    state: string
    country: string
    contact_person: string
    phone: string
    email: string
    is_default: boolean
    is_active: boolean
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Lager", href: "/warehouses" },
    { title: "Neues Lager" },
]

const countries = [
    { value: "DE", label: "Deutschland" },
    { value: "AT", label: t('pages.customers.austria')},
    { value: "CH", label: "Schweiz" },
    { value: "NL", label: "Niederlande" },
    { value: "BE", label: "Belgien" },
    { value: "FR", label: "Frankreich" },
    { value: "IT", label: "Italien" },
    { value: "ES", label: "Spanien" },
    { value: "PL", label: "Polen" },
    { value: "CZ", label: "Tschechien" },
]

export default function WarehouseCreate() {
    const { data, setData, post, processing, errors } = useForm<WarehouseFormData>({
        name: "",
        description: "",
        street: "",
        street_number: "",
        postal_code: "",
        city: "",
        state: "",
        country: "DE",
        contact_person: "",
        phone: "",
        email: "",
        is_default: false,
        is_active: true,
    })

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        post("/warehouses")
    }

    const hasErrors = Object.keys(errors).length > 0

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Neues Lager erstellen" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Button variant="ghost" asChild>
                            <Link href="/warehouses">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                {t('common.back')}
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight">Neues Lager erstellen</h1>
                            <p className="text-muted-foreground">Erstellen Sie einen neuen Lagerstandort</p>
                        </div>
                    </div>
                </div>

                {/* Error Alert */}
                {hasErrors && (
                    <Alert variant="destructive">
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>
                            <div className="font-medium mb-2">Bitte korrigieren Sie die folgenden Fehler:</div>
                            <ul className="list-disc list-inside space-y-1">
                                {Object.entries(errors).map(([field, message]) => (
                                    <li key={field} className="text-sm">
                                        <strong>{getFieldLabel(field)}:</strong> {message}
                                    </li>
                                ))}
                            </ul>
                        </AlertDescription>
                    </Alert>
                )}

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid gap-6 md:grid-cols-2">
                        {/* Basic Information */}
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('pages.products.basicInfo')}</CardTitle>
                                <CardDescription>{t('pages.warehouse.infoDesc')}</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="name">Lagername *</Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData("name", e.target.value)}
                                        placeholder={t('pages.warehouse.namePlaceholder')}
                                        required
                                        className={errors.name ? "border-red-500" : ""}
                                    />
                                    {errors.name && <p className="text-sm text-red-600">{errors.name}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="contact_person">Ansprechpartner</Label>
                                    <Input
                                        id="contact_person"
                                        value={data.contact_person}
                                        onChange={(e) => setData("contact_person", e.target.value)}
                                        placeholder="z.B. Max Mustermann"
                                        className={errors.contact_person ? "border-red-500" : ""}
                                    />
                                    {errors.contact_person && <p className="text-sm text-red-600">{errors.contact_person}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="description">{t('common.description')}</Label>
                                    <Textarea
                                        id="description"
                                        value={data.description}
                                        onChange={(e) => setData("description", e.target.value)}
                                        placeholder="Beschreibung des Lagers..."
                                        rows={3}
                                        className={errors.description ? "border-red-500" : ""}
                                    />
                                    {errors.description && <p className="text-sm text-red-600">{errors.description}</p>}
                                </div>

                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="is_active"
                                        checked={data.is_active}
                                        onCheckedChange={(checked) => setData("is_active", !!checked)}
                                    />
                                    <Label htmlFor="is_active" className="font-normal cursor-pointer">Lager ist aktiv</Label>
                                </div>

                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="is_default"
                                        checked={data.is_default}
                                        onCheckedChange={(checked) => setData("is_default", !!checked)}
                                    />
                                    <Label htmlFor="is_default" className="font-normal cursor-pointer">Als Standardlager festlegen</Label>
                                </div>

                                {data.is_default && (
                                    <div className="rounded-lg bg-blue-50 p-3 border border-blue-200">
                                        <p className="text-sm text-blue-800">
                                            <strong>Hinweis:</strong> Andere Lager werden automatisch als nicht-Standard markiert.
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Address Information */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Adresse</CardTitle>
                                <CardDescription>Standort und Adresse des Lagers</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="street">{t('settings.street')}</Label>
                                        <Input
                                            id="street"
                                            value={data.street}
                                            onChange={(e) => setData("street", e.target.value)}
                                            placeholder={t('settings.streetPlaceholder')}
                                            className={errors.street ? "border-red-500" : ""}
                                        />
                                        {errors.street && <p className="text-sm text-red-600">{errors.street}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="street_number">Hausnummer</Label>
                                        <Input
                                            id="street_number"
                                            value={data.street_number}
                                            onChange={(e) => setData("street_number", e.target.value)}
                                            placeholder="123"
                                            className={errors.street_number ? "border-red-500" : ""}
                                        />
                                        {errors.street_number && <p className="text-sm text-red-600">{errors.street_number}</p>}
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="postal_code">Postleitzahl</Label>
                                        <Input
                                            id="postal_code"
                                            value={data.postal_code}
                                            onChange={(e) => setData("postal_code", e.target.value)}
                                            placeholder="80331"
                                            className={errors.postal_code ? "border-red-500" : ""}
                                        />
                                        {errors.postal_code && <p className="text-sm text-red-600">{errors.postal_code}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="city">Stadt</Label>
                                        <Input
                                            id="city"
                                            value={data.city}
                                            onChange={(e) => setData("city", e.target.value)}
                                            placeholder={t('settings.cityPlaceholder')}
                                            className={errors.city ? "border-red-500" : ""}
                                        />
                                        {errors.city && <p className="text-sm text-red-600">{errors.city}</p>}
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="state">Bundesland</Label>
                                        <Input
                                            id="state"
                                            value={data.state}
                                            onChange={(e) => setData("state", e.target.value)}
                                            placeholder="Bayern"
                                            className={errors.state ? "border-red-500" : ""}
                                        />
                                        {errors.state && <p className="text-sm text-red-600">{errors.state}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="country">Land</Label>
                                        <select
                                            id="country"
                                            value={data.country}
                                            onChange={(e) => setData("country", e.target.value)}
                                            className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                        >
                                            {countries.map((c) => (
                                                <option key={c.value} value={c.value}>{c.label}</option>
                                            ))}
                                        </select>
                                        {errors.country && <p className="text-sm text-red-600">{errors.country}</p>}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Contact Information */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Kontaktinformationen</CardTitle>
                                <CardDescription>{t('pages.warehouse.contactDesc')}</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="phone">Telefon</Label>
                                        <Input
                                            id="phone"
                                            type="tel"
                                            value={data.phone}
                                            onChange={(e) => setData("phone", e.target.value)}
                                            placeholder="+49 89 123456"
                                            className={errors.phone ? "border-red-500" : ""}
                                        />
                                        {errors.phone && <p className="text-sm text-red-600">{errors.phone}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="email">E-Mail</Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            value={data.email}
                                            onChange={(e) => setData("email", e.target.value)}
                                            placeholder="lager@firma.de"
                                            className={errors.email ? "border-red-500" : ""}
                                        />
                                        {errors.email && <p className="text-sm text-red-600">{errors.email}</p>}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Actions */}
                    <div className="flex items-center justify-end space-x-4">
                        <Button variant="outline" asChild>
                            <Link href="/warehouses">{t('common.cancel')}</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Save className="mr-2 h-4 w-4" />
                            {processing ? "Speichern..." : "Lager erstellen"}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    )
}

function getFieldLabel(field: string): string {
    const labels: Record<string, string> = {
        name: "Lagername",
        description: "Beschreibung",
        street: t('settings.street'),
        street_number: "Hausnummer",
        postal_code: "Postleitzahl",
        city: "Stadt",
        state: "Bundesland",
        country: "Land",
        contact_person: "Ansprechpartner",
        phone: "Telefon",
        email: "E-Mail",
        is_default: "Standardlager",
        is_active: "Status",
    }
    return labels[field] || field
}
