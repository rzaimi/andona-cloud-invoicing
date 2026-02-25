"use client"

import type React from "react"

import { Head, Link, useForm, usePage } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { ArrowLeft, Building2, User, FileText, Download } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem, Customer } from "@/types"

interface Document {
    id: string
    name: string
    original_filename: string
    file_size: number
    mime_type: string
    category: string
    description?: string
    tags?: string[]
    link_type?: string
    created_at: string
}

interface CustomersEditProps {
    customer: Customer & { documents?: Document[] }
}

export default function CustomersEdit() {
    const { customer } = usePage<CustomersEditProps>().props

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Kunden", href: "/customers" },
        { title: customer.name },
    ]

    const { data, setData, put, processing, errors } = useForm({
        name: customer.name || "",
        email: customer.email || "",
        phone: customer.phone || "",
        address: customer.address || "",
        postal_code: customer.postal_code || "",
        city: customer.city || "",
        country: customer.country || "Deutschland",
        tax_number: customer.tax_number || "",
        vat_number: customer.vat_number || "",
        contact_person: customer.contact_person || "",
        customer_type: customer.customer_type || "business",
        status: customer.status || "active",
    })

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        put(`/customers/${customer.id}`)
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
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Kunde bearbeiten - ${customer.name}`} />

            <div className="flex flex-1 flex-col gap-6">
                {/* Header */}
                <div className="flex items-center gap-4">
                    <Link href="/customers">
                        <Button variant="outline" size="sm">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Zurück
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold text-foreground">Kunde bearbeiten</h1>
                        <p className="text-muted-foreground">{customer.name}</p>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Customer Type */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Kundentyp</CardTitle>
                            <CardDescription>Typ des Kunden</CardDescription>
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
                                    <Input id="name" value={data.name} onChange={(e) => setData("name", e.target.value)} required />
                                    {errors.name && <p className="text-red-600 text-sm">{errors.name}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="email">E-Mail-Adresse *</Label>
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
                                    <Label htmlFor="phone">Telefonnummer</Label>
                                    <Input id="phone" value={data.phone} onChange={(e) => setData("phone", e.target.value)} />
                                    {errors.phone && <p className="text-red-600 text-sm">{errors.phone}</p>}
                                </div>

                                {data.customer_type === "business" && (
                                    <div className="space-y-2">
                                        <Label htmlFor="contact_person">Ansprechpartner</Label>
                                        <Input
                                            id="contact_person"
                                            value={data.contact_person}
                                            onChange={(e) => setData("contact_person", e.target.value)}
                                        />
                                        {errors.contact_person && <p className="text-red-600 text-sm">{errors.contact_person}</p>}
                                    </div>
                                )}

                                <div className="space-y-2">
                                    <Label htmlFor="status">Status</Label>
                                    <Select
                                        value={data.status}
                                        onValueChange={(value) => setData("status", value as "active" | "inactive")}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="active">Aktiv</SelectItem>
                                            <SelectItem value="inactive">Inaktiv</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.status && <p className="text-red-600 text-sm">{errors.status}</p>}
                                </div>
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
                                    />
                                    {errors.postal_code && <p className="text-red-600 text-sm">{errors.postal_code}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="city">Stadt</Label>
                                    <Input id="city" value={data.city} onChange={(e) => setData("city", e.target.value)} />
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
                                        />
                                        {errors.tax_number && <p className="text-red-600 text-sm">{errors.tax_number}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="vat_number">Umsatzsteuer-Identifikationsnummer</Label>
                                        <Input
                                            id="vat_number"
                                            value={data.vat_number}
                                            onChange={(e) => setData("vat_number", e.target.value)}
                                        />
                                        {errors.vat_number && <p className="text-red-600 text-sm">{errors.vat_number}</p>}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Documents */}
                    {customer.documents && customer.documents.length > 0 && (
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle>Verknüpfte Dokumente</CardTitle>
                                        <CardDescription>Mit diesem Kunden verknüpfte Dokumente</CardDescription>
                                    </div>
                                    <Link href="/settings/documents">
                                        <Button variant="outline" size="sm">
                                            <FileText className="mr-2 h-4 w-4" />
                                            Alle Dokumente
                                        </Button>
                                    </Link>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-2">
                                    {customer.documents.map((document) => (
                                        <div
                                            key={document.id}
                                            className="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50"
                                        >
                                            <div className="flex items-center gap-3 flex-1 min-w-0">
                                                <FileText className="h-5 w-5 text-gray-400 shrink-0" />
                                                <div className="flex-1 min-w-0">
                                                    <p className="font-medium text-sm truncate">{document.name}</p>
                                                    <p className="text-xs text-gray-500">
                                                        {new Date(document.created_at).toLocaleDateString('de-DE')}
                                                        {document.link_type && (
                                                            <> • {document.link_type}</>
                                                        )}
                                                    </p>
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-2 shrink-0">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => window.open(`/documents/${document.id}/download`, '_blank')}
                                                >
                                                    <Download className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Actions */}
                    <div className="flex justify-end space-x-2">
                        <Link href="/customers">
                            <Button type="button" variant="outline">
                                Abbrechen
                            </Button>
                        </Link>
                        <Button type="submit" disabled={processing}>
                            {processing ? "Wird gespeichert..." : "Änderungen speichern"}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    )
}
