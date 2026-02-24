import { type ChangeEvent } from "react"
import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Button } from "@/components/ui/button"
import { Info, Upload, X } from "lucide-react"

interface Props {
    data: any
    setData: (key: string, value: any) => void
    errors: Record<string, string>
    logoPreview: string | null
    onLogoFile: (file: File | null) => void
}

export default function Step1CompanyInfo({ data, setData, errors, logoPreview, onLogoFile }: Props) {
    const handleLogoChange = (e: ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0] ?? null
        onLogoFile(file)
        // Allow re-selecting the same file next time
        e.target.value = ""
    }

    const handleRemoveLogo = () => {
        onLogoFile(null)
        // Signal the backend to delete the persisted logo on the next submit
        setData("company_info", { ...data.company_info, logo: null, remove_logo: true })
    }

    const ci = data.company_info ?? {}

    return (
        <div className="space-y-6">
            <Alert>
                <Info className="h-4 w-4" />
                <AlertDescription>
                    Bitte geben Sie die grundlegenden Informationen über Ihre Firma ein.
                    Felder mit * sind Pflichtfelder.
                </AlertDescription>
            </Alert>

            {/* Logo */}
            <div className="space-y-3">
                <Label>Firmenlogo</Label>
                <div className="flex items-start gap-4">
                    {logoPreview && (
                        <div className="relative">
                            <img
                                src={logoPreview}
                                alt="Firmenlogo"
                                className="h-20 w-20 rounded border object-contain bg-white"
                            />
                            <Button
                                type="button"
                                variant="destructive"
                                size="icon"
                                className="absolute -top-2 -right-2 h-6 w-6"
                                onClick={handleRemoveLogo}
                            >
                                <X className="h-3 w-3" />
                            </Button>
                        </div>
                    )}
                    <div className="space-y-2">
                        <label htmlFor="logo-upload" className="cursor-pointer inline-block">
                            <Button type="button" variant="outline" size="sm" asChild>
                                <span>
                                    <Upload className="mr-2 h-4 w-4" />
                                    Logo hochladen
                                </span>
                            </Button>
                        </label>
                        <input
                            id="logo-upload"
                            type="file"
                            accept="image/jpeg,image/png,image/jpg,image/gif"
                            className="hidden"
                            onChange={handleLogoChange}
                        />
                        <p className="text-xs text-muted-foreground">PNG / JPG / GIF, max. 2 MB</p>
                    </div>
                </div>
                {errors?.["company_info.logo"] && (
                    <p className="text-sm text-red-500">{errors["company_info.logo"]}</p>
                )}
            </div>

            {/* Fields */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="md:col-span-2">
                    <Label htmlFor="name">Firmenname *</Label>
                    <Input
                        id="name"
                        value={ci.name || ""}
                        onChange={(e) => setData("company_info", { ...ci, name: e.target.value })}
                        placeholder="z.B. Musterfirma GmbH"
                    />
                    {errors?.["company_info.name"] && (
                        <p className="text-sm text-red-500 mt-1">{errors["company_info.name"]}</p>
                    )}
                </div>

                <div>
                    <Label htmlFor="email">E-Mail *</Label>
                    <Input
                        id="email"
                        type="email"
                        value={ci.email || ""}
                        onChange={(e) => setData("company_info", { ...ci, email: e.target.value })}
                        placeholder="info@musterfirma.de"
                    />
                    {errors?.["company_info.email"] && (
                        <p className="text-sm text-red-500 mt-1">{errors["company_info.email"]}</p>
                    )}
                </div>

                <div>
                    <Label htmlFor="phone">Telefon</Label>
                    <Input
                        id="phone"
                        value={ci.phone || ""}
                        onChange={(e) => setData("company_info", { ...ci, phone: e.target.value })}
                        placeholder="+49 123 456789"
                    />
                    {errors?.["company_info.phone"] && (
                        <p className="text-sm text-red-500 mt-1">{errors["company_info.phone"]}</p>
                    )}
                </div>

                <div className="md:col-span-2">
                    <Label htmlFor="address">Adresse</Label>
                    <Input
                        id="address"
                        value={ci.address || ""}
                        onChange={(e) => setData("company_info", { ...ci, address: e.target.value })}
                        placeholder="Musterstraße 123"
                    />
                </div>

                <div>
                    <Label htmlFor="postal_code">Postleitzahl</Label>
                    <Input
                        id="postal_code"
                        value={ci.postal_code || ""}
                        onChange={(e) => setData("company_info", { ...ci, postal_code: e.target.value })}
                        placeholder="12345"
                    />
                </div>

                <div>
                    <Label htmlFor="city">Stadt</Label>
                    <Input
                        id="city"
                        value={ci.city || ""}
                        onChange={(e) => setData("company_info", { ...ci, city: e.target.value })}
                        placeholder="Berlin"
                    />
                </div>

                <div>
                    <Label htmlFor="country">Land</Label>
                    <Input
                        id="country"
                        value={ci.country || "Deutschland"}
                        onChange={(e) => setData("company_info", { ...ci, country: e.target.value })}
                    />
                </div>

                <div>
                    <Label htmlFor="tax_number">Steuernummer</Label>
                    <Input
                        id="tax_number"
                        value={ci.tax_number || ""}
                        onChange={(e) => setData("company_info", { ...ci, tax_number: e.target.value })}
                        placeholder="12/345/67890"
                    />
                </div>

                <div>
                    <Label htmlFor="vat_number">USt-IdNr.</Label>
                    <Input
                        id="vat_number"
                        value={ci.vat_number || ""}
                        onChange={(e) => setData("company_info", { ...ci, vat_number: e.target.value })}
                        placeholder="DE123456789"
                    />
                </div>

                <div>
                    <Label htmlFor="website">Webseite</Label>
                    <Input
                        id="website"
                        type="url"
                        value={ci.website || ""}
                        onChange={(e) => setData("company_info", { ...ci, website: e.target.value })}
                        placeholder="https://www.musterfirma.de"
                    />
                    {errors?.["company_info.website"] && (
                        <p className="text-sm text-red-500 mt-1">{errors["company_info.website"]}</p>
                    )}
                </div>
            </div>
        </div>
    )
}
