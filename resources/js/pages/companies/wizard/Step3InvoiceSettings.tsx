import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Badge } from "@/components/ui/badge"
import { Info } from "lucide-react"

// ─── Preview helper (mirrors settings/tabs/company.tsx) ───────────────────────
function previewNumberFormat(format: string, counter: number = 1): string {
    const now   = new Date()
    const yyyy  = now.getFullYear().toString()
    const yy    = yyyy.slice(2)
    const mm    = String(now.getMonth() + 1).padStart(2, "0")
    const dd    = String(now.getDate()).padStart(2, "0")

    let result = format
        .replace(/\{YYYY\}/g, yyyy)
        .replace(/\{YY\}/g, yy)
        .replace(/\{MM\}/g, mm)
        .replace(/\{DD\}/g, dd)
        .replace(/\{(#+)\}/g, (_: string, hashes: string) =>
            String(counter).padStart(hashes.length, "0")
        )
    return result
}

const TOKENS: [string, string][] = [
    ["{YYYY}", "4-stelliges Jahr (2025)"],
    ["{YY}",   "2-stelliges Jahr (25)"],
    ["{MM}",   "Monat (01–12)"],
    ["{DD}",   "Tag (01–31)"],
    ["{####}", "Laufende Nr. (Anzahl # = Stellenanzahl)"],
]

export default function Step3InvoiceSettings({ data, setData, errors }: any) {
    const inv = data.invoice_settings ?? {}

    const set = (field: string, value: any) =>
        setData("invoice_settings", { ...inv, [field]: value })

    return (
        <div className="space-y-8">

            {/* ── Nummernformat ──────────────────────────────────────────────── */}
            <div className="space-y-4">
                <div>
                    <h3 className="font-semibold">Nummernformat</h3>
                    <p className="text-sm text-muted-foreground mt-0.5">
                        Dynamisches Format für automatisch generierte Nummern. Verfügbare Tokens:
                    </p>
                    <div className="flex flex-wrap gap-2 mt-2">
                        {TOKENS.map(([token, desc]) => (
                            <span key={token} title={desc} className="cursor-help">
                                <Badge variant="outline" className="font-mono text-xs">{token}</Badge>
                            </span>
                        ))}
                    </div>
                </div>

                {/* Column headers */}
                <div className="grid grid-cols-[1fr_120px] gap-3 text-xs font-medium text-muted-foreground px-1">
                    <span>Format</span>
                    <span>Nächste Nr.</span>
                </div>

                {/* Rechnung */}
                <div className="grid grid-cols-[1fr_120px] gap-3 items-start">
                    <div className="space-y-1">
                        <Label>Rechnung *</Label>
                        <Input
                            value={inv.invoice_number_format ?? "RE-{YYYY}-{####}"}
                            onChange={(e) => set("invoice_number_format", e.target.value)}
                            maxLength={60}
                            placeholder="RE-{YYYY}-{####}"
                        />
                        {inv.invoice_number_format && (
                            <p className="text-xs text-muted-foreground">
                                Vorschau: <span className="font-mono font-medium">
                                    {previewNumberFormat(inv.invoice_number_format, inv.invoice_next_counter ?? 1)}
                                </span>
                            </p>
                        )}
                        {errors?.["invoice_settings.invoice_number_format"] && (
                            <p className="text-red-600 text-sm">{errors["invoice_settings.invoice_number_format"]}</p>
                        )}
                    </div>
                    <div className="space-y-1">
                        <Label>&nbsp;</Label>
                        <Input
                            type="number" min="1" max="999999"
                            value={inv.invoice_next_counter ?? 1}
                            onChange={(e) => set("invoice_next_counter", parseInt(e.target.value) || 1)}
                        />
                    </div>
                </div>

                {/* Storno */}
                <div className="grid grid-cols-[1fr_120px] gap-3 items-start">
                    <div className="space-y-1">
                        <Label>Storno *</Label>
                        <Input
                            value={inv.storno_number_format ?? "STORNO-{YYYY}-{####}"}
                            onChange={(e) => set("storno_number_format", e.target.value)}
                            maxLength={60}
                            placeholder="STORNO-{YYYY}-{####}"
                        />
                        {inv.storno_number_format && (
                            <p className="text-xs text-muted-foreground">
                                Vorschau: <span className="font-mono font-medium">
                                    {previewNumberFormat(inv.storno_number_format, inv.storno_next_counter ?? 1)}
                                </span>
                            </p>
                        )}
                    </div>
                    <div className="space-y-1">
                        <Label>&nbsp;</Label>
                        <Input
                            type="number" min="1" max="999999"
                            value={inv.storno_next_counter ?? 1}
                            onChange={(e) => set("storno_next_counter", parseInt(e.target.value) || 1)}
                        />
                    </div>
                </div>

                {/* Angebot */}
                <div className="grid grid-cols-[1fr_120px] gap-3 items-start">
                    <div className="space-y-1">
                        <Label>Angebot *</Label>
                        <Input
                            value={inv.offer_number_format ?? "AN-{YYYY}-{####}"}
                            onChange={(e) => set("offer_number_format", e.target.value)}
                            maxLength={60}
                            placeholder="AN-{YYYY}-{####}"
                        />
                        {inv.offer_number_format && (
                            <p className="text-xs text-muted-foreground">
                                Vorschau: <span className="font-mono font-medium">
                                    {previewNumberFormat(inv.offer_number_format, inv.offer_next_counter ?? 1)}
                                </span>
                            </p>
                        )}
                    </div>
                    <div className="space-y-1">
                        <Label>&nbsp;</Label>
                        <Input
                            type="number" min="1" max="999999"
                            value={inv.offer_next_counter ?? 1}
                            onChange={(e) => set("offer_next_counter", parseInt(e.target.value) || 1)}
                        />
                    </div>
                </div>

                {/* Kunde */}
                <div className="grid grid-cols-[1fr_120px] gap-3 items-start">
                    <div className="space-y-1">
                        <Label>Kunde</Label>
                        <Input
                            value={inv.customer_number_format ?? "KU-{YYYY}-{####}"}
                            onChange={(e) => set("customer_number_format", e.target.value)}
                            maxLength={60}
                            placeholder="KU-{YYYY}-{####}"
                        />
                        {inv.customer_number_format && (
                            <p className="text-xs text-muted-foreground">
                                Vorschau: <span className="font-mono font-medium">
                                    {previewNumberFormat(inv.customer_number_format, inv.customer_next_counter ?? 1)}
                                </span>
                            </p>
                        )}
                    </div>
                    <div className="space-y-1">
                        <Label>&nbsp;</Label>
                        <Input
                            type="number" min="1" max="999999"
                            value={inv.customer_next_counter ?? 1}
                            onChange={(e) => set("customer_next_counter", parseInt(e.target.value) || 1)}
                        />
                    </div>
                </div>

                <p className="text-xs text-muted-foreground pt-1 flex items-start gap-1.5">
                    <Info className="h-3.5 w-3.5 mt-0.5 shrink-0" />
                    Die nächste Nummer kann nur erhöht werden — sie wird nie kleiner als die höchste bereits vergebene Nummer.
                </p>
            </div>

            {/* ── Währung & Steuern ──────────────────────────────────────────── */}
            <div className="space-y-3">
                <h3 className="font-semibold">Währung & Steuern</h3>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div className="space-y-1">
                        <Label>Währung *</Label>
                        <Select
                            value={inv.currency ?? "EUR"}
                            onValueChange={(v) => set("currency", v)}
                        >
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="EUR">EUR (€)</SelectItem>
                                <SelectItem value="USD">USD ($)</SelectItem>
                                <SelectItem value="GBP">GBP (£)</SelectItem>
                                <SelectItem value="CHF">CHF</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-1">
                        <Label>Standard-Steuersatz *</Label>
                        <Input
                            type="number" step="0.01" min="0" max="1"
                            value={inv.tax_rate ?? 0.19}
                            onChange={(e) => set("tax_rate", parseFloat(e.target.value) || 0)}
                        />
                        <p className="text-xs text-muted-foreground">z.B. 0.19 für 19%</p>
                    </div>
                    <div className="space-y-1">
                        <Label>Ermäßigter Steuersatz</Label>
                        <Input
                            type="number" step="0.01" min="0" max="1"
                            value={inv.reduced_tax_rate ?? 0.07}
                            onChange={(e) => set("reduced_tax_rate", parseFloat(e.target.value) || 0)}
                        />
                        <p className="text-xs text-muted-foreground">z.B. 0.07 für 7%</p>
                    </div>
                </div>
            </div>

            {/* ── Zahlungs- & Gültigkeitsbedingungen ────────────────────────── */}
            <div className="space-y-3">
                <h3 className="font-semibold">Zahlungsbedingungen</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-1">
                        <Label>Zahlungsziel (Tage) *</Label>
                        <Input
                            type="number" min="1"
                            value={inv.payment_terms ?? 14}
                            onChange={(e) => set("payment_terms", parseInt(e.target.value) || 14)}
                        />
                    </div>
                    <div className="space-y-1">
                        <Label>Angebotsgültigkeit (Tage) *</Label>
                        <Input
                            type="number" min="1"
                            value={inv.offer_validity_days ?? 30}
                            onChange={(e) => set("offer_validity_days", parseInt(e.target.value) || 30)}
                        />
                    </div>
                </div>
            </div>

            {/* ── Format-Einstellungen ───────────────────────────────────────── */}
            <div className="space-y-3">
                <h3 className="font-semibold">Format-Einstellungen</h3>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div className="space-y-1">
                        <Label>Datumsformat *</Label>
                        <Select
                            value={inv.date_format ?? "d.m.Y"}
                            onValueChange={(v) => set("date_format", v)}
                        >
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="d.m.Y">DD.MM.YYYY (01.11.2025)</SelectItem>
                                <SelectItem value="Y-m-d">YYYY-MM-DD (2025-11-01)</SelectItem>
                                <SelectItem value="d/m/Y">DD/MM/YYYY (01/11/2025)</SelectItem>
                                <SelectItem value="m/d/Y">MM/DD/YYYY (11/01/2025)</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-1">
                        <Label>Dezimaltrennzeichen *</Label>
                        <Select
                            value={inv.decimal_separator ?? ","}
                            onValueChange={(v) => set("decimal_separator", v)}
                        >
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value=",">, (Komma)</SelectItem>
                                <SelectItem value=".">. (Punkt)</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-1">
                        <Label>Tausendertrennzeichen *</Label>
                        <Select
                            value={inv.thousands_separator ?? "."}
                            onValueChange={(v) => set("thousands_separator", v)}
                        >
                            <SelectTrigger><SelectValue /></SelectTrigger>
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
    )
}
