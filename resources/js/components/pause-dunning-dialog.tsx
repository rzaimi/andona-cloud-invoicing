import { useEffect, useState } from "react"
import { router } from "@inertiajs/react"
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
import { PauseCircle } from "lucide-react"
import { route } from "ziggy-js"

/** Default Mahnsperre: 14 days from today, in local time (not UTC). */
export function defaultPauseDate(): string {
    const d = new Date()
    d.setDate(d.getDate() + 14)
    const pad = (n: number) => String(n).padStart(2, "0")
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
}

function minPauseDate(): string {
    const d = new Date()
    d.setDate(d.getDate() + 1)
    const pad = (n: number) => String(n).padStart(2, "0")
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
}

interface PauseDunningDialogProps {
    invoiceId: string | null
    invoiceNumber?: string
    onClose: () => void
}

export function PauseDunningDialog({ invoiceId, invoiceNumber, onClose }: PauseDunningDialogProps) {
    const [until, setUntil] = useState(defaultPauseDate())
    const [error, setError] = useState<string | null>(null)
    const [processing, setProcessing] = useState(false)

    useEffect(() => {
        if (invoiceId) {
            setUntil(defaultPauseDate())
            setError(null)
        }
    }, [invoiceId])

    const confirm = () => {
        if (!invoiceId) return
        setProcessing(true)
        router.post(
            route("dunning.pause", invoiceId),
            { until },
            {
                preserveScroll: true,
                onSuccess: () => onClose(),
                onError: (errors) => setError(errors.until ?? "Das Datum konnte nicht gespeichert werden."),
                onFinish: () => setProcessing(false),
            },
        )
    }

    return (
        <Dialog open={invoiceId !== null} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle>Mahnlauf pausieren</DialogTitle>
                    <DialogDescription>
                        {invoiceNumber}: Bis zu diesem Datum werden keine automatischen Mahnungen
                        versendet — z.&nbsp;B. weil eine Zahlung zugesagt wurde.
                    </DialogDescription>
                </DialogHeader>
                <div className="space-y-2">
                    <Label htmlFor="pause_until">Pausiert bis</Label>
                    <Input
                        id="pause_until"
                        type="date"
                        value={until}
                        min={minPauseDate()}
                        onChange={(e) => setUntil(e.target.value)}
                    />
                    {error && <p className="text-sm text-red-600">{error}</p>}
                </div>
                <DialogFooter>
                    <Button variant="outline" onClick={onClose} disabled={processing}>
                        Abbrechen
                    </Button>
                    <Button onClick={confirm} disabled={processing}>
                        <PauseCircle className="mr-1 h-4 w-4" />
                        Pausieren
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    )
}
