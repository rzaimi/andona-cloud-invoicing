import { useEffect, useRef, useState } from "react"
import { useForm } from "@inertiajs/react"
import { route } from "ziggy-js"
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { Mail, Send } from "lucide-react"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { RecipientPicker, type RecipientContact } from "@/components/recipient-picker"

interface SendEmailDialogProps {
    open: boolean
    onOpenChange: (open: boolean) => void
    type: "invoice" | "offer"
    documentId: string
    documentNumber: string
    /** Enables picking the recipient/CC from the customer's contacts. */
    customerId?: string
    customerEmail?: string
    customerName?: string
    issueDate?: string
    dueDate?: string
    validUntil?: string
    isResend?: boolean
    onSuccess?: () => void
}

function buildDefaultSubject(type: "invoice" | "offer", documentNumber: string, isResend?: boolean): string {
    if (type === "invoice") {
        return isResend ? `Rechnung ${documentNumber} – erneute Zusendung` : `Rechnung ${documentNumber}`
    }

    return isResend ? `Angebot ${documentNumber} – aktualisierte Fassung` : `Angebot ${documentNumber}`
}

function buildDefaultMessage(
    type: "invoice" | "offer",
    documentNumber: string,
    customerName?: string,
    issueDate?: string,
    dueDate?: string,
    validUntil?: string,
    isResend?: boolean,
    contactName?: string | null,
): string {
    // A selected contact gets a personal (gender-neutral) greeting.
    const greeting = contactName
        ? `Guten Tag ${contactName},`
        : customerName
            ? `Sehr geehrte Damen und Herren von ${customerName},`
            : "Sehr geehrte Damen und Herren,"

    const fmt = (d?: string) =>
        d ? new Date(d).toLocaleDateString("de-DE", { day: "2-digit", month: "2-digit", year: "numeric" }) : ""

    if (type === "invoice") {
        const lines = [
            greeting,
            "",
            isResend
                ? `anbei erhalten Sie erneut die Rechnung ${documentNumber}${issueDate ? ` vom ${fmt(issueDate)}` : ""}.`
                : `anbei erhalten Sie die Rechnung ${documentNumber}${issueDate ? ` vom ${fmt(issueDate)}` : ""}.`,
            "",
            "Die Rechnung als PDF-Datei finden Sie im Anhang dieser E-Mail.",
        ]
        if (dueDate) {
            lines.push("", `Bitte überweisen Sie den Betrag bis zum ${fmt(dueDate)} unter Angabe der Rechnungsnummer ${documentNumber}.`)
        }
        lines.push("", "Bei Fragen stehen wir Ihnen gerne zur Verfügung.", "", "Mit freundlichen Grüßen")
        return lines.join("\n")
    } else {
        const lines = [
            greeting,
            "",
            isResend
                ? `anbei erhalten Sie das aktualisierte Angebot ${documentNumber}${issueDate ? ` vom ${fmt(issueDate)}` : ""}.`
                : `vielen Dank für Ihr Interesse. Gerne unterbreiten wir Ihnen das Angebot ${documentNumber}${issueDate ? ` vom ${fmt(issueDate)}` : ""}.`,
            "",
            "Das vollständige Angebot finden Sie als PDF-Datei im Anhang dieser E-Mail.",
        ]
        if (validUntil) {
            lines.push("", `Dieses Angebot ist gültig bis zum ${fmt(validUntil)}.`)
        }
        lines.push("", "Für Rückfragen stehen wir Ihnen jederzeit gerne zur Verfügung.", "", "Mit freundlichen Grüßen")
        return lines.join("\n")
    }
}

export function SendEmailDialog({
    open,
    onOpenChange,
    type,
    documentId,
    documentNumber,
    customerId,
    customerEmail,
    customerName,
    issueDate,
    dueDate,
    validUntil,
    isResend = false,
    onSuccess,
}: SendEmailDialogProps) {
    const [errors, setErrors] = useState<Record<string, string>>({})
    const [contacts, setContacts] = useState<RecipientContact[]>([])

    const messageFor = (contactName?: string | null) =>
        buildDefaultMessage(type, documentNumber, customerName, issueDate, dueDate, validUntil, isResend, contactName)

    const { data, setData, post, processing, reset } = useForm({
        to: customerEmail || "",
        to_name: "",
        cc: [] as string[],
        subject: buildDefaultSubject(type, documentNumber, isResend),
        message: messageFor(null),
    })

    // The last auto-generated message: while the textarea still equals it,
    // the greeting follows the selected recipient; once edited, it is left alone.
    const generatedMessage = useRef(data.message)
    // Set once the user picks or types a recipient, so late-arriving
    // defaults never overwrite a deliberate choice.
    const recipientTouched = useRef(false)

    const applyRecipient = (email: string, name: string | null, cc?: string[]) => {
        setData((current) => {
            const next = { ...current, to: email, to_name: name ?? "", cc: cc ?? current.cc }
            if (current.message === generatedMessage.current) {
                next.message = messageFor(name)
                generatedMessage.current = next.message
            }
            return next
        })
    }

    // Load the customer's contacts and the default recipients for this
    // document type (the same rule the automatic emails use).
    useEffect(() => {
        if (!open || !customerId) return

        let cancelled = false
        recipientTouched.current = false

        fetch(route("customers.contacts.index", { customer: customerId, purpose: type }), {
            headers: { Accept: "application/json" },
            credentials: "same-origin",
        })
            .then((response) => (response.ok ? response.json() : null))
            .then((payload) => {
                if (cancelled || !payload) return

                const loaded: RecipientContact[] = payload.contacts ?? []
                setContacts(loaded)

                const defaults = payload.defaults
                if (recipientTouched.current || !defaults?.to) return

                const isContact = loaded.some(
                    (contact) => contact.email.toLowerCase() === String(defaults.to).toLowerCase(),
                )
                applyRecipient(defaults.to, isContact ? defaults.to_name : null, defaults.cc ?? [])
            })
            .catch(() => {
                // Contacts are a convenience — the dialog works with the plain address.
            })

        return () => {
            cancelled = true
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, customerId, type])

    const resetForm = () => {
        reset()
        generatedMessage.current = messageFor(null)
    }

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        setErrors({})

        const routeName = type === "invoice" ? "invoices.send" : "offers.send"

        post(route(routeName, documentId), {
            preserveScroll: true,
            onSuccess: () => {
                onOpenChange(false)
                resetForm()
                if (onSuccess) {
                    onSuccess()
                }
            },
            onError: (errors) => {
                setErrors(errors)
            },
        })
    }

    const handleClose = () => {
        if (!processing) {
            onOpenChange(false)
            resetForm()
            setErrors({})
        }
    }

    return (
        <Dialog open={open} onOpenChange={handleClose}>
            <DialogContent className="sm:max-w-[600px]">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Mail className="h-5 w-5" />
                        {isResend
                            ? `${type === "invoice" ? "Rechnung" : "Angebot"} erneut senden`
                            : `${type === "invoice" ? "Rechnung" : "Angebot"} per E-Mail versenden`}
                    </DialogTitle>
                    <DialogDescription>
                        {isResend
                            ? `Senden Sie ${type === "invoice" ? "die Rechnung" : "das Angebot"} ${documentNumber} erneut als PDF. Sie können eine andere Empfängeradresse eintragen.`
                            : `Versenden Sie ${type === "invoice" ? "die Rechnung" : "das Angebot"} ${documentNumber} als PDF per E-Mail an Ihren Kunden.`}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit}>
                    <div className="space-y-4 py-4">
                        {Object.keys(errors).length > 0 && (
                            <Alert variant="destructive">
                                <AlertDescription>
                                    {Object.values(errors).map((error, index) => (
                                        <div key={index}>{error}</div>
                                    ))}
                                </AlertDescription>
                            </Alert>
                        )}

                        <RecipientPicker
                            contacts={contacts}
                            to={data.to}
                            onToChange={(email, name) => {
                                recipientTouched.current = true
                                applyRecipient(email, name)
                            }}
                            cc={data.cc}
                            onCcChange={(cc) => {
                                recipientTouched.current = true
                                setData("cc", cc)
                            }}
                            toError={errors.to}
                            ccError={errors.cc}
                        />

                        <div className="space-y-2">
                            <Label htmlFor="subject">Betreff *</Label>
                            <Input
                                id="subject"
                                type="text"
                                value={data.subject}
                                onChange={(e) => setData("subject", e.target.value)}
                                required
                            />
                            {errors.subject && <p className="text-sm text-red-600">{errors.subject}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="message">Nachricht (Optional)</Label>
                            <Textarea
                                id="message"
                                value={data.message}
                                onChange={(e) => setData("message", e.target.value)}
                                rows={9}
                                placeholder="Zusätzliche Nachricht..."
                            />
                            {errors.message && <p className="text-sm text-red-600">{errors.message}</p>}
                            <p className="text-xs text-muted-foreground">
                                Das PDF wird automatisch als Anhang beigefügt.
                            </p>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={handleClose}
                            disabled={processing}
                        >
                            Abbrechen
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Send className="mr-2 h-4 w-4" />
                            {processing ? "Wird gesendet..." : "E-Mail senden"}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    )
}


