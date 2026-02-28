import { useState, useEffect } from "react"
import { useTranslation } from "react-i18next"
import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Building2, CreditCard } from "lucide-react"
import { Alert, AlertDescription } from "@/components/ui/alert"

interface Step5Props {
    data: any
    updateData: (key: string, data: any) => void
}

export default function Step5Banking({ data, updateData }: Step5Props) {
    const { t } = useTranslation()
    const [formData, setFormData] = useState(data.banking_info || {
        bank_name: "",
        iban: "",
        bic: "",
        account_holder: "",
    })

    useEffect(() => {
        updateData("banking_info", formData)
    }, [formData])

    const handleChange = (field: string, value: string) => {
        setFormData({ ...formData, [field]: value })
    }

    const formatIBAN = (value: string) => {
        // Remove all spaces and convert to uppercase
        const cleaned = value.replace(/\s/g, "").toUpperCase()
        // Add space every 4 characters
        return cleaned.match(/.{1,4}/g)?.join(" ") || cleaned
    }

    return (
        <div className="space-y-6">
            <Alert>
                <CreditCard className="h-4 w-4" />
                <AlertDescription>
                    {t('settings.bankingAlertDesc')}
                </AlertDescription>
            </Alert>

            <div className="grid gap-6 md:grid-cols-2">
                {/* Bank Name */}
                <div className="space-y-2 md:col-span-2">
                    <Label htmlFor="bank_name" className="flex items-center gap-2">
                        <Building2 className="h-4 w-4" />
                        Name der Bank
                    </Label>
                    <Input
                        id="bank_name"
                        value={formData.bank_name}
                        onChange={(e) => handleChange("bank_name", e.target.value)}
                        placeholder="z.B. Deutsche Bank AG"
                    />
                </div>

                {/* IBAN */}
                <div className="space-y-2 md:col-span-2">
                    <Label htmlFor="iban" className="flex items-center gap-2">
                        <CreditCard className="h-4 w-4" />
                        IBAN
                    </Label>
                    <Input
                        id="iban"
                        value={formData.iban}
                        onChange={(e) => handleChange("iban", e.target.value)}
                        onBlur={(e) => handleChange("iban", formatIBAN(e.target.value))}
                        placeholder="DE89 3704 0044 0532 0130 00"
                        maxLength={34}
                    />
                    <p className="text-xs text-muted-foreground">
                        Internationale Bankkontonummer
                    </p>
                </div>

                {/* BIC */}
                <div className="space-y-2">
                    <Label htmlFor="bic">BIC / SWIFT-Code</Label>
                    <Input
                        id="bic"
                        value={formData.bic}
                        onChange={(e) => handleChange("bic", e.target.value.toUpperCase())}
                        placeholder="DEUTDEDBBER"
                        maxLength={11}
                    />
                    <p className="text-xs text-muted-foreground">
                        Bank Identifier Code
                    </p>
                </div>

                {/* Account Holder */}
                <div className="space-y-2">
                    <Label htmlFor="account_holder">Kontoinhaber</Label>
                    <Input
                        id="account_holder"
                        value={formData.account_holder}
                        onChange={(e) => handleChange("account_holder", e.target.value)}
                        placeholder="Firmenname oder Name des Inhabers"
                    />
                </div>
            </div>

            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p className="text-sm text-blue-800">
                    <strong>{t('settings.bankingNoteTitle')}</strong> {t('settings.bankingNoteDesc')}
                </p>
            </div>

            <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <h4 className="font-semibold mb-2">Beispiel einer Bankverbindung auf Rechnung:</h4>
                <div className="text-sm space-y-1 font-mono">
                    <p>Bank: {formData.bank_name || "Deutsche Bank AG"}</p>
                    <p>IBAN: {formData.iban || "DE89 3704 0044 0532 0130 00"}</p>
                    <p>BIC: {formData.bic || "DEUTDEDBBER"}</p>
                </div>
            </div>
        </div>
    )
}

