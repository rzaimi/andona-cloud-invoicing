import { useEffect, useState } from "react"
import { usePage } from "@inertiajs/react"
import { useUnits } from "@/hooks/use-units"
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Badge } from "@/components/ui/badge"
import { Checkbox } from "@/components/ui/checkbox"
import { Search, Plus, Package, Check } from "lucide-react"

interface Product {
    id: string
    name: string
    description?: string
    price: number
    unit: string
    tax_rate: number
    sku?: string
    number?: string
}

interface ProductSelectorDialogProps {
    products: Product[]
    onSelect: (item: {
        description: string
        quantity: number
        unit_price: number
        unit: string
        tax_rate?: number
        product_id?: string
        product_sku?: string
        product_number?: string
    }) => void
    trigger?: React.ReactNode
}

export function ProductSelectorDialog({ products, onSelect, trigger }: ProductSelectorDialogProps) {
    const page = usePage()
    const hasProductsModule = ((page.props as { enabledModules?: string[] }).enabledModules)?.includes("products") ?? true
    const defaultTaxRate = Number((page.props as { auth?: { user?: { company?: { settings?: { tax_rate?: number } } } } }).auth?.user?.company?.settings?.tax_rate ?? 0.19)
    const [open, setOpen] = useState(false)
    const [searchTerm, setSearchTerm] = useState("")
    const [catalog, setCatalog] = useState(products)
    const [addedIds, setAddedIds] = useState<Set<string>>(new Set())
    const [addedCount, setAddedCount] = useState(0)
    const [customAdded, setCustomAdded] = useState(false)
    const [saveAsProduct, setSaveAsProduct] = useState(false)
    const [savingProduct, setSavingProduct] = useState(false)
    const [saveError, setSaveError] = useState<string | null>(null)
    const [customItem, setCustomItem] = useState({
        description: "",
        quantity: 1,
        unit_price: 0,
        unit: "Stk.",
    })

    useEffect(() => {
        setCatalog(products)
    }, [products])

    const filteredProducts = catalog.filter((product) =>
        product.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        product.description?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        product.sku?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        product.number?.toLowerCase().includes(searchTerm.toLowerCase())
    )

    const handleProductSelect = (product: Product) => {
        onSelect({
            description: product.name + (product.description ? ` - ${product.description}` : ""),
            quantity: 1,
            unit_price: Number(product.price),
            unit: product.unit,
            tax_rate: product.tax_rate,
            product_id: product.id,
            product_sku: product.sku,
            product_number: product.number,
        })

        // Show per-row feedback and update count — dialog stays open
        setAddedIds((prev) => new Set(prev).add(product.id))
        setAddedCount((c) => c + 1)

        // Remove the checkmark after 2 s so it can be re-added
        setTimeout(() => {
            setAddedIds((prev) => {
                const next = new Set(prev)
                next.delete(product.id)
                return next
            })
        }, 2000)
    }

    const handleCustomItemAdd = async () => {
        if (!customItem.description || savingProduct) return

        let productId: string | undefined
        let productNumber: string | undefined
        let productSku: string | undefined
        let taxRate: number | undefined

        if (saveAsProduct) {
            setSavingProduct(true)
            setSaveError(null)
            try {
                const token = (page.props as { csrf_token?: string }).csrf_token
                    || document.querySelector('meta[name="csrf-token"]')?.getAttribute("content")
                    || ""
                const response = await fetch("/products", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": token,
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    body: JSON.stringify({
                        name: customItem.description,
                        unit: customItem.unit,
                        price: customItem.unit_price,
                        tax_rate: defaultTaxRate,
                        stock_quantity: 0,
                        min_stock_level: 0,
                        track_stock: false,
                        is_service: false,
                        status: "active",
                    }),
                })

                const body = await response.json().catch(() => ({}))
                if (!response.ok) {
                    const validationMessage = body?.errors
                        ? Object.values(body.errors as Record<string, string[]>)[0]?.[0]
                        : null
                    setSaveError(validationMessage || body?.message || "Produkt konnte nicht gespeichert werden.")
                    setSavingProduct(false)
                    return
                }

                const created = body.product as Product
                productId = created.id
                productNumber = created.number
                productSku = created.sku
                taxRate = Number(created.tax_rate)
                setCatalog((current) => [created, ...current.filter((product) => product.id !== created.id)])
            } catch {
                setSaveError("Produkt konnte nicht gespeichert werden.")
                setSavingProduct(false)
                return
            }
            setSavingProduct(false)
        }

        onSelect({
            ...customItem,
            tax_rate: taxRate,
            product_id: productId,
            product_sku: productSku,
            product_number: productNumber,
        })
        setAddedCount((c) => c + 1)
        setCustomAdded(true)
        setSaveError(null)
        setSaveAsProduct(false)
        setCustomItem({ description: "", quantity: 1, unit_price: 0, unit: "Stk." })
        setTimeout(() => setCustomAdded(false), 2000)
    }

    const handleClose = () => {
        setOpen(false)
        setSearchTerm("")
        setAddedIds(new Set())
        setAddedCount(0)
        setCustomAdded(false)
        setSaveAsProduct(false)
        setSaveError(null)
    }

    const germanUnits = useUnits()

    return (
        <Dialog open={open} onOpenChange={(o) => { if (!o) handleClose(); else setOpen(true) }}>
            <DialogTrigger asChild>
                {trigger || (
                    <Button type="button" variant="outline" size="sm">
                        <Plus className="mr-2 h-4 w-4" />
                        Position hinzufügen
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="max-w-4xl max-h-[80vh] overflow-y-auto">
                <DialogHeader>
                    <div className="flex items-center justify-between">
                        <div>
                            <DialogTitle>Position hinzufügen</DialogTitle>
                            <DialogDescription>
                                Wählen Sie ein Produkt aus oder fügen Sie eine benutzerdefinierte Position hinzu.
                            </DialogDescription>
                        </div>
                        {addedCount > 0 && (
                            <Badge variant="secondary" className="text-sm px-3 py-1">
                                <Check className="mr-1 h-3 w-3 text-green-600" />
                                {addedCount} {addedCount === 1 ? "Position" : "Positionen"} hinzugefügt
                            </Badge>
                        )}
                    </div>
                </DialogHeader>

                <Tabs defaultValue="products" className="w-full">
                    <TabsList className="grid w-full grid-cols-2">
                        <TabsTrigger value="products">
                            <Package className="mr-2 h-4 w-4" />
                            Aus Produkten
                        </TabsTrigger>
                        <TabsTrigger value="custom">
                            <Plus className="mr-2 h-4 w-4" />
                            Benutzerdefiniert
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="products" className="space-y-4">
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                type="text"
                                placeholder="Produkt suchen (Name, Beschreibung, SKU, Nummer)..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="pl-10"
                            />
                        </div>

                        <div className="border rounded-lg max-h-[400px] overflow-y-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Produkt</TableHead>
                                        <TableHead>SKU / Nummer</TableHead>
                                        <TableHead>Preis</TableHead>
                                        <TableHead>Einheit</TableHead>
                                        <TableHead></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {filteredProducts.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={5} className="text-center text-muted-foreground py-8">
                                                {searchTerm ? "Keine Produkte gefunden." : "Keine Produkte verfügbar."}
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        filteredProducts.map((product) => {
                                            const wasAdded = addedIds.has(product.id)
                                            return (
                                                <TableRow key={product.id} className="cursor-pointer hover:bg-muted/50">
                                                    <TableCell>
                                                        <div>
                                                            <div className="font-medium">{product.name}</div>
                                                            {product.description && (
                                                                <div className="text-sm text-muted-foreground line-clamp-1">
                                                                    {product.description}
                                                                </div>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="text-sm">
                                                            {product.sku && <Badge variant="outline">{product.sku}</Badge>}
                                                            {product.number && (
                                                                <Badge variant="outline" className="ml-1">{product.number}</Badge>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        {Number(product.price).toFixed(2)} €
                                                    </TableCell>
                                                    <TableCell>{product.unit}</TableCell>
                                                    <TableCell>
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant={wasAdded ? "secondary" : "default"}
                                                            onClick={() => handleProductSelect(product)}
                                                            className={wasAdded ? "text-green-600" : ""}
                                                        >
                                                            {wasAdded ? (
                                                                <Check className="h-4 w-4" />
                                                            ) : (
                                                                <Plus className="h-4 w-4" />
                                                            )}
                                                        </Button>
                                                    </TableCell>
                                                </TableRow>
                                            )
                                        })
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        <div className="flex justify-end">
                            <Button type="button" onClick={handleClose}>
                                Fertig
                            </Button>
                        </div>
                    </TabsContent>

                    <TabsContent value="custom" className="space-y-4">
                        <div className="space-y-4">
                            <div>
                                <Label htmlFor="custom-description">Beschreibung *</Label>
                                <Input
                                    id="custom-description"
                                    value={customItem.description}
                                    onChange={(e) => setCustomItem({ ...customItem, description: e.target.value })}
                                    placeholder="z.B. Beratung, Installation, Sonderanfertigung"
                                />
                            </div>

                            <div className="grid grid-cols-3 gap-4">
                                <div>
                                    <Label htmlFor="custom-quantity">Menge *</Label>
                                    <Input
                                        id="custom-quantity"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        value={customItem.quantity}
                                        onChange={(e) => setCustomItem({ ...customItem, quantity: parseFloat(e.target.value) || 1 })}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="custom-unit-price">Einzelpreis *</Label>
                                    <Input
                                        id="custom-unit-price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={customItem.unit_price}
                                        onChange={(e) => setCustomItem({ ...customItem, unit_price: parseFloat(e.target.value) || 0 })}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="custom-unit">Einheit</Label>
                                    <select
                                        id="custom-unit"
                                        value={customItem.unit}
                                        onChange={(e) => setCustomItem({ ...customItem, unit: e.target.value })}
                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    >
                                        {germanUnits.map((unit) => (
                                            <option key={unit} value={unit}>
                                                {unit}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            {hasProductsModule && (
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="save-as-product"
                                        checked={saveAsProduct}
                                        onCheckedChange={(checked) => setSaveAsProduct(checked === true)}
                                    />
                                    <Label htmlFor="save-as-product">Als Produkt speichern</Label>
                                </div>
                            )}
                            {saveError && <p className="text-sm text-red-600">{saveError}</p>}

                            <div className="flex items-center justify-between">
                                {customAdded ? (
                                    <span className="flex items-center gap-1 text-sm text-green-600 font-medium">
                                        <Check className="h-4 w-4" />
                                        Position hinzugefügt
                                    </span>
                                ) : (
                                    <span />
                                )}
                                <div className="flex gap-2">
                                    <Button type="button" variant="outline" onClick={handleClose}>
                                        Fertig
                                    </Button>
                                    <Button
                                        type="button"
                                        onClick={handleCustomItemAdd}
                                        disabled={!customItem.description || savingProduct}
                                    >
                                        <Plus className="mr-2 h-4 w-4" />
                                        {savingProduct ? "Wird gespeichert..." : "Position hinzufügen"}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </TabsContent>
                </Tabs>
            </DialogContent>
        </Dialog>
    )
}
