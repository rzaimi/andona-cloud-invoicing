import { Fragment, useRef, useState } from "react"
import { isAlternativeUnit } from "@/hooks/use-units"
import { usePage } from "@inertiajs/react"
import { Button } from "@/components/ui/button"
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { FileUp, Loader2 } from "lucide-react"

export interface GaebPosition {
    position: string
    group: string
    name: string
    description: string
    quantity: number
    unit: string
    unit_price: number
}

interface GaebImportButtonProps {
    onImport: (positions: GaebPosition[]) => void
}

export function gaebLineItems(positions: GaebPosition[], taxRate: number) {
    const base = Date.now()

    return positions.map((position, index) => ({
        id: base + index,
        product_id: undefined,
        product_sku: undefined,
        product_number: undefined,
        description: position.description,
        quantity: position.quantity,
        unit_price: position.unit_price,
        unit: position.unit,
        tax_rate: taxRate,
        total: isAlternativeUnit(position.unit) ? 0 : position.quantity * position.unit_price,
        discount_type: null as null,
        discount_value: null as null,
        discount_amount: 0,
    }))
}

export function GaebImportButton({ onImport }: GaebImportButtonProps) {
    const page = usePage()
    const enabled = ((page.props as { auth?: { user?: { company?: { settings?: { gaeb_import_enabled?: boolean } } } } }).auth?.user?.company?.settings?.gaeb_import_enabled) === true
    const inputRef = useRef<HTMLInputElement>(null)
    const [open, setOpen] = useState(false)
    const [loading, setLoading] = useState(false)
    const [error, setError] = useState<string | null>(null)
    const [fileName, setFileName] = useState("")
    const [project, setProject] = useState("")
    const [positions, setPositions] = useState<GaebPosition[]>([])

    if (!enabled) {
        return null
    }

    const reset = () => {
        setError(null)
        setFileName("")
        setProject("")
        setPositions([])
        setLoading(false)
        if (inputRef.current) {
            inputRef.current.value = ""
        }
    }

    const parseFile = async (file: File) => {
        setLoading(true)
        setError(null)
        setPositions([])
        setFileName(file.name)

        const token = (page.props as { csrf_token?: string }).csrf_token
            || document.querySelector('meta[name="csrf-token"]')?.getAttribute("content")
            || ""

        const body = new FormData()
        body.append("file", file)

        try {
            const response = await fetch("/gaeb/positions", {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": token,
                    "X-Requested-With": "XMLHttpRequest",
                },
                body,
            })
            const payload = await response.json().catch(() => ({}))
            if (!response.ok) {
                const validationMessage = payload?.errors?.file?.[0]
                setError(validationMessage || payload?.message || "Die Datei konnte nicht gelesen werden.")
                setLoading(false)
                return
            }

            const imported = (payload.positions ?? []) as GaebPosition[]
            setPositions(imported)
            const title = [payload.project, payload.label].filter(Boolean).join(" — ")
            const boq = payload.boq ? ` · ${payload.boq}` : ""
            setProject(`${title}${boq}`)
        } catch {
            setError("Die Datei konnte nicht gelesen werden.")
        }
        setLoading(false)
    }

    const apply = () => {
        if (positions.length === 0) return
        onImport(positions)
        setOpen(false)
        reset()
    }

    return (
        <>
            <Button type="button" variant="outline" size="sm" onClick={() => { reset(); setOpen(true) }}>
                <FileUp className="mr-2 h-4 w-4" />
                GAEB importieren
            </Button>
            <Dialog open={open} onOpenChange={(next) => { setOpen(next); if (!next) reset() }}>
                <DialogContent className="max-w-3xl max-h-[80vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>GAEB-Positionen importieren</DialogTitle>
                        <DialogDescription>
                            Es werden nur die Positionen übernommen und als benutzerdefinierte Zeilen eingefügt. Vorbemerkungen und Hinweise bleiben draußen. Einheitspreise bleiben 0, sofern die Datei keine Preise enthält.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-3">
                        <input
                            ref={inputRef}
                            type="file"
                            accept=".X81,.x81,.X82,.x82,.X83,.x83,.X84,.x84,.X85,.x85,.X86,.x86,.xml,.gaeb"
                            className="block w-full text-sm file:mr-3 file:rounded-md file:border file:bg-background file:px-3 file:py-1.5"
                            onChange={(event) => {
                                const file = event.target.files?.[0]
                                if (file) parseFile(file)
                            }}
                        />
                        {loading && (
                            <p className="flex items-center gap-2 text-sm text-muted-foreground">
                                <Loader2 className="h-4 w-4 animate-spin" />
                                Datei wird gelesen…
                            </p>
                        )}
                        {error && <p className="text-sm text-red-600">{error}</p>}
                        {positions.length > 0 && (
                            <div className="space-y-2">
                                <p className="text-sm text-muted-foreground">
                                    {fileName}{project ? ` · ${project}` : ""} · {positions.length} {positions.length === 1 ? "Position" : "Positionen"}
                                </p>
                                <div className="border rounded-lg max-h-[360px] overflow-y-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Pos.</TableHead>
                                                <TableHead>Beschreibung</TableHead>
                                                <TableHead>Menge</TableHead>
                                                <TableHead>Einheit</TableHead>
                                                <TableHead>EP</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {positions.map((position, index) => {
                                                const showGroup = position.group !== "" && position.group !== positions[index - 1]?.group
                                                return (
                                                    <Fragment key={`${position.position}-${index}`}>
                                                        {showGroup && (
                                                            <TableRow>
                                                                <TableCell colSpan={5} className="bg-muted/50 text-sm font-medium">
                                                                    {position.group}
                                                                </TableCell>
                                                            </TableRow>
                                                        )}
                                                        <TableRow>
                                                            <TableCell className="whitespace-nowrap">{position.position}</TableCell>
                                                            <TableCell className="whitespace-pre-line">{position.description}</TableCell>
                                                            <TableCell>{position.quantity.toLocaleString("de-DE")}</TableCell>
                                                            <TableCell>{position.unit}</TableCell>
                                                            <TableCell>{position.unit_price.toLocaleString("de-DE", { minimumFractionDigits: 2, maximumFractionDigits: 2 })} €</TableCell>
                                                        </TableRow>
                                                    </Fragment>
                                                )
                                            })}
                                        </TableBody>
                                    </Table>
                                </div>
                            </div>
                        )}
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setOpen(false)}>Abbrechen</Button>
                        <Button type="button" onClick={apply} disabled={positions.length === 0 || loading}>
                            Als benutzerdefinierte Positionen übernehmen
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    )
}
