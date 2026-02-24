import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Info } from "lucide-react"

export default function Step3InvoiceSettings({ data, setData, errors }: any) {
    return (
        <div className="space-y-6">
            <Alert>
                <Info className="h-4 w-4" />
                <AlertDescription>
                    Konfigurieren Sie die Standard-Einstellungen für Rechnungen und Angebote.
                </AlertDescription>
            </Alert>

            <div className="space-y-6">
                <div>
                    <h3 className="font-semibold mb-3">Nummernpräfixe</h3>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <Label htmlFor="invoice_prefix">Rechnungspräfix *</Label>
                            <Input
                                id="invoice_prefix"
                                value={data.invoice_settings?.invoice_prefix || 'RE-'}
                                onChange={(e) => setData('invoice_settings', { ...data.invoice_settings, invoice_prefix: e.target.value })}
                                placeholder="RE-"
                            />
                            {errors?.['invoice_settings.invoice_prefix'] && (
                                <p className="text-sm text-red-500 mt-1">{errors['invoice_settings.invoice_prefix']}</p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="offer_prefix">Angebotspräfix *</Label>
                            <Input
                                id="offer_prefix"
                                value={data.invoice_settings?.offer_prefix || 'AN-'}
                                onChange={(e) => setData('invoice_settings', { ...data.invoice_settings, offer_prefix: e.target.value })}
                                placeholder="AN-"
                            />
                            {errors?.['invoice_settings.offer_prefix'] && (
                                <p className="text-sm text-red-500 mt-1">{errors['invoice_settings.offer_prefix']}</p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="customer_prefix">Kundenpräfix</Label>
                            <Input
                                id="customer_prefix"
                                value={data.invoice_settings?.customer_prefix || 'KD-'}
                                onChange={(e) => setData('invoice_settings', { ...data.invoice_settings, customer_prefix: e.target.value })}
                                placeholder="KD-"
                            />
                        </div>
                    </div>
                </div>

                <div>
                    <h3 className="font-semibold mb-3">Währung & Steuern</h3>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <Label htmlFor="currency">Währung *</Label>
                            <Select
                                value={data.invoice_settings?.currency || 'EUR'}
                                onValueChange={(value) => setData('invoice_settings', { ...data.invoice_settings, currency: value })}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="EUR">EUR (€)</SelectItem>
                                    <SelectItem value="USD">USD ($)</SelectItem>
                                    <SelectItem value="GBP">GBP (£)</SelectItem>
                                    <SelectItem value="CHF">CHF</SelectItem>
                                </SelectContent>
                            </Select>
                            {errors?.['invoice_settings.currency'] && (
                                <p className="text-sm text-red-500 mt-1">{errors['invoice_settings.currency']}</p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="tax_rate">Steuersatz (19%) *</Label>
                            <Input
                                id="tax_rate"
                                type="number"
                                step="0.01"
                                min="0"
                                max="1"
                                value={data.invoice_settings?.tax_rate ?? 0.19}
                                onChange={(e) => setData('invoice_settings', { ...data.invoice_settings, tax_rate: parseFloat(e.target.value) || 0 })}
                            />
                            {errors?.['invoice_settings.tax_rate'] && (
                                <p className="text-sm text-red-500 mt-1">{errors['invoice_settings.tax_rate']}</p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="reduced_tax_rate">Ermäßigter Steuersatz (7%)</Label>
                            <Input
                                id="reduced_tax_rate"
                                type="number"
                                step="0.01"
                                min="0"
                                max="1"
                                value={data.invoice_settings?.reduced_tax_rate ?? 0.07}
                                onChange={(e) => setData('invoice_settings', { ...data.invoice_settings, reduced_tax_rate: parseFloat(e.target.value) || 0 })}
                            />
                            {errors?.['invoice_settings.reduced_tax_rate'] && (
                                <p className="text-sm text-red-500 mt-1">{errors['invoice_settings.reduced_tax_rate']}</p>
                            )}
                        </div>
                    </div>
                </div>

                <div>
                    <h3 className="font-semibold mb-3">Zahlungs- & Gültigkeitsbedingungen</h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="payment_terms">Zahlungsziel (Tage) *</Label>
                            <Input
                                id="payment_terms"
                                type="number"
                                min="1"
                                value={data.invoice_settings?.payment_terms ?? 14}
                                onChange={(e) => setData('invoice_settings', { ...data.invoice_settings, payment_terms: parseInt(e.target.value) || 14 })}
                            />
                            {errors?.['invoice_settings.payment_terms'] && (
                                <p className="text-sm text-red-500 mt-1">{errors['invoice_settings.payment_terms']}</p>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="offer_validity_days">Angebotsgültigkeit (Tage) *</Label>
                            <Input
                                id="offer_validity_days"
                                type="number"
                                min="1"
                                value={data.invoice_settings?.offer_validity_days ?? 30}
                                onChange={(e) => setData('invoice_settings', { ...data.invoice_settings, offer_validity_days: parseInt(e.target.value) || 30 })}
                            />
                            {errors?.['invoice_settings.offer_validity_days'] && (
                                <p className="text-sm text-red-500 mt-1">{errors['invoice_settings.offer_validity_days']}</p>
                            )}
                        </div>
                    </div>
                </div>

                <div>
                    <h3 className="font-semibold mb-3">Format-Einstellungen</h3>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <Label htmlFor="date_format">Datumsformat *</Label>
                            <Select
                                value={data.invoice_settings?.date_format || 'd.m.Y'}
                                onValueChange={(value) => setData('invoice_settings', { ...data.invoice_settings, date_format: value })}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="d.m.Y">01.11.2025 (d.m.Y)</SelectItem>
                                    <SelectItem value="Y-m-d">2025-11-01 (Y-m-d)</SelectItem>
                                    <SelectItem value="m/d/Y">11/01/2025 (m/d/Y)</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label htmlFor="decimal_separator">Dezimaltrennzeichen *</Label>
                            <Select
                                value={data.invoice_settings?.decimal_separator || ','}
                                onValueChange={(value) => setData('invoice_settings', { ...data.invoice_settings, decimal_separator: value })}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value=",">, (Komma)</SelectItem>
                                    <SelectItem value=".">. (Punkt)</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label htmlFor="thousands_separator">Tausendertrennzeichen *</Label>
                            <Select
                                value={data.invoice_settings?.thousands_separator || '.'}
                                onValueChange={(value) => setData('invoice_settings', { ...data.invoice_settings, thousands_separator: value })}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value=".">. (Punkt)</SelectItem>
                                    <SelectItem value=",">, (Komma)</SelectItem>
                                    <SelectItem value=" "> (Leerzeichen)</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    )
}


