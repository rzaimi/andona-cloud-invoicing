import { useState, useEffect } from "react"
import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { FileText, Percent, Calendar, Coins } from "lucide-react"

interface Step3Props {
    data: any
    updateData: (key: string, data: any) => void
}

export default function Step3InvoiceSettings({ data, updateData }: Step3Props) {
    const [formData, setFormData] = useState(data.invoice_settings || {
        invoice_prefix: "RE-",
        offer_prefix: "AN-",
        currency: "EUR",
        tax_rate: 0.19,
        reduced_tax_rate: 0.07,
        payment_terms: 14,
        offer_validity_days: 30,
        date_format: "d.m.Y",
    })

    useEffect(() => {
        updateData("invoice_settings", formData)
    }, [formData])

    const handleChange = (field: string, value: string | number) => {
        setFormData({ ...formData, [field]: value })
    }

    return (
        <div className="space-y-6">
            <div className="grid gap-6 md:grid-cols-2">
                {/* Invoice Prefix */}
                <div className="space-y-2">
                    <Label htmlFor="invoice_prefix" className="flex items-center gap-2">
                        <FileText className="h-4 w-4" />
                        {t('settings.invoicePrefix')}
                    </Label>
                    <Input
                        id="invoice_prefix"
                        value={formData.invoice_prefix}
                        onChange={(e) => handleChange("invoice_prefix", e.target.value)}
                        placeholder="RE-"
                    />
                    <p className="text-xs text-muted-foreground">
                        Beispiel: RE-2025-001
                    </p>
                </div>

                {/* Offer Prefix */}
                <div className="space-y-2">
                    <Label htmlFor="offer_prefix" className="flex items-center gap-2">
                        <FileText className="h-4 w-4" />
                        {t('settings.offerPrefix')}
                    </Label>
                    <Input
                        id="offer_prefix"
                        value={formData.offer_prefix}
                        onChange={(e) => handleChange("offer_prefix", e.target.value)}
                        placeholder="AN-"
                    />
                    <p className="text-xs text-muted-foreground">
                        Beispiel: AN-2025-001
                    </p>
                </div>

                {/* Currency */}
                <div className="space-y-2">
                    <Label htmlFor="currency" className="flex items-center gap-2">
                        <Coins className="h-4 w-4" />
                        {t('settings.currency')}
                    </Label>
                    <Select
                        value={formData.currency}
                        onValueChange={(value) => handleChange("currency", value)}
                    >
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="EUR">EUR (€)</SelectItem>
                            <SelectItem value="USD">USD ($)</SelectItem>
                            <SelectItem value="GBP">GBP (£)</SelectItem>
                            <SelectItem value="CHF">CHF (Fr.)</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {/* Date Format */}
                <div className="space-y-2">
                    <Label htmlFor="date_format" className="flex items-center gap-2">
                        <Calendar className="h-4 w-4" />
                        Datumsformat
                    </Label>
                    <Select
                        value={formData.date_format}
                        onValueChange={(value) => handleChange("date_format", value)}
                    >
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="d.m.Y">TT.MM.JJJJ (z.B. 01.12.2025)</SelectItem>
                            <SelectItem value="Y-m-d">JJJJ-MM-TT (z.B. 2025-12-01)</SelectItem>
                            <SelectItem value="m/d/Y">MM/TT/JJJJ (z.B. 12/01/2025)</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {/* Tax Rate */}
                <div className="space-y-2">
                    <Label htmlFor="tax_rate" className="flex items-center gap-2">
                        <Percent className="h-4 w-4" />
                        Standard MwSt.-Satz
                    </Label>
                    <div className="flex items-center gap-2">
                        <Input
                            id="tax_rate"
                            type="number"
                            step="0.01"
                            min="0"
                            max="1"
                            value={formData.tax_rate}
                            onChange={(e) => handleChange("tax_rate", parseFloat(e.target.value))}
                        />
                        <span className="text-sm text-muted-foreground">
                            ({(formData.tax_rate * 100).toFixed(0)}%)
                        </span>
                    </div>
                    <p className="text-xs text-muted-foreground">
                        Standard: 0.19 (19%)
                    </p>
                </div>

                {/* Reduced Tax Rate */}
                <div className="space-y-2">
                    <Label htmlFor="reduced_tax_rate" className="flex items-center gap-2">
                        <Percent className="h-4 w-4" />
                        {t('settings.reducedTaxRate')}
                    </Label>
                    <div className="flex items-center gap-2">
                        <Input
                            id="reduced_tax_rate"
                            type="number"
                            step="0.01"
                            min="0"
                            max="1"
                            value={formData.reduced_tax_rate}
                            onChange={(e) => handleChange("reduced_tax_rate", parseFloat(e.target.value))}
                        />
                        <span className="text-sm text-muted-foreground">
                            ({(formData.reduced_tax_rate * 100).toFixed(0)}%)
                        </span>
                    </div>
                    <p className="text-xs text-muted-foreground">
                        Standard: 0.07 (7%)
                    </p>
                </div>

                {/* Payment Terms */}
                <div className="space-y-2">
                    <Label htmlFor="payment_terms">Zahlungsziel (Tage)</Label>
                    <Input
                        id="payment_terms"
                        type="number"
                        min="1"
                        value={formData.payment_terms}
                        onChange={(e) => handleChange("payment_terms", parseInt(e.target.value))}
                    />
                    <p className="text-xs text-muted-foreground">
                        {t('settings.defaultDueDate')}
                    </p>
                </div>

                {/* Offer Validity */}
                <div className="space-y-2">
                    <Label htmlFor="offer_validity_days">{t('settings.offerValidityDays')}</Label>
                    <Input
                        id="offer_validity_days"
                        type="number"
                        min="1"
                        value={formData.offer_validity_days}
                        onChange={(e) => handleChange("offer_validity_days", parseInt(e.target.value))}
                    />
                    <p className="text-xs text-muted-foreground">
                        Standard: 30 Tage
                    </p>
                </div>
            </div>

            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p className="text-sm text-blue-800">
                    <strong>{t('common.note')}:</strong> {t('settings.invoiceSettingsNote')}/Angebot individuell angepasst werden.
                </p>
            </div>
        </div>
    )
}

