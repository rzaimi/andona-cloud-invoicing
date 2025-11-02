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
    onSuccess?: () => void
}

export function SendEmailDialog({
    open,
    onOpenChange,
    type,
    documentId,
    documentNumber,
    customerEmail,
    onSuccess,
}: SendEmailDialogProps) {
    const [errors, setErrors] = useState<Record<string, string>>({})

    const { data, setData, post, processing, reset } = useForm({
        to: customerEmail || "",
        cc: "",
        subject: type === "invoice" 
            ? `Rechnung ${documentNumber}` 
            : `Angebot ${documentNumber}`,
        message: type === "invoice"
            ? `Sehr geehrte Damen und Herren,\n\nanbei erhalten Sie die Rechnung ${documentNumber}.\n\nMit freundlichen Grüßen`
            : `Sehr geehrte Damen und Herren,\n\nvielen Dank für Ihre Anfrage. Gerne unterbreiten wir Ihnen das Angebot ${documentNumber}.\n\nMit freundlichen Grüßen`,
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
                                rows={6}
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


