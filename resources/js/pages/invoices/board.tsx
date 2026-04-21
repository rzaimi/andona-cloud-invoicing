"use client"

import { useState } from "react"
import { Head, Link, usePage, router } from "@inertiajs/react"
import axios from "axios"
import { toast } from "sonner"
import AppLayout from "@/layouts/app-layout"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { ArrowLeft, LayoutGrid, List, Plus } from "lucide-react"
import { formatCurrency as formatCurrencyUtil } from "@/utils/formatting"
import type { BreadcrumbItem } from "@/types"

type InvoiceCard = {
    id: string
    number: string
    status: InvoiceStatus
    issue_date: string
    due_date: string
    total: number | string
    customer: { id: string; name: string } | null
}

type InvoiceStatus = "draft" | "sent" | "paid" | "overdue" | "cancelled"

interface BoardProps {
    columns: Record<InvoiceStatus, InvoiceCard[]>
    perColumn: number
}

const COLUMN_ORDER: InvoiceStatus[] = ["draft", "sent", "overdue", "paid", "cancelled"]

const COLUMN_META: Record<InvoiceStatus, { label: string; hint: string }> = {
    draft:     { label: "Entwurf",   hint: "Noch nicht versendet" },
    sent:      { label: "Versendet", hint: "Offen, im Zahlungsziel" },
    overdue:   { label: "Überfällig", hint: "Fälligkeit überschritten" },
    paid:      { label: "Bezahlt",   hint: "Abgeschlossen" },
    cancelled: { label: "Storniert", hint: "Über Stornorechnung" },
}

// Allowed transitions mirror InvoiceController::setStatus. Any other drop
// is rejected by the server anyway — but disabling the drop target here
// gives instant UX feedback.
const ALLOWED_TRANSITIONS: Partial<Record<InvoiceStatus, InvoiceStatus[]>> = {
    draft:   ["sent", "cancelled"],
    sent:    ["paid", "cancelled"],
    overdue: ["paid", "cancelled"],
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Rechnungen", href: "/invoices" },
    { title: "Board" },
]

function formatCurrency(v: number | string): string {
    const n = typeof v === "string" ? parseFloat(v) : v
    return formatCurrencyUtil(isNaN(n) ? 0 : n)
}

export default function InvoicesBoard() {
    const { columns: initialColumns, perColumn } = usePage<BoardProps>().props
    // Local state so drops reflect instantly without a full page reload.
    const [columns, setColumns] = useState(initialColumns)
    const [draggingId, setDraggingId] = useState<string | null>(null)
    const [dragFrom, setDragFrom]     = useState<InvoiceStatus | null>(null)
    const [hoverTarget, setHoverTarget] = useState<InvoiceStatus | null>(null)

    const moveCard = (card: InvoiceCard, from: InvoiceStatus, to: InvoiceStatus) => {
        setColumns((cols) => {
            const next = { ...cols }
            next[from] = next[from].filter((c) => c.id !== card.id)
            next[to]   = [{ ...card, status: to }, ...next[to]]
            return next
        })
    }

    const handleDrop = async (to: InvoiceStatus) => {
        const id   = draggingId
        const from = dragFrom
        setDraggingId(null)
        setDragFrom(null)
        setHoverTarget(null)

        if (!id || !from || from === to) return

        const card = columns[from].find((c) => c.id === id)
        if (!card) return

        if (!ALLOWED_TRANSITIONS[from]?.includes(to)) {
            toast.error(`Wechsel von "${COLUMN_META[from].label}" nach "${COLUMN_META[to].label}" nicht erlaubt.`)
            return
        }

        // Optimistic UI — revert on error.
        moveCard(card, from, to)

        try {
            await axios.patch(`/invoices/${card.id}/status`, { status: to })
            toast.success(`Rechnung ${card.number}: ${COLUMN_META[to].label}`)
        } catch (err: any) {
            moveCard({ ...card, status: from }, to, from)
            const msg = err?.response?.data?.message ?? "Status konnte nicht geändert werden."
            toast.error(msg)
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Rechnungen – Board" />

            <div className="flex items-center justify-between gap-2 flex-wrap">
                <div className="flex items-center gap-2">
                    <Link href="/invoices">
                        <Button variant="outline" size="sm">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Zurück
                        </Button>
                    </Link>
                    <h1 className="text-xl font-semibold">Rechnungen – Board</h1>
                    <Badge variant="outline" className="gap-1">
                        <LayoutGrid className="h-3 w-3" />
                        Board
                    </Badge>
                </div>
                <div className="flex items-center gap-2">
                    <Link href="/invoices">
                        <Button variant="outline" size="sm">
                            <List className="mr-2 h-4 w-4" />
                            Listenansicht
                        </Button>
                    </Link>
                    <Link href="/invoices/create">
                        <Button size="sm">
                            <Plus className="mr-2 h-4 w-4" />
                            Neue Rechnung
                        </Button>
                    </Link>
                </div>
            </div>

            <p className="text-xs text-muted-foreground">
                Ziehen Sie eine Karte in eine andere Spalte, um den Status zu ändern. GoBD-Sperre: bezahlte und stornierte Rechnungen bleiben fix.
            </p>

            <div className="grid gap-3 md:grid-cols-3 lg:grid-cols-5 flex-1 min-h-[500px]">
                {COLUMN_ORDER.map((status) => {
                    const items = columns[status] ?? []
                    const accept = !!(dragFrom && ALLOWED_TRANSITIONS[dragFrom]?.includes(status))
                    const isHover = hoverTarget === status && accept

                    return (
                        <Card
                            key={status}
                            className={[
                                "flex flex-col transition-colors",
                                isHover ? "border-primary bg-accent/30" : "",
                                dragFrom && !accept && dragFrom !== status ? "opacity-50" : "",
                            ].join(" ")}
                            onDragOver={(e) => {
                                if (!accept) return
                                e.preventDefault()
                                setHoverTarget(status)
                            }}
                            onDragLeave={() => {
                                if (hoverTarget === status) setHoverTarget(null)
                            }}
                            onDrop={(e) => {
                                e.preventDefault()
                                handleDrop(status)
                            }}
                        >
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm flex items-center justify-between">
                                    <span>{COLUMN_META[status].label}</span>
                                    <Badge variant="secondary" className="text-xs tabular-nums">
                                        {items.length}
                                        {items.length === perColumn ? "+" : ""}
                                    </Badge>
                                </CardTitle>
                                <p className="text-xs text-muted-foreground">{COLUMN_META[status].hint}</p>
                            </CardHeader>
                            <CardContent className="flex-1 space-y-2 overflow-y-auto">
                                {items.length === 0 && (
                                    <p className="text-xs text-muted-foreground py-4 text-center">—</p>
                                )}
                                {items.map((card) => (
                                    <div
                                        key={card.id}
                                        draggable={!!ALLOWED_TRANSITIONS[status]}
                                        onDragStart={(e) => {
                                            e.dataTransfer.effectAllowed = "move"
                                            setDraggingId(card.id)
                                            setDragFrom(status)
                                        }}
                                        onDragEnd={() => {
                                            setDraggingId(null)
                                            setDragFrom(null)
                                            setHoverTarget(null)
                                        }}
                                        className={[
                                            "rounded-md border bg-background p-2 text-xs shadow-sm",
                                            ALLOWED_TRANSITIONS[status] ? "cursor-grab active:cursor-grabbing" : "cursor-default",
                                            draggingId === card.id ? "opacity-40" : "",
                                        ].join(" ")}
                                    >
                                        <div className="flex items-center justify-between gap-2">
                                            <Link
                                                href={`/invoices/${card.id}`}
                                                className="font-medium hover:underline truncate"
                                                onClick={(e) => e.stopPropagation()}
                                            >
                                                {card.number}
                                            </Link>
                                            <span className="tabular-nums text-right shrink-0">
                                                {formatCurrency(card.total)}
                                            </span>
                                        </div>
                                        {card.customer && (
                                            <p className="text-muted-foreground truncate mt-0.5">
                                                {card.customer.name}
                                            </p>
                                        )}
                                        <p className="text-muted-foreground mt-0.5">
                                            Fällig {new Date(card.due_date).toLocaleDateString("de-DE")}
                                        </p>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    )
                })}
            </div>
        </AppLayout>
    )
}
