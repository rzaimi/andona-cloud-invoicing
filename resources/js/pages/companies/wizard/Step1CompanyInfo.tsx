import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Info } from "lucide-react"

export default function Step1CompanyInfo({ data, setData, errors }: any) {
    return (
        <div className="space-y-6">
            <Alert>
                <Info className="h-4 w-4" />
                <AlertDescription>
                    Bitte geben Sie die grundlegenden Informationen über Ihre Firma ein. Felder mit * sind Pflichtfelder.
                </AlertDescription>
            </Alert>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="md:col-span-2">
                    <Label htmlFor="name">Firmenname *</Label>
                    <Input
                        id="name"
                        value={data.company_info?.name || ''}
                        onChange={(e) => setData('company_info', { ...data.company_info, name: e.target.value })}
                        placeholder="z.B. Musterfirma GmbH"
                    />
                    {errors?.['company_info.name'] && (
                        <p className="text-sm text-red-500 mt-1">{errors['company_info.name']}</p>
                    )}
                </div>

                <div>
                    <Label htmlFor="email">E-Mail *</Label>
                    <Input
                        id="email"
                        type="email"
                        value={data.company_info?.email || ''}
                        onChange={(e) => setData('company_info', { ...data.company_info, email: e.target.value })}
                        placeholder="info@musterfirma.de"
                    />
                    {errors?.['company_info.email'] && (
                        <p className="text-sm text-red-500 mt-1">{errors['company_info.email']}</p>
                    )}
                </div>

                <div>
                    <Label htmlFor="phone">Telefon</Label>
                    <Input
                        id="phone"
                        value={data.company_info?.phone || ''}
                        onChange={(e) => setData('company_info', { ...data.company_info, phone: e.target.value })}
                        placeholder="+49 123 456789"
                    />
                </div>

                <div className="md:col-span-2">
                    <Label htmlFor="address">Adresse</Label>
                    <Input
                        id="address"
                        value={data.company_info?.address || ''}
                        onChange={(e) => setData('company_info', { ...data.company_info, address: e.target.value })}
                        placeholder="Musterstraße 123"
                    />
                </div>

                <div>
                    <Label htmlFor="postal_code">Postleitzahl</Label>
                    <Input
                        id="postal_code"
                        value={data.company_info?.postal_code || ''}
                        onChange={(e) => setData('company_info', { ...data.company_info, postal_code: e.target.value })}
                        placeholder="12345"
                    />
                </div>

                <div>
                    <Label htmlFor="city">Stadt</Label>
                    <Input
                        id="city"
                        value={data.company_info?.city || ''}
                        onChange={(e) => setData('company_info', { ...data.company_info, city: e.target.value })}
                        placeholder="Berlin"
                    />
                </div>

                <div>
                    <Label htmlFor="country">Land</Label>
                    <Input
                        id="country"
                        value={data.company_info?.country || 'Deutschland'}
                        onChange={(e) => setData('company_info', { ...data.company_info, country: e.target.value })}
                    />
                </div>

                <div>
                    <Label htmlFor="tax_number">Steuernummer</Label>
                    <Input
                        id="tax_number"
                        value={data.company_info?.tax_number || ''}
                        onChange={(e) => setData('company_info', { ...data.company_info, tax_number: e.target.value })}
                        placeholder="12/345/67890"
                    />
                </div>

                <div>
                    <Label htmlFor="vat_number">USt-IdNr.</Label>
                    <Input
                        id="vat_number"
                        value={data.company_info?.vat_number || ''}
                        onChange={(e) => setData('company_info', { ...data.company_info, vat_number: e.target.value })}
                        placeholder="DE123456789"
                    />
                </div>

                <div>
                    <Label htmlFor="website">Webseite</Label>
                    <Input
                        id="website"
                        type="url"
                        value={data.company_info?.website || ''}
                        onChange={(e) => setData('company_info', { ...data.company_info, website: e.target.value })}
                        placeholder="https://www.musterfirma.de"
                    />
                </div>
            </div>
        </div>
    )
}


