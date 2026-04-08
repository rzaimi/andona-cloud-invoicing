import { useState } from "react"
import { useForm, router } from "@inertiajs/react"
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

interface SendEmailDialogProps {
    open: boolean
    onOpenChange: (open: boolean) => void
    type: "invoice" | "offer"
    documentId: string
    documentNumber: string
    customerEmail?: string
    customerName?: string
    issueDate?: string
    dueDate?: string
    validUntil?: string
    onSuccess?: () => void
}

function buildDefaultMessage(
    type: "invoice" | "offer",
    documentNumber: string,
    customerName?: string,
    issueDate?: string,
    dueDate?: string,
    validUntil?: string,
): string {
    const greeting = customerName
        ? `Sehr geehrte Damen und Herren von ${customerName},`
        : "Sehr geehrte Damen und Herren,"

    const fmt = (d?: string) =>
        d ? new Date(d).toLocaleDateString("de-DE", { day: "2-digit", month: "2-digit", year: "numeric" }) : ""

    if (type === "invoice") {
        const lines = [
            greeting,
            "",
            `anbei erhalten Sie die Rechnung ${documentNumber}${issueDate ? ` vom ${fmt(issueDate)}` : ""}.`,
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
            `vielen Dank für Ihr Interesse. Gerne unterbreiten wir Ihnen das Angebot ${documentNumber}${issueDate ? ` vom ${fmt(issueDate)}` : ""}.`,
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
    customerEmail,
    customerName,
    issueDate,
    dueDate,
    validUntil,
    onSuccess,
}: SendEmailDialogProps) {
    const [errors, setErrors] = useState<Record<string, string>>({})

    const { data, setData, post, processing, reset } = useForm({
        to: customerEmail || "",
        cc: "",
        subject: type === "invoice"
            ? `Rechnung ${documentNumber}`
            : `Angebot ${documentNumber}`,
        message: buildDefaultMessage(type, documentNumber, customerName, issueDate, dueDate, validUntil),
    })

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        setErrors({})

        const routeName = type === "invoice" ? "invoices.send" : "offers.send"

        post(route(routeName, documentId), {
            preserveScroll: true,
            onSuccess: () => {
                onOpenChange(false)
                reset()
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
            reset()
            setErrors({})
        }
    }

    return (
        <Dialog open={open} onOpenChange={handleClose}>
            <DialogContent className="sm:max-w-[600px]">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Mail className="h-5 w-5" />
                        {type === "invoice" ? "Rechnung" : "Angebot"} per E-Mail versenden
                    </DialogTitle>
                    <DialogDescription>
                        Versenden Sie {type === "invoice" ? "die Rechnung" : "das Angebot"} {documentNumber} als PDF per E-Mail an Ihren Kunden.
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

                        <div className="space-y-2">
                            <Label htmlFor="to">Empfänger *</Label>
                            <Input
                                id="to"
                                type="email"
                                value={data.to}
                                onChange={(e) => setData("to", e.target.value)}
                                placeholder="kunde@example.com"
                                required
                            />
                            {errors.to && <p className="text-sm text-red-600">{errors.to}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="cc">CC (Optional)</Label>
                            <Input
                                id="cc"
                                type="email"
                                value={data.cc}
                                onChange={(e) => setData("cc", e.target.value)}
                                placeholder="kopie@example.com"
                            />
                            {errors.cc && <p className="text-sm text-red-600">{errors.cc}</p>}
                        </div>

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


