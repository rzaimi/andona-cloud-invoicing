import { useState, useEffect } from "react"
import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Building2, Mail, Phone, MapPin, Globe, FileText } from "lucide-react"

interface Step1Props {
    data: any
    updateData: (key: string, data: any) => void
}

export default function Step1CompanyInfo({ data, updateData }: Step1Props) {
    const [formData, setFormData] = useState(data.company_info || {
        name: "",
        email: "",
        phone: "",
        address: "",
        postal_code: "",
        city: "",
        country: "Deutschland",
        tax_number: "",
        vat_number: "",
        website: "",
    })

    useEffect(() => {
        updateData("company_info", formData)
    }, [formData])

    const handleChange = (field: string, value: string) => {
        setFormData({ ...formData, [field]: value })
    }

    return (
        <div className="space-y-6">
            <div className="grid gap-6 md:grid-cols-2">
                {/* Company Name */}
                <div className="space-y-2 md:col-span-2">
                    <Label htmlFor="name" className="flex items-center gap-2">
                        <Building2 className="h-4 w-4" />
                        Firmenname *
                    </Label>
                    <Input
                        id="name"
                        value={formData.name}
                        onChange={(e) => handleChange("name", e.target.value)}
                        placeholder="z.B. Muster GmbH"
                        required
                    />
                </div>

                {/* Email */}
                <div className="space-y-2">
                    <Label htmlFor="email" className="flex items-center gap-2">
                        <Mail className="h-4 w-4" />
                        E-Mail *
                    </Label>
                    <Input
                        id="email"
                        type="email"
                        value={formData.email}
                        onChange={(e) => handleChange("email", e.target.value)}
                        placeholder="info@firma.de"
                        required
                    />
                </div>

                {/* Phone */}
                <div className="space-y-2">
                    <Label htmlFor="phone" className="flex items-center gap-2">
                        <Phone className="h-4 w-4" />
                        Telefon
                    </Label>
                    <Input
                        id="phone"
                        type="tel"
                        value={formData.phone}
                        onChange={(e) => handleChange("phone", e.target.value)}
                        placeholder="+49 123 456789"
                    />
                </div>

                {/* Address */}
                <div className="space-y-2 md:col-span-2">
                    <Label htmlFor="address" className="flex items-center gap-2">
                        <MapPin className="h-4 w-4" />
                        Adresse
                    </Label>
                    <Textarea
                        id="address"
                        value={formData.address}
                        onChange={(e) => handleChange("address", e.target.value)}
                        placeholder="Straße und Hausnummer"
                        rows={2}
                    />
                </div>

                {/* Postal Code */}
                <div className="space-y-2">
                    <Label htmlFor="postal_code">Postleitzahl</Label>
                    <Input
                        id="postal_code"
                        value={formData.postal_code}
                        onChange={(e) => handleChange("postal_code", e.target.value)}
                        placeholder="12345"
                    />
                </div>

                {/* City */}
                <div className="space-y-2">
                    <Label htmlFor="city">Stadt</Label>
                    <Input
                        id="city"
                        value={formData.city}
                        onChange={(e) => handleChange("city", e.target.value)}
                        placeholder="Berlin"
                    />
                </div>

                {/* Country */}
                <div className="space-y-2">
                    <Label htmlFor="country">Land</Label>
                    <Input
                        id="country"
                        value={formData.country}
                        onChange={(e) => handleChange("country", e.target.value)}
                        placeholder="Deutschland"
                    />
                </div>

                {/* Website */}
                <div className="space-y-2">
                    <Label htmlFor="website" className="flex items-center gap-2">
                        <Globe className="h-4 w-4" />
                        Website
                    </Label>
                    <Input
                        id="website"
                        type="url"
                        value={formData.website}
                        onChange={(e) => handleChange("website", e.target.value)}
                        placeholder="https://www.firma.de"
                    />
                </div>

                {/* Tax Number */}
                <div className="space-y-2">
                    <Label htmlFor="tax_number" className="flex items-center gap-2">
                        <FileText className="h-4 w-4" />
                        Steuernummer
                    </Label>
                    <Input
                        id="tax_number"
                        value={formData.tax_number}
                        onChange={(e) => handleChange("tax_number", e.target.value)}
                        placeholder="123/456/78910"
                    />
                </div>

                {/* VAT Number */}
                <div className="space-y-2">
                    <Label htmlFor="vat_number" className="flex items-center gap-2">
                        <FileText className="h-4 w-4" />
                        USt-IdNr.
                    </Label>
                    <Input
                        id="vat_number"
                        value={formData.vat_number}
                        onChange={(e) => handleChange("vat_number", e.target.value)}
                        placeholder="DE123456789"
                    />
                </div>
            </div>

            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p className="text-sm text-blue-800">
                    <strong>Hinweis:</strong> Felder mit * sind Pflichtfelder. Alle anderen Angaben können Sie auch später noch ergänzen.
                </p>
            </div>
        </div>
    )
}

