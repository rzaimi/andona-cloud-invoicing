"use client"

import { useState, useMemo } from "react"
import { Search, Layers, CheckSquare, Square, AlertCircle, X } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Badge } from "@/components/ui/badge"
import { Checkbox } from "@/components/ui/checkbox"
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog"
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select"

export interface SelectableAbschlag {
    id: string | number
    number: string
    amount: number
    date: string
    sequence_number?: number
    status?: string
    bauvorhaben?: string
    already_claimed: boolean
}

export interface AbschlagRef {
    invoice_id: string | number
    number: string
    amount: number
    date: string
}

interface AbschlagSelectionDialogProps {
    abschlaege: SelectableAbschlag[]
    selected: AbschlagRef[]
    onToggle: (ab: SelectableAbschlag) => void
    loading?: boolean
    disabled?: boolean
    noCustomer?: boolean
    totalAmount?: number
}

const statusLabel: Record<string, string> = {
    draft: "Entwurf",
    sent: "Gesendet",
    paid: "Bezahlt",
    overdue: "Überfällig",
    cancelled: "Storniert",
}

const statusColor: Record<string, string> = {
    draft: "bg-gray-100 text-gray-700",
    sent: "bg-blue-100 text-blue-700",
    paid: "bg-green-100 text-green-700",
    overdue: "bg-red-100 text-red-700",
    cancelled: "bg-orange-100 text-orange-700",
}

export function AbschlagSelectionDialog({
    abschlaege,
    selected,
    onToggle,
    loading,
    disabled,
    noCustomer,
    totalAmount,
}: AbschlagSelectionDialogProps) {
    const [open, setOpen] = useState(false)
    const [search, setSearch] = useState("")
    const [bvFilter, setBvFilter] = useState<string>("__all__")

    const bvOptions = useMemo(() => {
        const values = abschlaege.map((a) => a.bauvorhaben).filter(Boolean) as string[]
        return [...new Set(values)].sort()
    }, [abschlaege])

    const filtered = useMemo(() => {
        const q = search.toLowerCase().trim()
        return abschlaege.filter((ab) => {
            const matchSearch = !q || ab.number.toLowerCase().includes(q) || (ab.bauvorhaben ?? "").toLowerCase().includes(q)
            const matchBv = bvFilter === "__all__" || ab.bauvorhaben === bvFilter
            return matchSearch && matchBv
        })
    }, [abschlaege, search, bvFilter])

    const abschlagTotal = selected.reduce((sum, r) => sum + r.amount, 0)
    const remainingAmount = totalAmount !== undefined ? Math.max(0, totalAmount - abschlagTotal) : null

    const allVisibleSelected = filtered.length > 0 && filtered.every((ab) => selected.some((r) => r.invoice_id === ab.id))
    const someVisibleSelected = filtered.some((ab) => selected.some((r) => r.invoice_id === ab.id))

    const toggleAllVisible = () => {
        if (allVisibleSelected) {
            filtered.forEach((ab) => {
                if (selected.some((r) => r.invoice_id === ab.id)) onToggle(ab)
            })
        } else {
            filtered.forEach((ab) => {
                if (!selected.some((r) => r.invoice_id === ab.id) && !(ab.already_claimed)) onToggle(ab)
            })
        }
    }

    return (
        <div className="space-y-3">
            {/* Trigger + summary */}
            <div className="flex items-center gap-3 flex-wrap">
                <Dialog open={open} onOpenChange={setOpen}>
                    <DialogTrigger asChild>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={disabled || noCustomer || loading}
                            className="gap-2"
                        >
                            <Layers className="h-4 w-4" />
                            {loading ? "Lade…" : noCustomer ? "Kunde wählen" : "Abschläge auswählen"}
                            {selected.length > 0 && (
                                <Badge variant="secondary" className="ml-1 text-xs">
                                    {selected.length}
                                </Badge>
                            )}
                        </Button>
                    </DialogTrigger>

                    <DialogContent className="max-w-2xl max-h-[80vh] flex flex-col">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2">
                                <Layers className="h-5 w-5" />
                                Abschlagsrechnungen auswählen
                            </DialogTitle>
                        </DialogHeader>

                        {/* Filters */}
                        <div className="flex gap-2 flex-wrap">
                            <div className="relative flex-1 min-w-[180px]">
                                <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    placeholder="Suche nach Nummer oder BV…"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-8"
                                />
                            </div>
                            {bvOptions.length > 0 && (
                                <Select value={bvFilter} onValueChange={setBvFilter}>
                                    <SelectTrigger className="w-[200px]">
                                        <SelectValue placeholder="Alle Bauvorhaben" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="__all__">Alle Bauvorhaben</SelectItem>
                                        {bvOptions.map((bv) => (
                                            <SelectItem key={bv} value={bv}>
                                                {bv}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            )}
                        </div>

                        {/* Select-all row */}
                        {filtered.length > 0 && (
                            <div className="flex items-center justify-between text-xs text-muted-foreground border-b pb-2">
                                <button
                                    type="button"
                                    onClick={toggleAllVisible}
                                    className="flex items-center gap-1.5 hover:text-foreground transition-colors"
                                >
                                    {allVisibleSelected ? (
                                        <CheckSquare className="h-3.5 w-3.5" />
                                    ) : (
                                        <Square className="h-3.5 w-3.5" />
                                    )}
                                    {allVisibleSelected ? "Alle abwählen" : "Alle auswählen"}
                                </button>
                                <span>{filtered.length} Treffer</span>
                            </div>
                        )}

                        {/* List */}
                        <div className="flex-1 overflow-y-auto space-y-1 pr-1">
                            {filtered.length === 0 ? (
                                <p className="text-sm text-muted-foreground text-center py-8">Keine Abschlagsrechnungen gefunden.</p>
                            ) : (
                                filtered.map((ab) => {
                                    const isSelected = selected.some((r) => r.invoice_id === ab.id)
                                    const isClaimed = ab.already_claimed && !isSelected
                                    return (
                                        <div
                                            key={ab.id}
                                            className={`flex items-center gap-3 rounded-md border px-3 py-2.5 text-sm transition-colors
                                                ${isSelected ? "border-primary/40 bg-primary/5" : "hover:bg-muted/40"}
                                                ${isClaimed ? "opacity-50" : ""}
                                            `}
                                        >
                                            <Checkbox
                                                checked={isSelected}
                                                onCheckedChange={() => onToggle(ab)}
                                                disabled={isClaimed || disabled}
                                            />
                                            <div className="flex-1 min-w-0">
                                                <div className="flex items-center gap-2 flex-wrap">
                                                    <span className="font-medium">{ab.number}</span>
                                                    {ab.sequence_number != null && (
                                                        <span className="text-muted-foreground text-xs">Abschlag {ab.sequence_number}</span>
                                                    )}
                                                    {ab.bauvorhaben && (
                                                        <Badge variant="outline" className="text-[10px] px-1.5 py-0">
                                                            {ab.bauvorhaben}
                                                        </Badge>
                                                    )}
                                                    {ab.status && (
                                                        <span className={`text-[10px] px-1.5 py-0.5 rounded font-medium ${statusColor[ab.status] ?? "bg-gray-100 text-gray-700"}`}>
                                                            {statusLabel[ab.status] ?? ab.status}
                                                        </span>
                                                    )}
                                                    {isClaimed && (
                                                        <span className="flex items-center gap-0.5 text-[10px] text-amber-600">
                                                            <AlertCircle className="h-3 w-3" />
                                                            bereits verknüpft
                                                        </span>
                                                    )}
                                                </div>
                                                {ab.date && (
                                                    <p className="text-xs text-muted-foreground mt-0.5">
                                                        {new Date(ab.date).toLocaleDateString("de-DE")}
                                                    </p>
                                                )}
                                            </div>
                                            <span className="font-medium tabular-nums shrink-0">
                                                {ab.amount.toLocaleString("de-DE", { minimumFractionDigits: 2 })} €
                                            </span>
                                        </div>
                                    )
                                })
                            )}
                        </div>

                        {/* Footer summary */}
                        {selected.length > 0 && (
                            <div className="border-t pt-3 space-y-1 text-sm">
                                <div className="flex justify-between text-muted-foreground">
                                    <span>Ausgewählte Abzüge ({selected.length})</span>
                                    <span className="tabular-nums text-red-600">−{abschlagTotal.toLocaleString("de-DE", { minimumFractionDigits: 2 })} €</span>
                                </div>
                                {totalAmount !== undefined && (
                                    <div className="flex justify-between font-semibold">
                                        <span>Verbleibender Betrag</span>
                                        <span className="tabular-nums">{(remainingAmount ?? 0).toLocaleString("de-DE", { minimumFractionDigits: 2 })} €</span>
                                    </div>
                                )}
                            </div>
                        )}

                        <Button type="button" onClick={() => setOpen(false)} className="w-full">
                            Übernehmen ({selected.length} ausgewählt)
                        </Button>
                    </DialogContent>
                </Dialog>

                {/* No customer hint */}
                {noCustomer && (
                    <span className="text-xs text-amber-600">Bitte zuerst einen Kunden auswählen.</span>
                )}
            </div>

            {/* Selected chips */}
            {selected.length > 0 && (
                <div className="space-y-2">
                    <div className="flex flex-wrap gap-1.5">
                        {selected.map((ref) => (
                            <span
                                key={ref.invoice_id}
                                className="inline-flex items-center gap-1 rounded-full bg-primary/10 border border-primary/20 px-2.5 py-1 text-xs font-medium text-primary"
                            >
                                {ref.number}
                                <span className="text-primary/60 tabular-nums">
                                    {ref.amount.toLocaleString("de-DE", { minimumFractionDigits: 2 })} €
                                </span>
                                {!disabled && (
                                    <button
                                        type="button"
                                        onClick={() => {
                                            const ab = abschlaege.find((a) => a.id === ref.invoice_id)
                                            if (ab) onToggle(ab)
                                        }}
                                        className="ml-0.5 hover:text-red-500 transition-colors"
                                    >
                                        <X className="h-3 w-3" />
                                    </button>
                                )}
                            </span>
                        ))}
                    </div>
                    <div className="flex items-center justify-between text-sm text-muted-foreground border-t pt-2">
                        <span>Abzüge gesamt</span>
                        <span className="tabular-nums text-red-600 font-medium">−{abschlagTotal.toLocaleString("de-DE", { minimumFractionDigits: 2 })} €</span>
                    </div>
                    {totalAmount !== undefined && (
                        <div className="flex items-center justify-between text-sm font-semibold">
                            <span>Verbleibender Betrag</span>
                            <span className="tabular-nums">{(remainingAmount ?? 0).toLocaleString("de-DE", { minimumFractionDigits: 2 })} €</span>
                        </div>
                    )}
                </div>
            )}
        </div>
    )
}
