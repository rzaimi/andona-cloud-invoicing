"use client"

import { router, usePage } from "@inertiajs/react"
import { Command } from "cmdk"
import {
    Calendar as CalendarIcon,
    FileText,
    Home,
    LayoutTemplate,
    Package,
    ReceiptText,
    Settings,
    Users,
} from "lucide-react"
import { useEffect, useState } from "react"
import axios from "axios"

type NavEntry = {
    group: "Neu" | "Navigieren"
    label: string
    shortcut?: string
    href: string
    icon: React.ComponentType<{ className?: string }>
}

/**
 * Static navigation entries. Customer search is fetched on demand from the
 * existing customers index endpoint so we don't pre-load the whole customer
 * list into every page.
 */
const STATIC_ENTRIES: NavEntry[] = [
    { group: "Neu", label: "Neue Rechnung",  href: "/invoices/create",  icon: ReceiptText },
    { group: "Neu", label: "Neues Angebot",  href: "/offers/create",    icon: FileText },
    { group: "Neu", label: "Neuer Kunde",    href: "/customers/create", icon: Users },
    { group: "Neu", label: "Neues Produkt",  href: "/products/create",  icon: Package },
    { group: "Neu", label: "Neue Ausgabe",   href: "/expenses/create",  icon: ReceiptText },

    { group: "Navigieren", label: "Dashboard",         href: "/dashboard",        icon: Home },
    { group: "Navigieren", label: "Rechnungen",        href: "/invoices",         icon: ReceiptText },
    { group: "Navigieren", label: "Angebote",          href: "/offers",           icon: FileText },
    { group: "Navigieren", label: "Kunden",            href: "/customers",        icon: Users },
    { group: "Navigieren", label: "Produkte",          href: "/products",         icon: Package },
    { group: "Navigieren", label: "Ausgaben",          href: "/expenses",         icon: ReceiptText },
    { group: "Navigieren", label: "Kalender",          href: "/calendar",         icon: CalendarIcon },
    { group: "Navigieren", label: "Einstellungen",     href: "/settings",         icon: Settings },
    { group: "Navigieren", label: "Rechnungslayouts",  href: "/invoice-layouts",  icon: LayoutTemplate },
    { group: "Navigieren", label: "Angebotslayouts",   href: "/offer-layouts",    icon: LayoutTemplate },
]

type CustomerHit = { id: string; name: string; number?: string | null }

export function CommandPalette() {
    const [open, setOpen] = useState(false)
    const [search, setSearch] = useState("")
    const [customers, setCustomers] = useState<CustomerHit[]>([])

    // Cmd/Ctrl+K toggles the palette. Registers once.
    useEffect(() => {
        const onKey = (e: KeyboardEvent) => {
            if (e.key === "k" && (e.metaKey || e.ctrlKey)) {
                e.preventDefault()
                setOpen((o) => !o)
            }
        }
        window.addEventListener("keydown", onKey)
        return () => window.removeEventListener("keydown", onKey)
    }, [])

    // Customer search — only fires after 2+ chars and when the palette is
    // open. Results come from the regular index (JSON via Inertia would
    // require a new endpoint; axios + HTML list is fine here and keeps
    // payload small via the `only` partial-reload pattern isn't applicable
    // without an Inertia-first route, so we query the simple API).
    useEffect(() => {
        if (!open || search.length < 2) {
            setCustomers([])
            return
        }
        const controller = new AbortController()
        const handle = window.setTimeout(() => {
            axios
                .get("/customers/search", {
                    params: { q: search },
                    signal: controller.signal,
                })
                .then((res) => {
                    const rows = res.data?.data ?? []
                    setCustomers(
                        rows.map((c: any) => ({
                            id:     c.id,
                            name:   c.name,
                            number: c.number,
                        }))
                    )
                })
                .catch(() => { /* ignore — search is best-effort */ })
        }, 150)
        return () => {
            controller.abort()
            window.clearTimeout(handle)
        }
    }, [open, search])

    const go = (href: string) => {
        setOpen(false)
        setSearch("")
        router.visit(href)
    }

    // Group static entries for rendering.
    const groupedStatic = STATIC_ENTRIES.reduce<Record<string, NavEntry[]>>((acc, entry) => {
        acc[entry.group] ??= []
        acc[entry.group].push(entry)
        return acc
    }, {})

    if (!open) return null

    return (
        <div
            className="fixed inset-0 z-50 flex items-start justify-center bg-black/40 p-4 pt-[15vh]"
            onClick={(e) => { if (e.target === e.currentTarget) setOpen(false) }}
        >
            <Command
                label="Schnellaktionen"
                className="w-full max-w-lg rounded-lg border bg-popover text-popover-foreground shadow-lg"
            >
                <div className="border-b px-3">
                    <Command.Input
                        autoFocus
                        value={search}
                        onValueChange={setSearch}
                        placeholder="Befehl eingeben oder Kunden suchen…"
                        className="h-11 w-full bg-transparent outline-none text-sm"
                    />
                </div>

                <Command.List className="max-h-80 overflow-y-auto p-2">
                    <Command.Empty className="py-6 text-center text-sm text-muted-foreground">
                        Keine Treffer.
                    </Command.Empty>

                    {Object.entries(groupedStatic).map(([group, entries]) => (
                        <Command.Group key={group} heading={group} className="text-xs uppercase tracking-wide text-muted-foreground px-2 py-1">
                            {entries.map((entry) => {
                                const Icon = entry.icon
                                return (
                                    <Command.Item
                                        key={entry.href}
                                        value={`${entry.label} ${entry.group}`}
                                        onSelect={() => go(entry.href)}
                                        className="flex items-center gap-2 rounded px-2 py-1.5 text-sm aria-selected:bg-accent cursor-pointer"
                                    >
                                        <Icon className="h-4 w-4 text-muted-foreground" />
                                        <span>{entry.label}</span>
                                    </Command.Item>
                                )
                            })}
                        </Command.Group>
                    ))}

                    {customers.length > 0 && (
                        <Command.Group heading="Kunden" className="text-xs uppercase tracking-wide text-muted-foreground px-2 py-1">
                            {customers.map((c) => (
                                <Command.Item
                                    key={c.id}
                                    value={`${c.name} ${c.number ?? ""}`}
                                    onSelect={() => go(`/customers/${c.id}`)}
                                    className="flex items-center gap-2 rounded px-2 py-1.5 text-sm aria-selected:bg-accent cursor-pointer"
                                >
                                    <Users className="h-4 w-4 text-muted-foreground" />
                                    <span className="truncate">{c.name}</span>
                                    {c.number && (
                                        <span className="ml-auto text-xs text-muted-foreground shrink-0">
                                            {c.number}
                                        </span>
                                    )}
                                </Command.Item>
                            ))}
                        </Command.Group>
                    )}
                </Command.List>

                <div className="border-t px-3 py-2 text-xs text-muted-foreground flex items-center justify-between">
                    <span>
                        <kbd className="rounded border px-1 py-px text-[10px]">↑↓</kbd> wechseln ·
                        <kbd className="rounded border px-1 py-px text-[10px] ml-1">↵</kbd> ausführen
                    </span>
                    <span>
                        <kbd className="rounded border px-1 py-px text-[10px]">Esc</kbd> schliessen
                    </span>
                </div>
            </Command>
        </div>
    )
}
