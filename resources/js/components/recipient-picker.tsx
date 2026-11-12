"use client"

import { useState } from "react"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Users, X } from "lucide-react"

export interface RecipientContact {
    id: string
    name: string
    email: string
    title?: string | null
    is_primary?: boolean
}

interface RecipientPickerProps {
    contacts: RecipientContact[]
    to: string
    /** Called with the address and, when it belongs to a contact, that contact's name. */
    onToChange: (email: string, name: string | null) => void
    cc: string[]
    onCcChange: (cc: string[]) => void
    toError?: string
    ccError?: string
}

const sameAddress = (a: string, b: string) => a.trim().toLowerCase() === b.trim().toLowerCase()
const looksLikeEmail = (value: string) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)

function ContactMenuItems({
    contacts,
    onSelect,
}: {
    contacts: RecipientContact[]
    onSelect: (contact: RecipientContact) => void
}) {
    return (
        <>
            {contacts.map((contact) => (
                <DropdownMenuItem key={contact.id} onSelect={() => onSelect(contact)} className="flex flex-col items-start gap-0">
                    <span className="font-medium">
                        {contact.name}
                        {contact.title ? <span className="font-normal text-muted-foreground"> · {contact.title}</span> : null}
                    </span>
                    <span className="text-xs text-muted-foreground">{contact.email}</span>
                </DropdownMenuItem>
            ))}
        </>
    )
}

/**
 * "An" + "CC" fields for the send dialogs: pick from the customer's
 * contacts or type any address.
 */
export function RecipientPicker({ contacts, to, onToChange, cc, onCcChange, toError, ccError }: RecipientPickerProps) {
    const [ccDraft, setCcDraft] = useState("")

    const contactFor = (email: string) => contacts.find((contact) => sameAddress(contact.email, email)) ?? null
    const activeContact = to ? contactFor(to) : null

    const addCc = (raw: string) => {
        const addresses = raw
            .split(/[,;\s]+/)
            .map((value) => value.trim())
            .filter((value) => value !== "" && looksLikeEmail(value))
            .filter((value) => !sameAddress(value, to) && !cc.some((existing) => sameAddress(existing, value)))

        if (addresses.length > 0) {
            onCcChange([...cc, ...addresses])
        }
    }

    const commitCcDraft = () => {
        if (ccDraft.trim() === "") return
        addCc(ccDraft)
        // Keep an unparseable draft visible so the user can fix it.
        if (ccDraft.split(/[,;\s]+/).every((value) => value === "" || looksLikeEmail(value))) {
            setCcDraft("")
        }
    }

    const ccCandidates = contacts.filter(
        (contact) => !sameAddress(contact.email, to) && !cc.some((existing) => sameAddress(existing, contact.email)),
    )

    return (
        <>
            <div className="space-y-2">
                <Label htmlFor="to">Empfänger *</Label>
                <div className="flex gap-2">
                    <Input
                        id="to"
                        type="email"
                        value={to}
                        onChange={(e) => onToChange(e.target.value, contactFor(e.target.value)?.name ?? null)}
                        placeholder="kunde@example.com"
                        required
                    />
                    {contacts.length > 0 && (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button type="button" variant="outline" className="shrink-0">
                                    <Users className="mr-2 h-4 w-4" />
                                    Kontakt
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-72">
                                <DropdownMenuLabel>Ansprechpartner wählen</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <ContactMenuItems
                                    contacts={contacts}
                                    onSelect={(contact) => {
                                        onToChange(contact.email, contact.name)
                                        // A contact promoted to recipient no longer belongs in CC.
                                        onCcChange(cc.filter((existing) => !sameAddress(existing, contact.email)))
                                    }}
                                />
                            </DropdownMenuContent>
                        </DropdownMenu>
                    )}
                </div>
                {activeContact && (
                    <p className="text-xs text-muted-foreground">
                        {activeContact.name}
                        {activeContact.title ? ` · ${activeContact.title}` : ""}
                    </p>
                )}
                {toError && <p className="text-sm text-red-600">{toError}</p>}
            </div>

            <div className="space-y-2">
                <Label htmlFor="cc">CC (Optional)</Label>
                {cc.length > 0 && (
                    <div className="flex flex-wrap gap-1">
                        {cc.map((address) => {
                            const contact = contactFor(address)

                            return (
                                <Badge key={address} variant="secondary" className="gap-1 pr-1">
                                    {contact ? `${contact.name} <${address}>` : address}
                                    <button
                                        type="button"
                                        aria-label={`${address} aus CC entfernen`}
                                        className="rounded-sm p-0.5 hover:bg-muted-foreground/20"
                                        onClick={() => onCcChange(cc.filter((existing) => existing !== address))}
                                    >
                                        <X className="h-3 w-3" />
                                    </button>
                                </Badge>
                            )
                        })}
                    </div>
                )}
                <div className="flex gap-2">
                    <Input
                        id="cc"
                        type="text"
                        inputMode="email"
                        value={ccDraft}
                        onChange={(e) => setCcDraft(e.target.value)}
                        onBlur={commitCcDraft}
                        onKeyDown={(e) => {
                            if (e.key === "Enter" || e.key === ",") {
                                e.preventDefault()
                                commitCcDraft()
                            }
                        }}
                        placeholder="kopie@example.com"
                    />
                    {ccCandidates.length > 0 && (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button type="button" variant="outline" className="shrink-0">
                                    <Users className="mr-2 h-4 w-4" />
                                    Kontakt
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-72">
                                <DropdownMenuLabel>In CC aufnehmen</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <ContactMenuItems contacts={ccCandidates} onSelect={(contact) => onCcChange([...cc, contact.email])} />
                            </DropdownMenuContent>
                        </DropdownMenu>
                    )}
                </div>
                {ccError && <p className="text-sm text-red-600">{ccError}</p>}
            </div>
        </>
    )
}
