"use client"

import type React from "react"
import { useEffect, useMemo, useState } from "react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { Switch } from "@/components/ui/switch"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Plus, Trash2 } from "lucide-react"
import type { Customer } from "@/types"

export interface RecurringFormItem {
    id?: string
    product_id?: string | null
    description: string
    quantity: number
    unit_price: number
    unit: string
    tax_rate?: number | null
    discount_type?: "percentage" | "fixed" | null
    discount_value?: number | null
    sort_order?: number
}

export interface RecurringFormData {
    customer_id: string
    layout_id: string
    name: string
    description: string
    vat_regime: string
    tax_rate: number
    payment_method: string
    payment_terms: string
    skonto_percent: string | number
    skonto_days: string | number
    due_days_after_issue: number
    notes: string
    bauvorhaben: string
    auftragsnummer: string
    interval_unit: "day" | "week" | "month" | "quarter" | "year"
    interval_count: number
    day_of_month: number | ""
    start_date: string
    end_date: string
    max_occurrences: number | ""
    auto_send: boolean
    email_subject_template: string
    email_body_template: string
    items: RecurringFormItem[]
}

interface Props {
    data: RecurringFormData
    setData: <K extends keyof RecurringFormData>(key: K, value: RecurringFormData[K]) => void
    errors: Record<string, string>
    customers: Customer[]
    layouts: Array<{ id: string; name: string; is_default?: boolean }>
    products: Array<{
        id: string
        name: string
        description?: string
        price: number
        unit: string
        tax_rate: number
    }>
    processing: boolean
    onSubmit: (e: React.FormEvent) => void
    submitLabel: string
    cancelHref: string
}

export function RecurringProfileForm({
    data,
    setData,
    errors,
    customers,
    layouts,
    products,
    processing,
    onSubmit,
    submitLabel,
    cancelHref,
}: Props) {
    const [productPickerOpen, setProductPickerOpen] = useState(false)

    const addItem = () => {
        setData("items", [
            ...data.items,
            {
                description: "",
                quantity: 1,
                unit_price: 0,
                unit: "Stk.",
                tax_rate: 0.19,
                discount_type: null,
                discount_value: null,
                sort_order: data.items.length,
            },
        ])
    }

    const removeItem = (index: number) => {
        setData(
            "items",
            data.items.filter((_, i) => i !== index).map((it, i) => ({ ...it, sort_order: i }))
        )
    }

    const updateItem = (index: number, patch: Partial<RecurringFormItem>) => {
        setData(
            "items",
            data.items.map((it, i) => (i === index ? { ...it, ...patch } : it))
        )
    }

    const applyProduct = (index: number, productId: string) => {
        const product = products.find((p) => p.id === productId)
        if (!product) return
        updateItem(index, {
            product_id: product.id,
            description: product.description ? `${product.name}\n${product.description}` : product.name,
            unit_price: product.price,
            unit: product.unit || "Stk.",
            tax_rate: product.tax_rate ?? 0.19,
        })
    }

    // Live-compute a preview net/gross per item for UX feedback only
    // (server is authoritative on the generated invoice).
    const totals = useMemo(() => {
        let net = 0
        let tax = 0
        data.items.forEach((it) => {
            const q = Number(it.quantity) || 0
            const p = Number(it.unit_price) || 0
            let line = q * p
            if (it.discount_type === "percentage" && it.discount_value) {
                line -= line * (Number(it.discount_value) / 100)
            } else if (it.discount_type === "fixed" && it.discount_value) {
                line = Math.max(0, line - Number(it.discount_value))
            }
            net += line
            const rate = it.tax_rate ?? data.tax_rate ?? 0
            if (data.vat_regime === "standard") {
                tax += line * rate
            }
        })
        return { net, tax, gross: net + tax }
    }, [data.items, data.tax_rate, data.vat_regime])

    const showDayOfMonth = ["month", "quarter", "year"].includes(data.interval_unit)

    // Mirror InvoiceController: when vat_regime switches to non-standard, zero the tax rate.
    useEffect(() => {
        if (data.vat_regime !== "standard" && data.tax_rate !== 0) {
            setData("tax_rate", 0)
        }
    }, [data.vat_regime])

    return (
        <form onSubmit={onSubmit} className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Allgemein</CardTitle>
                    <CardDescription>Stammdaten des Abo-Profils.</CardDescription>
                </CardHeader>
                <CardContent className="grid gap-4 md:grid-cols-2">
                    <div className="md:col-span-2">
                        <Label htmlFor="name">Name *</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData("name", e.target.value)}
                            placeholder="z.B. Wartung Müller GmbH — monatlich"
                        />
                        {errors.name && <p className="text-sm text-red-600 mt-1">{errors.name}</p>}
                    </div>
                    <div>
                        <Label htmlFor="customer_id">Kunde *</Label>
                        <Select value={data.customer_id} onValueChange={(v) => setData("customer_id", v)}>
                            <SelectTrigger id="customer_id">
                                <SelectValue placeholder="Kunde wählen" />
                            </SelectTrigger>
                            <SelectContent>
                                {customers.map((c) => (
                                    <SelectItem key={c.id} value={c.id}>
                                        {c.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.customer_id && <p className="text-sm text-red-600 mt-1">{errors.customer_id}</p>}
                    </div>
                    <div>
                        <Label htmlFor="layout_id">Layout</Label>
                        <Select value={data.layout_id} onValueChange={(v) => setData("layout_id", v)}>
                            <SelectTrigger id="layout_id">
                                <SelectValue placeholder="Standard-Layout" />
                            </SelectTrigger>
                            <SelectContent>
                                {layouts.map((l) => (
                                    <SelectItem key={l.id} value={l.id}>
                                        {l.name} {l.is_default ? "(Standard)" : ""}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="md:col-span-2">
                        <Label htmlFor="description">Beschreibung</Label>
                        <Textarea
                            id="description"
                            value={data.description}
                            onChange={(e) => setData("description", e.target.value)}
                            placeholder="Interne Notiz — wird nicht auf der Rechnung angezeigt."
                        />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Zeitplan</CardTitle>
                    <CardDescription>
                        Wann und wie oft automatisch eine neue Rechnung erstellt werden soll.
                    </CardDescription>
                </CardHeader>
                <CardContent className="grid gap-4 md:grid-cols-4">
                    <div>
                        <Label htmlFor="interval_count">Alle</Label>
                        <Input
                            id="interval_count"
                            type="number"
                            min={1}
                            max={365}
                            value={data.interval_count}
                            onChange={(e) => setData("interval_count", Number(e.target.value) || 1)}
                        />
                        {errors.interval_count && (
                            <p className="text-sm text-red-600 mt-1">{errors.interval_count}</p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="interval_unit">Einheit</Label>
                        <Select
                            value={data.interval_unit}
                            onValueChange={(v) => setData("interval_unit", v as RecurringFormData["interval_unit"])}
                        >
                            <SelectTrigger id="interval_unit">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="day">Tage</SelectItem>
                                <SelectItem value="week">Wochen</SelectItem>
                                <SelectItem value="month">Monate</SelectItem>
                                <SelectItem value="quarter">Quartale</SelectItem>
                                <SelectItem value="year">Jahre</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    {showDayOfMonth && (
                        <div>
                            <Label htmlFor="day_of_month">Tag im Monat</Label>
                            <Input
                                id="day_of_month"
                                type="number"
                                min={1}
                                max={31}
                                value={data.day_of_month}
                                onChange={(e) =>
                                    setData(
                                        "day_of_month",
                                        e.target.value === "" ? "" : Math.min(31, Math.max(1, Number(e.target.value)))
                                    )
                                }
                                placeholder="z.B. 1 oder 31"
                            />
                        </div>
                    )}
                    <div>
                        <Label htmlFor="due_days_after_issue">Zahlungsziel (Tage)</Label>
                        <Input
                            id="due_days_after_issue"
                            type="number"
                            min={0}
                            max={365}
                            value={data.due_days_after_issue}
                            onChange={(e) => setData("due_days_after_issue", Number(e.target.value) || 0)}
                        />
                    </div>
                    <div>
                        <Label htmlFor="start_date">Startdatum *</Label>
                        <Input
                            id="start_date"
                            type="date"
                            value={data.start_date}
                            onChange={(e) => setData("start_date", e.target.value)}
                        />
                        {errors.start_date && <p className="text-sm text-red-600 mt-1">{errors.start_date}</p>}
                    </div>
                    <div>
                        <Label htmlFor="end_date">Enddatum (optional)</Label>
                        <Input
                            id="end_date"
                            type="date"
                            value={data.end_date}
                            onChange={(e) => setData("end_date", e.target.value)}
                        />
                    </div>
                    <div>
                        <Label htmlFor="max_occurrences">Max. Anzahl (optional)</Label>
                        <Input
                            id="max_occurrences"
                            type="number"
                            min={1}
                            max={1000}
                            value={data.max_occurrences}
                            onChange={(e) =>
                                setData("max_occurrences", e.target.value === "" ? "" : Number(e.target.value))
                            }
                            placeholder="z.B. 12"
                        />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Steuern & Zahlung</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 md:grid-cols-2">
                    <div>
                        <Label htmlFor="vat_regime">Umsatzsteuer-Regime</Label>
                        <Select value={data.vat_regime} onValueChange={(v) => setData("vat_regime", v)}>
                            <SelectTrigger id="vat_regime">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="standard">Regelbesteuerung</SelectItem>
                                <SelectItem value="small_business">§ 19 UStG (Kleinunternehmer)</SelectItem>
                                <SelectItem value="reverse_charge">§ 13b (EU)</SelectItem>
                                <SelectItem value="reverse_charge_domestic">§ 13b (Inland)</SelectItem>
                                <SelectItem value="intra_community">Innergem. Lieferung</SelectItem>
                                <SelectItem value="export">Ausfuhrlieferung</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label htmlFor="tax_rate">MwSt.-Satz</Label>
                        <Input
                            id="tax_rate"
                            type="number"
                            step="0.01"
                            min={0}
                            max={1}
                            value={data.tax_rate}
                            onChange={(e) => setData("tax_rate", Number(e.target.value) || 0)}
                            disabled={data.vat_regime !== "standard"}
                        />
                        <p className="text-xs text-muted-foreground mt-1">Als Dezimal, z.B. 0.19 für 19%.</p>
                    </div>
                    <div>
                        <Label htmlFor="payment_method">Zahlungsmethode</Label>
                        <Input
                            id="payment_method"
                            value={data.payment_method}
                            onChange={(e) => setData("payment_method", e.target.value)}
                            placeholder="Überweisung, Lastschrift ..."
                        />
                    </div>
                    <div>
                        <Label htmlFor="payment_terms">Zahlungsbedingungen</Label>
                        <Input
                            id="payment_terms"
                            value={data.payment_terms}
                            onChange={(e) => setData("payment_terms", e.target.value)}
                        />
                    </div>
                    <div>
                        <Label htmlFor="skonto_percent">Skonto %</Label>
                        <Select
                            value={data.skonto_percent ? String(data.skonto_percent) : "none"}
                            onValueChange={(v) => setData("skonto_percent", v === "none" ? "" : Number(v))}
                        >
                            <SelectTrigger id="skonto_percent">
                                <SelectValue placeholder="Kein Skonto" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">Kein Skonto</SelectItem>
                                <SelectItem value="2">2%</SelectItem>
                                <SelectItem value="3">3%</SelectItem>
                                <SelectItem value="4">4%</SelectItem>
                                <SelectItem value="5">5%</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label htmlFor="skonto_days">Skonto innerhalb</Label>
                        <Select
                            value={data.skonto_days ? String(data.skonto_days) : "none"}
                            onValueChange={(v) => setData("skonto_days", v === "none" ? "" : Number(v))}
                        >
                            <SelectTrigger id="skonto_days">
                                <SelectValue placeholder="—" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">—</SelectItem>
                                <SelectItem value="7">7 Tage</SelectItem>
                                <SelectItem value="10">10 Tage</SelectItem>
                                <SelectItem value="14">14 Tage</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0">
                    <div>
                        <CardTitle>Positionen</CardTitle>
                        <CardDescription>
                            Diese Positionen werden bei jeder Ausführung unverändert auf die neue Rechnung übernommen.
                        </CardDescription>
                    </div>
                    <Button type="button" onClick={addItem} variant="outline" size="sm">
                        <Plus className="h-4 w-4 mr-1" /> Position
                    </Button>
                </CardHeader>
                <CardContent className="space-y-3">
                    {data.items.length === 0 && (
                        <p className="text-sm text-muted-foreground">Noch keine Positionen hinzugefügt.</p>
                    )}
                    {data.items.length > 0 && (
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="min-w-[260px]">Beschreibung</TableHead>
                                        <TableHead className="w-20">Menge</TableHead>
                                        <TableHead className="w-24">Einheit</TableHead>
                                        <TableHead className="w-28">Einzelpreis</TableHead>
                                        <TableHead className="w-24">MwSt.</TableHead>
                                        <TableHead className="w-12"></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {data.items.map((it, i) => (
                                        <TableRow key={i}>
                                            <TableCell>
                                                <div className="flex gap-2">
                                                    <Select
                                                        value={it.product_id ?? "none"}
                                                        onValueChange={(v) =>
                                                            v === "none"
                                                                ? updateItem(i, { product_id: null })
                                                                : applyProduct(i, v)
                                                        }
                                                    >
                                                        <SelectTrigger className="w-40">
                                                            <SelectValue placeholder="Produkt" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="none">Freitext</SelectItem>
                                                            {products.map((p) => (
                                                                <SelectItem key={p.id} value={p.id}>
                                                                    {p.name}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                    <Textarea
                                                        value={it.description}
                                                        onChange={(e) =>
                                                            updateItem(i, { description: e.target.value })
                                                        }
                                                        className="min-h-[40px]"
                                                    />
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Input
                                                    type="number"
                                                    step="0.01"
                                                    value={it.quantity}
                                                    onChange={(e) =>
                                                        updateItem(i, { quantity: Number(e.target.value) || 0 })
                                                    }
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <Input
                                                    value={it.unit}
                                                    onChange={(e) => updateItem(i, { unit: e.target.value })}
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <Input
                                                    type="number"
                                                    step="0.01"
                                                    value={it.unit_price}
                                                    onChange={(e) =>
                                                        updateItem(i, { unit_price: Number(e.target.value) || 0 })
                                                    }
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <Input
                                                    type="number"
                                                    step="0.01"
                                                    min={0}
                                                    max={1}
                                                    value={it.tax_rate ?? ""}
                                                    onChange={(e) =>
                                                        updateItem(i, {
                                                            tax_rate:
                                                                e.target.value === ""
                                                                    ? null
                                                                    : Number(e.target.value),
                                                        })
                                                    }
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => removeItem(i)}
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}
                    {errors.items && <p className="text-sm text-red-600">{errors.items}</p>}
                    <div className="flex justify-end text-sm text-muted-foreground">
                        <div className="space-y-1 text-right">
                            <div>Netto: {totals.net.toFixed(2)} €</div>
                            <div>MwSt.: {totals.tax.toFixed(2)} €</div>
                            <div className="font-semibold text-foreground">
                                Gesamt: {totals.gross.toFixed(2)} €
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Automatischer Versand</CardTitle>
                    <CardDescription>
                        Wenn aktiviert, wird die erzeugte Rechnung direkt per E-Mail an den Kunden versendet.
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="flex items-center gap-3">
                        <Switch
                            id="auto_send"
                            checked={data.auto_send}
                            onCheckedChange={(v) => setData("auto_send", v)}
                        />
                        <Label htmlFor="auto_send">Rechnung automatisch per E-Mail versenden</Label>
                    </div>
                    {data.auto_send && (
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="md:col-span-2">
                                <Label htmlFor="email_subject_template">Betreff-Vorlage</Label>
                                <Input
                                    id="email_subject_template"
                                    value={data.email_subject_template}
                                    onChange={(e) => setData("email_subject_template", e.target.value)}
                                    placeholder="Rechnung {invoice_number}"
                                />
                                <p className="text-xs text-muted-foreground mt-1">
                                    Platzhalter: {"{invoice_number}"}, {"{customer_name}"}, {"{total}"},{" "}
                                    {"{issue_date}"}, {"{due_date}"}
                                </p>
                            </div>
                            <div className="md:col-span-2">
                                <Label htmlFor="email_body_template">Nachricht (optional)</Label>
                                <Textarea
                                    id="email_body_template"
                                    rows={4}
                                    value={data.email_body_template}
                                    onChange={(e) => setData("email_body_template", e.target.value)}
                                />
                            </div>
                        </div>
                    )}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Zusätzliche Felder</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 md:grid-cols-2">
                    <div>
                        <Label htmlFor="bauvorhaben">Bauvorhaben</Label>
                        <Input
                            id="bauvorhaben"
                            value={data.bauvorhaben}
                            onChange={(e) => setData("bauvorhaben", e.target.value)}
                        />
                    </div>
                    <div>
                        <Label htmlFor="auftragsnummer">Auftragsnummer</Label>
                        <Input
                            id="auftragsnummer"
                            value={data.auftragsnummer}
                            onChange={(e) => setData("auftragsnummer", e.target.value)}
                        />
                    </div>
                    <div className="md:col-span-2">
                        <Label htmlFor="notes">Notizen auf der Rechnung</Label>
                        <Textarea
                            id="notes"
                            value={data.notes}
                            onChange={(e) => setData("notes", e.target.value)}
                            rows={3}
                        />
                    </div>
                </CardContent>
            </Card>

            <div className="flex items-center justify-end gap-3">
                <Button type="button" variant="outline" asChild>
                    <a href={cancelHref}>Abbrechen</a>
                </Button>
                <Button type="submit" disabled={processing}>
                    {processing ? "Speichert…" : submitLabel}
                </Button>
            </div>
        </form>
    )
}
