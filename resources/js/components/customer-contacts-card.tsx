"use client"

import { useState } from "react"
import { router, useForm } from "@inertiajs/react"
import { route } from "ziggy-js"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Checkbox } from "@/components/ui/checkbox"
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import type { CustomerContact } from "@/types"
import { Mail, Pencil, Phone, Plus, Trash2, Users } from "lucide-react"

interface CustomerContactsCardProps {
    customerId: string
    contacts: CustomerContact[]
}

const emptyForm = {
    name: "",
    email: "",
    title: "",
    phone: "",
    is_primary: false,
    receives_invoices: false,
    receives_reminders: false,
    receives_offers: false,
}

const PURPOSE_FLAGS = [
    { key: "receives_invoices", label: "Rechnungen", hint: "erhält Rechnungen und Stornorechnungen" },
    { key: "receives_reminders", label: "Mahnungen", hint: "erhält Zahlungserinnerungen und Mahnungen" },
    { key: "receives_offers", label: "Angebote", hint: "erhält Angebote und Angebots-Erinnerungen" },
] as const

export function CustomerContactsCard({ customerId, contacts }: CustomerContactsCardProps) {
    const [open, setOpen] = useState(false)
    const [editing, setEditing] = useState<CustomerContact | null>(null)
    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({ ...emptyForm })

    const openCreate = () => {
        setEditing(null)
        reset()
        clearErrors()
        setOpen(true)
    }

    const openEdit = (contact: CustomerContact) => {
        setEditing(contact)
        clearErrors()
        setData({
            name: contact.name,
            email: contact.email ?? "",
            title: contact.title ?? "",
            phone: contact.phone ?? "",
            is_primary: contact.is_primary,
            receives_invoices: contact.receives_invoices,
            receives_reminders: contact.receives_reminders,
            receives_offers: contact.receives_offers,
        })
        setOpen(true)
    }

    const submit = (e: React.FormEvent) => {
        e.preventDefault()
        // The dialog is portaled, but React still bubbles the submit to a
        // surrounding <form> (the customer edit form) — stop it here.
        e.stopPropagation()
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false)
                reset()
            },
        }

        if (editing) {
            put(route("customers.contacts.update", [customerId, editing.id]), options)
        } else {
            post(route("customers.contacts.store", customerId), options)
        }
    }

    const remove = (contact: CustomerContact) => {
        if (!confirm(`Ansprechpartner „${contact.name}“ wirklich löschen?`)) return
        router.delete(route("customers.contacts.destroy", [customerId, contact.id]), { preserveScroll: true })
    }

    return (
        <Card>
            <CardHeader>
                <div className="flex items-start justify-between gap-2">
                    <div>
                        <CardTitle className="flex items-center gap-2">
                            <Users className="h-5 w-5" />
                            Ansprechpartner
                        </CardTitle>
                        <CardDescription>
                            Personen, an die E-Mails gesendet werden können. Ohne Zuordnung gehen E-Mails an die
                            Haupt-E-Mail-Adresse des Kunden.
                        </CardDescription>
                    </div>
                    <Button type="button" size="sm" onClick={openCreate}>
                        <Plus className="mr-2 h-4 w-4" />
                        Hinzufügen
                    </Button>
                </div>
            </CardHeader>
            <CardContent>
                {contacts.length === 0 ? (
                    <p className="text-sm text-muted-foreground">Noch keine Ansprechpartner angelegt.</p>
                ) : (
                    <div className="divide-y">
                        {contacts.map((contact) => (
                            <div key={contact.id} className="flex items-start justify-between gap-3 py-3 first:pt-0 last:pb-0">
                                <div className="min-w-0 space-y-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="font-medium">{contact.name}</span>
                                        {contact.title && (
                                            <span className="text-sm text-muted-foreground">{contact.title}</span>
                                        )}
                                    </div>
                                    <div className="flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground">
                                        {contact.email && (
                                            <a href={`mailto:${contact.email}`} className="flex items-center gap-1 hover:underline">
                                                <Mail className="h-3.5 w-3.5" />
                                                {contact.email}
                                            </a>
                                        )}
                                        {contact.phone && (
                                            <a href={`tel:${contact.phone}`} className="flex items-center gap-1 hover:underline">
                                                <Phone className="h-3.5 w-3.5" />
                                                {contact.phone}
                                            </a>
                                        )}
                                    </div>
                                    <div className="flex flex-wrap gap-1 pt-0.5">
                                        {contact.is_primary && <Badge>Hauptansprechpartner</Badge>}
                                        {PURPOSE_FLAGS.filter((flag) => contact[flag.key]).map((flag) => (
                                            <Badge key={flag.key} variant="secondary">
                                                {flag.label}
                                            </Badge>
                                        ))}
                                    </div>
                                </div>
                                <div className="flex shrink-0 gap-1">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label={`${contact.name} bearbeiten`}
                                        onClick={() => openEdit(contact)}
                                    >
                                        <Pencil className="h-4 w-4" />
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label={`${contact.name} löschen`}
                                        onClick={() => remove(contact)}
                                    >
                                        <Trash2 className="h-4 w-4 text-red-600" />
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>

            <Dialog open={open} onOpenChange={(next) => !processing && setOpen(next)}>
                <DialogContent className="sm:max-w-[480px]">
                    <DialogHeader>
                        <DialogTitle>{editing ? "Ansprechpartner bearbeiten" : "Ansprechpartner hinzufügen"}</DialogTitle>
                        <DialogDescription>
                            Legen Sie fest, welche E-Mails diese Person standardmäßig erhält.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submit}>
                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="contact-name">Name *</Label>
                                <Input
                                    id="contact-name"
                                    value={data.name}
                                    onChange={(e) => setData("name", e.target.value)}
                                    required
                                />
                                {errors.name && <p className="text-sm text-red-600">{errors.name}</p>}
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="contact-email">E-Mail-Adresse *</Label>
                                <Input
                                    id="contact-email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData("email", e.target.value)}
                                    required
                                />
                                {errors.email && <p className="text-sm text-red-600">{errors.email}</p>}
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="contact-title">Position</Label>
                                    <Input
                                        id="contact-title"
                                        value={data.title}
                                        onChange={(e) => setData("title", e.target.value)}
                                        placeholder="z.B. Buchhaltung"
                                    />
                                    {errors.title && <p className="text-sm text-red-600">{errors.title}</p>}
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="contact-phone">Telefon</Label>
                                    <Input
                                        id="contact-phone"
                                        value={data.phone}
                                        onChange={(e) => setData("phone", e.target.value)}
                                    />
                                    {errors.phone && <p className="text-sm text-red-600">{errors.phone}</p>}
                                </div>
                            </div>

                            <div className="space-y-3 rounded-lg border p-3">
                                <p className="text-sm font-medium">Standardempfänger für</p>
                                {PURPOSE_FLAGS.map((flag) => (
                                    <label key={flag.key} className="flex items-start gap-3">
                                        <Checkbox
                                            className="mt-0.5"
                                            checked={data[flag.key]}
                                            onCheckedChange={(checked) => setData(flag.key, checked === true)}
                                        />
                                        <span className="flex flex-col">
                                            <span className="text-sm">{flag.label}</span>
                                            <span className="text-xs text-muted-foreground">{flag.hint}</span>
                                        </span>
                                    </label>
                                ))}
                            </div>

                            <label className="flex items-start gap-3">
                                <Checkbox
                                    className="mt-0.5"
                                    checked={data.is_primary}
                                    onCheckedChange={(checked) => setData("is_primary", checked === true)}
                                />
                                <span className="flex flex-col">
                                    <span className="text-sm">Hauptansprechpartner</span>
                                    <span className="text-xs text-muted-foreground">
                                        wird als Ansprechpartner auf Rechnungen und Angeboten gedruckt
                                    </span>
                                </span>
                            </label>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setOpen(false)} disabled={processing}>
                                Abbrechen
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? "Wird gespeichert…" : "Speichern"}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </Card>
    )
}
