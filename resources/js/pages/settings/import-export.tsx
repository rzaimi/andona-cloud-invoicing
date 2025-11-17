"use client"

import { Head, useForm, usePage } from "@inertiajs/react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Upload, Download, FileText, CheckCircle, AlertTriangle, Info } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { User } from "@/types"
import { route } from "ziggy-js"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"

interface ImportExportProps {
    user: User
}

export default function ImportExportSettings() {
    const { props } = usePage<ImportExportProps>()
    const user = props.auth?.user || props.user

    const customerForm = useForm({
        file: null as File | null,
    })

    const productForm = useForm({
        file: null as File | null,
    })

    const invoiceForm = useForm({
        file: null as File | null,
    })

    const handleCustomerImport = (e: React.FormEvent) => {
        e.preventDefault()
        if (!customerForm.data.file) return

        customerForm.post(route('import.customers'), {
            forceFormData: true,
        })
    }

    const handleProductImport = (e: React.FormEvent) => {
        e.preventDefault()
        if (!productForm.data.file) return

        productForm.post(route('import.products'), {
            forceFormData: true,
        })
    }

    const handleInvoiceImport = (e: React.FormEvent) => {
        e.preventDefault()
        if (!invoiceForm.data.file) return

        invoiceForm.post(route('import.invoices'), {
            forceFormData: true,
        })
    }

    return (
        <AppLayout user={user}>
            <Head title="Import & Export" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">Import & Export</h1>
                    <p className="text-gray-600 mt-2">
                        Importieren Sie Daten aus alten Systemen oder exportieren Sie Daten für Backup und Analyse
                    </p>
                </div>

                <Alert>
                    <Info className="h-4 w-4" />
                    <AlertDescription>
                        <strong>Hinweis:</strong> Import-Funktionen sind nur für Administratoren verfügbar. 
                        Export-Funktionen können von allen Benutzern verwendet werden.
                    </AlertDescription>
                </Alert>

                <Tabs defaultValue="export" className="space-y-4">
                    <TabsList>
                        <TabsTrigger value="export">Export</TabsTrigger>
                        <TabsTrigger value="import">Import</TabsTrigger>
                    </TabsList>

                    {/* Export Tab */}
                    <TabsContent value="export" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Daten exportieren</CardTitle>
                                <CardDescription>
                                    Exportieren Sie Ihre Daten als CSV-Datei für Backup oder Analyse
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <h3 className="font-semibold">Kunden exportieren</h3>
                                        <p className="text-sm text-gray-600">
                                            Exportieren Sie alle Kunden mit ihren Kontaktdaten
                                        </p>
                                        <Button
                                            variant="outline"
                                            onClick={() => {
                                                window.location.href = route('export.customers')
                                            }}
                                        >
                                            <Download className="mr-2 h-4 w-4" />
                                            Kunden exportieren
                                        </Button>
                                    </div>

                                    <div className="space-y-2">
                                        <h3 className="font-semibold">Produkte exportieren</h3>
                                        <p className="text-sm text-gray-600">
                                            Exportieren Sie alle Produkte mit Preisen und Lagerbeständen
                                        </p>
                                        <Button
                                            variant="outline"
                                            onClick={() => {
                                                window.location.href = route('export.products')
                                            }}
                                        >
                                            <Download className="mr-2 h-4 w-4" />
                                            Produkte exportieren
                                        </Button>
                                    </div>

                                    <div className="space-y-2">
                                        <h3 className="font-semibold">Rechnungen exportieren</h3>
                                        <p className="text-sm text-gray-600">
                                            Exportieren Sie alle Rechnungen mit Kundendaten und Beträgen
                                        </p>
                                        <Button
                                            variant="outline"
                                            onClick={() => {
                                                window.location.href = route('export.invoices')
                                            }}
                                        >
                                            <Download className="mr-2 h-4 w-4" />
                                            Rechnungen exportieren
                                        </Button>
                                    </div>

                                    <div className="space-y-2">
                                        <h3 className="font-semibold">Angebote exportieren</h3>
                                        <p className="text-sm text-gray-600">
                                            Exportieren Sie alle Angebote mit Kundendaten und Beträgen
                                        </p>
                                        <Button
                                            variant="outline"
                                            onClick={() => {
                                                window.location.href = route('export.offers')
                                            }}
                                        >
                                            <Download className="mr-2 h-4 w-4" />
                                            Angebote exportieren
                                        </Button>
                                    </div>
                                </div>

                                <Alert>
                                    <Info className="h-4 w-4" />
                                    <AlertDescription>
                                        Exportierte Dateien werden im CSV-Format (Semikolon-getrennt, UTF-8) erstellt 
                                        und sind kompatibel mit Microsoft Excel und anderen Tabellenkalkulationsprogrammen.
                                    </AlertDescription>
                                </Alert>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Import Tab */}
                    <TabsContent value="import" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Kunden importieren</CardTitle>
                                <CardDescription>
                                    Importieren Sie Kunden aus einer CSV-Datei (z.B. aus einem alten System)
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleCustomerImport} className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="customer-file">CSV-Datei</Label>
                                        <Input
                                            id="customer-file"
                                            type="file"
                                            accept=".csv,.txt"
                                            onChange={(e) => customerForm.setData('file', e.target.files?.[0] || null)}
                                            required
                                        />
                                        {customerForm.errors.file && (
                                            <p className="text-sm text-red-600">{customerForm.errors.file}</p>
                                        )}
                                    </div>

                                    {customerForm.recentlySuccessful && (
                                        <Alert>
                                            <CheckCircle className="h-4 w-4" />
                                            <AlertDescription>
                                                Kunden erfolgreich importiert!
                                            </AlertDescription>
                                        </Alert>
                                    )}

                                    <div className="flex gap-2">
                                        <Button type="submit" disabled={customerForm.processing || !customerForm.data.file}>
                                            <Upload className="mr-2 h-4 w-4" />
                                            {customerForm.processing ? "Wird importiert..." : "Kunden importieren"}
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => {
                                                window.location.href = route('export.customers')
                                            }}
                                        >
                                            <FileText className="mr-2 h-4 w-4" />
                                            Beispiel-Export herunterladen
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Produkte importieren</CardTitle>
                                <CardDescription>
                                    Importieren Sie Produkte aus einer CSV-Datei (z.B. aus einem alten System)
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleProductImport} className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="product-file">CSV-Datei</Label>
                                        <Input
                                            id="product-file"
                                            type="file"
                                            accept=".csv,.txt"
                                            onChange={(e) => productForm.setData('file', e.target.files?.[0] || null)}
                                            required
                                        />
                                        {productForm.errors.file && (
                                            <p className="text-sm text-red-600">{productForm.errors.file}</p>
                                        )}
                                    </div>

                                    {productForm.recentlySuccessful && (
                                        <Alert>
                                            <CheckCircle className="h-4 w-4" />
                                            <AlertDescription>
                                                Produkte erfolgreich importiert!
                                            </AlertDescription>
                                        </Alert>
                                    )}

                                    <div className="flex gap-2">
                                        <Button type="submit" disabled={productForm.processing || !productForm.data.file}>
                                            <Upload className="mr-2 h-4 w-4" />
                                            {productForm.processing ? "Wird importiert..." : "Produkte importieren"}
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => {
                                                window.location.href = route('export.products')
                                            }}
                                        >
                                            <FileText className="mr-2 h-4 w-4" />
                                            Beispiel-Export herunterladen
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Rechnungen importieren</CardTitle>
                                <CardDescription>
                                    Importieren Sie Rechnungen aus einem alten System. Kunden werden automatisch erstellt, falls sie nicht existieren.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleInvoiceImport} className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="invoice-file">CSV-Datei</Label>
                                        <Input
                                            id="invoice-file"
                                            type="file"
                                            accept=".csv,.txt"
                                            onChange={(e) => invoiceForm.setData('file', e.target.files?.[0] || null)}
                                            required
                                        />
                                        {invoiceForm.errors.file && (
                                            <p className="text-sm text-red-600">{invoiceForm.errors.file}</p>
                                        )}
                                    </div>

                                    {invoiceForm.recentlySuccessful && (
                                        <Alert>
                                            <CheckCircle className="h-4 w-4" />
                                            <AlertDescription>
                                                Rechnungen erfolgreich importiert!
                                            </AlertDescription>
                                        </Alert>
                                    )}

                                    <Alert>
                                        <AlertTriangle className="h-4 w-4" />
                                        <AlertDescription>
                                            <strong>Wichtig:</strong> Rechnungen werden mit einer einzelnen Position importiert. 
                                            Sie können die Rechnungen nach dem Import bearbeiten, um weitere Positionen hinzuzufügen.
                                        </AlertDescription>
                                    </Alert>

                                    <div className="flex gap-2">
                                        <Button type="submit" disabled={invoiceForm.processing || !invoiceForm.data.file}>
                                            <Upload className="mr-2 h-4 w-4" />
                                            {invoiceForm.processing ? "Wird importiert..." : "Rechnungen importieren"}
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => {
                                                window.location.href = route('export.invoices')
                                            }}
                                        >
                                            <FileText className="mr-2 h-4 w-4" />
                                            Beispiel-Export herunterladen
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Hilfe & Format-Anforderungen</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <h3 className="font-semibold mb-2">CSV-Format:</h3>
                                    <ul className="list-disc list-inside text-sm text-gray-600 space-y-1">
                                        <li>Semikolon (;) als Trennzeichen</li>
                                        <li>UTF-8 Kodierung (mit BOM für Excel-Kompatibilität)</li>
                                        <li>Erste Zeile enthält Spaltenüberschriften auf Deutsch</li>
                                        <li>Maximale Dateigröße: 10MB</li>
                                    </ul>
                                </div>

                                <div>
                                    <h3 className="font-semibold mb-2">Import-Hinweise:</h3>
                                    <ul className="list-disc list-inside text-sm text-gray-600 space-y-1">
                                        <li>Bestehende Einträge (gleiche Nummer/E-Mail/SKU) werden übersprungen</li>
                                        <li>Fehlerhafte Zeilen werden übersprungen und in einem Bericht angezeigt</li>
                                        <li>Der Import erfolgt in einer Transaktion - bei Fehlern wird nichts gespeichert</li>
                                        <li>Bei Rechnungen: Kunden werden automatisch erstellt, falls sie nicht existieren</li>
                                    </ul>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    )
}



