"use client"

import { useState } from "react"
import axios from "axios"
import { toast } from "sonner"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog"
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select"

export interface QuickCustomer {
    id: string
    name: string
    email: string | null
}

interface QuickCustomerDialogProps {
    open: boolean
    onOpenChange: (open: boolean) => void
    /** Fires once the customer is persisted server-side. */
    onCreated: (customer: QuickCustomer) => void
}

/**
 * Minimal customer-create dialog used from forms that need to add a customer
 * without leaving the page (invoice/offer create). Hits the JSON quickStore
 * endpoint and hands the persisted row back via onCreated.
 */
export function QuickCustomerDialog({ open, onOpenChange, onCreated }: QuickCustomerDialogProps) {
    const [name, setName]         = useState("")
    const [email, setEmail]       = useState("")
    const [type, setType]         = useState<"business" | "private">("business")
    const [submitting, setSubmitting] = useState(false)
    const [errors, setErrors]     = useState<Record<string, string>>({})

    const reset = () => {
        setName("")
        setEmail("")
        setType("business")
        setErrors({})
    }

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault()
        setSubmitting(true)
        setErrors({})

        try {
            const { data } = await axios.post<QuickCustomer>("/customers/quick", {
                name,
                email: email || null,
                customer_type: type,
            })

            toast.success(`Kunde "${data.name}" angelegt`)
            onCreated(data)
            reset()
            onOpenChange(false)
        } catch (err: any) {
            // Laravel validation returns 422 with { errors: { field: [msg] } }
            if (err?.response?.status === 422 && err.response.data?.errors) {
                const flat: Record<string, string> = {}
                for (const [k, v] of Object.entries(err.response.data.errors)) {
                    flat[k] = Array.isArray(v) ? (v[0] as string) : String(v)
                }
                setErrors(flat)
            } else {
                toast.error("Kunde konnte nicht angelegt werden.")
            }
        } finally {
            setSubmitting(false)
        }
    }

    return (
        <Dialog open={open} onOpenChange={(next) => { if (!next) reset(); onOpenChange(next) }}>
            <DialogContent className="sm:max-w-md">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Neuer Kunde</DialogTitle>
                        <DialogDescription>
                            Schnell-Anlage. Für weitere Felder öffnen Sie später den vollständigen Kundendialog.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-4">
                        <div className="space-y-2">
                            <Label htmlFor="quick_customer_name">Name *</Label>
                            <Input
                                id="quick_customer_name"
                                value={name}
                                onChange={(e) => setName(e.target.value)}
                                autoFocus
                                required
                            />
                            {errors.name && <p className="text-red-600 text-sm">{errors.name}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="quick_customer_email">E-Mail</Label>
                            <Input
                                id="quick_customer_email"
                                type="email"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                            />
                            {errors.email && <p className="text-red-600 text-sm">{errors.email}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="quick_customer_type">Kundentyp *</Label>
                            <Select value={type} onValueChange={(v) => setType(v as "business" | "private")}>
                                <SelectTrigger id="quick_customer_type">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="business">Geschäftskunde</SelectItem>
                                    <SelectItem value="private">Privatkunde</SelectItem>
                                </SelectContent>
                            </Select>
                            {errors.customer_type && <p className="text-red-600 text-sm">{errors.customer_type}</p>}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                            disabled={submitting}
                        >
                            Abbrechen
                        </Button>
                        <Button type="submit" disabled={submitting || !name.trim()}>
                            {submitting ? "Speichern…" : "Anlegen & Auswählen"}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    )
}
