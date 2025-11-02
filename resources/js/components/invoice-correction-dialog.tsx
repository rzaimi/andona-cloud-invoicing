import { useState } from "react"
import { useForm } from "@inertiajs/react"
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog"
import { Button } from "@/components/ui/button"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { AlertTriangle } from "lucide-react"

interface Invoice {
    id: string
    number: string
    total: number
}

interface InvoiceCorrectionDialogProps {
    open: boolean
    onOpenChange: (open: boolean) => void
    invoice: Invoice
}

export function InvoiceCorrectionDialog({ open, onOpenChange, invoice }: InvoiceCorrectionDialogProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        correction_reason: "",
        create_new_invoice: false,
    })

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        
        post(route("invoices.create-correction", invoice.id), {
            onSuccess: () => {
                reset()
                onOpenChange(false)
            },
        })
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[600px]">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Stornorechnung erstellen</DialogTitle>
                        <DialogDescription>
                            Erstellen Sie eine Stornorechnung für <strong>{invoice.number}</strong>
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-4">
                        <Alert variant="destructive">
                            <AlertTriangle className="h-4 w-4" />
                            <AlertDescription>
                                <strong>Wichtig:</strong> Nach deutschem Steuerrecht können versendete Rechnungen nicht 
                                einfach gelöscht oder bearbeitet werden. Es wird eine Stornorechnung mit negativen Beträgen 
                                erstellt, die die ursprüngliche Rechnung aufhebt.
                            </AlertDescription>
                        </Alert>

                        <div className="space-y-2">
                            <Label htmlFor="correction_reason">
                                Grund für die Stornierung <span className="text-red-500">*</span>
                            </Label>
                            <Textarea
                                id="correction_reason"
                                placeholder="z.B. Fehler in der Rechnungsstellung, Kunde hat storniert, Preisfehler..."
                                value={data.correction_reason}
                                onChange={(e) => setData("correction_reason", e.target.value)}
                                rows={4}
                                className={errors.correction_reason ? "border-red-500" : ""}
                                required
                            />
                            {errors.correction_reason && (
                                <p className="text-sm text-red-600">{errors.correction_reason}</p>
                            )}
                        </div>

                        <div className="rounded-lg bg-muted p-4 space-y-2">
                            <h4 className="font-medium">Was passiert:</h4>
                            <ul className="list-disc list-inside text-sm space-y-1">
                                <li>Eine Stornorechnung mit negativen Beträgen wird erstellt</li>
                                <li>Die ursprüngliche Rechnung wird als "Storniert" markiert</li>
                                <li>Die Stornorechnung referenziert die ursprüngliche Rechnung</li>
                                <li>Beide Rechnungen bleiben im System für die Buchhaltung</li>
                            </ul>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                            disabled={processing}
                        >
                            Abbrechen
                        </Button>
                        <Button type="submit" variant="destructive" disabled={processing}>
                            {processing ? "Wird erstellt..." : "Stornorechnung erstellen"}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    )
}

