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
import { ArrowLeft, Save, AlertCircle } from 'lucide-react'
import AppLayout from "@/layouts/app-layout"
import type { User } from "@/types"

interface WarehouseCreateProps {
    user: User
}

interface WarehouseFormData {
    name: string
    description: string
    address: string
    postal_code: string
    city: string
    country: string
    contact_person: string
    phone: string
    email: string
    is_default: boolean
    is_active: boolean
}

export default function WarehouseCreate({ user }: WarehouseCreateProps) {
    const { data, setData, post, processing, errors } = useForm<WarehouseFormData>({
        name: "",
        description: "",
        address: "",
        postal_code: "",
        city: "",
        country: "Deutschland",
        contact_person: "",
        phone: "",
        email: "",
        is_default: false,
        is_active: true,
    })

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        post("/warehouse")
    }

    // Check if there are any errors
    const hasErrors = Object.keys(errors).length > 0

    return (
        <AppLayout user={user}>
            <Head title="Neues Lager erstellen" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Button variant="ghost" asChild>
                            <Link href="/warehouse">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Zurück
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-1xl font-bold tracking-tight">Neues Lager erstellen</h1>
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
                    {/* Basic Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Grundinformationen</CardTitle>
                            <CardDescription>Grundlegende Informationen über das Lager</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="grid gap-6 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="name">Lagername *</Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData("name", e.target.value)}
                                        placeholder="z.B. Hauptlager München"
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
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">Beschreibung</Label>
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
                        </CardContent>
                    </Card>

                    {/* Address Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Adressinformationen</CardTitle>
                            <CardDescription>Standort und Adresse des Lagers</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="address">Straße und Hausnummer</Label>
                                <Input
                                    id="address"
                                    value={data.address}
                                    onChange={(e) => setData("address", e.target.value)}
                                    placeholder="z.B. Musterstraße 123"
                                    className={errors.address ? "border-red-500" : ""}
                                />
                                {errors.address && <p className="text-sm text-red-600">{errors.address}</p>}
                            </div>

                            <div className="grid gap-6 md:grid-cols-3">
                                <div className="space-y-2">
                                    <Label htmlFor="postal_code">Postleitzahl</Label>
                                    <Input
                                        id="postal_code"
                                        value={data.postal_code}
                                        onChange={(e) => setData("postal_code", e.target.value)}
                                        placeholder="z.B. 80331"
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
                                        placeholder="z.B. München"
                                        className={errors.city ? "border-red-500" : ""}
                                    />
                                    {errors.city && <p className="text-sm text-red-600">{errors.city}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="country">Land</Label>
                                    <Input
                                        id="country"
                                        value={data.country}
                                        onChange={(e) => setData("country", e.target.value)}
                                        placeholder="Deutschland"
                                        className={errors.country ? "border-red-500" : ""}
                                    />
                                    {errors.country && <p className="text-sm text-red-600">{errors.country}</p>}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Contact Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Kontaktinformationen</CardTitle>
                            <CardDescription>Telefon und E-Mail für das Lager</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="grid gap-6 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="phone">Telefon</Label>
                                    <Input
                                        id="phone"
                                        value={data.phone}
                                        onChange={(e) => setData("phone", e.target.value)}
                                        placeholder="z.B. +49 89 123456"
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
                                        placeholder="z.B. lager@firma.de"
                                        className={errors.email ? "border-red-500" : ""}
                                    />
                                    {errors.email && <p className="text-sm text-red-600">{errors.email}</p>}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Settings */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Einstellungen</CardTitle>
                            <CardDescription>Lagereinstellungen und Status</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="space-y-4">
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="is_default"
                                        checked={data.is_default}
                                        onCheckedChange={(checked) => setData("is_default", !!checked)}
                                    />
                                    <Label htmlFor="is_default">Als Standardlager festlegen</Label>
                                </div>

                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="is_active"
                                        checked={data.is_active}
                                        onCheckedChange={(checked) => setData("is_active", !!checked)}
                                    />
                                    <Label htmlFor="is_active">Lager ist aktiv</Label>
                                </div>
                            </div>

                            {data.is_default && (
                                <div className="rounded-lg bg-blue-50 p-4 border border-blue-200">
                                    <p className="text-sm text-blue-800">
                                        <strong>Hinweis:</strong> Dieses Lager wird als Standardlager für neue Produkte verwendet. Andere
                                        Lager werden automatisch als nicht-Standard markiert.
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Actions */}
                    <div className="flex items-center justify-end space-x-4">
                        <Button variant="outline" asChild>
                            <Link href="/warehouse">Abbrechen</Link>
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

// Helper function to get field labels in German
function getFieldLabel(field: string): string {
    const labels: Record<string, string> = {
        name: "Lagername",
        description: "Beschreibung",
        address: "Adresse",
        postal_code: "Postleitzahl",
        city: "Stadt",
        country: "Land",
        contact_person: "Ansprechpartner",
        phone: "Telefon",
        email: "E-Mail",
        is_default: "Standardlager",
        is_active: "Status",
    }
    return labels[field] || field
}
