"use client"

import { Head, Link, useForm, router, usePage } from "@inertiajs/react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Checkbox } from "@/components/ui/checkbox"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { ArrowLeft, Save, AlertCircle } from "lucide-react"
import AppLayout from "@/layouts/app-layout"

interface Warehouse {
    id: string
    code: string
    name: string
    description?: string
    street?: string
    street_number?: string
    postal_code?: string
    city?: string
    state?: string
    country?: string
    contact_person?: string
    phone?: string
    email?: string
    is_default: boolean
    is_active: boolean
}

interface WarehouseEditProps {
    warehouse: Warehouse
}

export default function WarehouseEdit() {
    const { props } = usePage<WarehouseEditProps>()
    const { warehouse } = props
    const user = (props as any).auth?.user || (props as any).user

    const { data, setData, put, processing, errors } = useForm({
        name: warehouse.name || "",
        description: warehouse.description || "",
        street: warehouse.street || "",
        street_number: warehouse.street_number || "",
        postal_code: warehouse.postal_code || "",
        city: warehouse.city || "",
        state: warehouse.state || "",
        country: warehouse.country || "DE",
        contact_person: warehouse.contact_person || "",
        phone: warehouse.phone || "",
        email: warehouse.email || "",
        is_default: warehouse.is_default || false,
        is_active: warehouse.is_active !== undefined ? warehouse.is_active : true,
    })

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        put(`/warehouses/${warehouse.id}`)
    }

    const countries = [
        { value: "DE", label: "Deutschland" },
        { value: "AT", label: "Österreich" },
        { value: "CH", label: "Schweiz" },
        { value: "NL", label: "Niederlande" },
        { value: "BE", label: "Belgien" },
        { value: "FR", label: "Frankreich" },
        { value: "IT", label: "Italien" },
        { value: "ES", label: "Spanien" },
        { value: "PL", label: "Polen" },
        { value: "CZ", label: "Tschechien" },
    ]

    const hasErrors = Object.keys(errors).length > 0

    return (
        <AppLayout user={user}>
            <Head title={`Lager bearbeiten: ${warehouse.name}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Button variant="ghost" asChild>
                            <Link href={`/warehouses/${warehouse.id}`}>
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                {t('common.back')}
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-1xl font-bold tracking-tight">Lager bearbeiten</h1>
                            <p className="text-muted-foreground">{warehouse.name}</p>
                        </div>
                    </div>
                </div>

                {/* Errors */}
                {hasErrors && (
                    <Alert variant="destructive">
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>
                            Bitte korrigieren Sie die Fehler im Formular.
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
                                    <Label htmlFor="name">Name *</Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData("name", e.target.value)}
                                        placeholder="z.B. Hauptlager"
                                    />
                                    {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="description">{t('common.description')}</Label>
                                    <Textarea
                                        id="description"
                                        value={data.description}
                                        onChange={(e) => setData("description", e.target.value)}
                                        placeholder="Optionale Beschreibung des Lagers"
                                        rows={3}
                                    />
                                    {errors.description && <p className="text-sm text-red-500">{errors.description}</p>}
                                </div>

                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="is_active"
                                        checked={data.is_active}
                                        onCheckedChange={(checked) => setData("is_active", !!checked)}
                                    />
                                    <Label htmlFor="is_active" className="font-normal cursor-pointer">
                                        Aktiv
                                    </Label>
                                </div>

                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="is_default"
                                        checked={data.is_default}
                                        onCheckedChange={(checked) => setData("is_default", !!checked)}
                                    />
                                    <Label htmlFor="is_default" className="font-normal cursor-pointer">
                                        Als Standardlager markieren
                                    </Label>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Address Information */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Adresse</CardTitle>
                                <CardDescription>Standort des Lagers</CardDescription>
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
                                        />
                                        {errors.street && <p className="text-sm text-red-500">{errors.street}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="street_number">Hausnummer</Label>
                                        <Input
                                            id="street_number"
                                            value={data.street_number}
                                            onChange={(e) => setData("street_number", e.target.value)}
                                            placeholder="123"
                                        />
                                        {errors.street_number && <p className="text-sm text-red-500">{errors.street_number}</p>}
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="postal_code">PLZ</Label>
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
                                            placeholder="Musterstadt"
                                        />
                                        {errors.city && <p className="text-sm text-red-500">{errors.city}</p>}
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="state">Bundesland / Staat</Label>
                                        <Input
                                            id="state"
                                            value={data.state}
                                            onChange={(e) => setData("state", e.target.value)}
                                            placeholder="Bayern"
                                        />
                                        {errors.state && <p className="text-sm text-red-500">{errors.state}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="country">Land</Label>
                                        <select
                                            id="country"
                                            value={data.country}
                                            onChange={(e) => setData("country", e.target.value)}
                                            className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            {countries.map((country) => (
                                                <option key={country.value} value={country.value}>
                                                    {country.label}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.country && <p className="text-sm text-red-500">{errors.country}</p>}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Contact Information */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Kontaktinformationen</CardTitle>
                                <CardDescription>{t('pages.warehouse.contactPerson')}</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="contact_person">Ansprechpartner</Label>
                                    <Input
                                        id="contact_person"
                                        value={data.contact_person}
                                        onChange={(e) => setData("contact_person", e.target.value)}
                                        placeholder="Max Mustermann"
                                    />
                                    {errors.contact_person && <p className="text-sm text-red-500">{errors.contact_person}</p>}
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
                                    <Label htmlFor="email">E-Mail</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData("email", e.target.value)}
                                        placeholder="lager@example.com"
                                    />
                                    {errors.email && <p className="text-sm text-red-500">{errors.email}</p>}
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Actions */}
                    <div className="flex justify-end space-x-2">
                        <Button variant="outline" asChild>
                            <Link href={`/warehouses/${warehouse.id}`}>{t('common.cancel')}</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Save className="mr-2 h-4 w-4" />
                            {processing ? "Speichern..." : "Speichern"}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    )
}
